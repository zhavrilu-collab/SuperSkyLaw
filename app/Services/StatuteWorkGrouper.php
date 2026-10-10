<?php

namespace App\Services;

use App\Models\Statute;
use App\Models\StatuteWork;

class StatuteWorkGrouper
{
    private const AMENDMENT = '/^Zakon o (izmjenama i dopunama|izmjenama i dopuni|izmjeni i dopunama|izmjeni i dopuni|izmjenama|dopunama|izmjeni|dopuni)\s+/iu';

    public function __construct(private StatuteAreaClassifier $areas) {}

    public function attachMissing(): void
    {
        Statute::query()->whereNull('work_id')->orderBy('id')->each(function (Statute $statute): void {
            $statute->work_id = $this->workFor(
                $statute->title,
                $statute->external_id,
                ! $this->isAmendment($statute->title),
            )->id;
            $statute->save();
        });
    }

    public function regroup(): void
    {
        Statute::query()->orderBy('id')->each(function (Statute $statute): void {
            $base = ! $this->isAmendment($statute->title);
            $work = $this->workFor($statute->title, $statute->external_id, $base);
            if ((int) $statute->work_id !== (int) $work->id) {
                $statute->work_id = $work->id;
                $statute->save();
            }
        });

        StatuteWork::query()->whereDoesntHave('statutes')->delete();
    }

    public function assignAreas(): void
    {
        StatuteWork::query()->orderBy('id')->each(function (StatuteWork $work): void {
            $area = $this->areas->classify($work->title);
            if ($work->area !== $area) {
                $work->area = $area;
                $work->save();
            }
        });
    }

    public function workFor(string $publicationTitle, ?string $externalId, bool $baseAct): StatuteWork
    {
        $title = $this->workTitle($publicationTitle);
        $area = $this->areas->classify($title);
        $work = StatuteWork::query()->firstOrCreate(
            ['title_key' => $this->key($title)],
            ['title' => $title, 'area' => $area],
        );

        if ($work->title !== $title || $work->area !== $area) {
            $work->title = $title;
            $work->area = $area;
            $work->save();
        }

        if ($baseAct && ! $this->isAmendment($publicationTitle) && $work->base_external_id === null && is_string($externalId) && $externalId !== '') {
            $taken = StatuteWork::query()
                ->where('base_external_id', $externalId)
                ->where('id', '!=', $work->id)
                ->exists();
            if (! $taken) {
                $work->base_external_id = $externalId;
                $work->save();
            }
        }

        return $work;
    }

    public function workTitle(string $title): string
    {
        $title = $this->clean($title);

        for ($pass = 0; $pass < 4; $pass++) {
            $next = preg_replace('/^Ispravak\s+/iu', '', $title) ?? $title;
            $next = preg_replace('/^Odluk[ae]\s+o\s+proglašenju\s+/iu', '', $next) ?? $next;
            $next = preg_replace(self::AMENDMENT, '', $next) ?? $next;
            $next = preg_replace('/^zakona\b/iu', 'Zakon', $next) ?? $next;
            $next = preg_replace('/\s*\(pročišćeni tekst\)\s*/iu', ' ', $next) ?? $next;
            $next = $this->nominative(trim(preg_replace('/\s+/u', ' ', $next) ?? $next));
            if ($next === '' || $next === $title) {
                break;
            }
            $title = $next;
        }

        return $this->polish($title);
    }

    public function isAmendment(string $title): bool
    {
        $title = $this->clean($title);

        return preg_match('/^Ispravak\b/iu', $title) === 1
            || preg_match('/^Odluk[ae]\s+o\s+proglašenju\b/iu', $title) === 1
            || preg_match(self::AMENDMENT, $title) === 1;
    }

    public function key(string $title): string
    {
        $title = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $title) ?? $title));

        return sha1(rtrim($title, '.'));
    }

    private function clean(string $title): string
    {
        $title = str_replace(["\u{00AD}", "\u{200B}", "\u{FEFF}", "\u{00A0}"], ['', '', '', ' '], $title);
        $title = preg_replace('/\(\s+/u', '(', $title) ?? $title;
        $title = str_replace('oboljšanju', 'poboljšanju', $title);
        $title = preg_replace('/^Zaklon\b/u', 'Zakon', $title) ?? $title;

        return trim(preg_replace('/\s+/u', ' ', $title) ?? $title);
    }

    private function nominative(string $title): string
    {
        if (! preg_match('/^(.+?)\s+(zakona|zakonika)$/iu', $title, $match)) {
            return $title;
        }

        $words = preg_split('/\s+/u', $match[1]) ?: [];
        $converted = [];
        foreach ($words as $word) {
            if (preg_match('/[oe]g$/iu', $word) !== 1) {
                return $title;
            }
            $converted[] = preg_replace('/[oe]g$/iu', 'i', $word) ?? $word;
        }

        $noun = mb_strtolower($match[2]) === 'zakonika' ? 'zakonik' : 'zakon';
        $name = implode(' ', $converted).' '.$noun;

        return mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1);
    }

    private function polish(string $title): string
    {
        $title = preg_replace('/\s+Europskog parlamenta i vijeća od \d{1,2}\. \p{L}+ \d{4}\.?(?:\s+godine)?/iu', '', $title) ?? $title;
        $title = preg_replace('/[“”„"]([^"“”„]+?)[“”„"]/u', '»$1«', $title) ?? $title;
        $title = preg_replace('/Zakon o zakladi »Hrvatska za djecu«/iu', 'Zakon o zakladi »Hrvatska za djecu«', $title) ?? $title;

        return trim(preg_replace('/\s+/u', ' ', $title) ?? $title);
    }
}
