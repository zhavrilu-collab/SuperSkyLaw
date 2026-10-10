<?php

namespace App\Services;

use App\Models\Statute;
use App\Models\StatuteWork;

class StatuteWorkGrouper
{
    private const AMENDMENT = '/^Zakon o (izmjenama i dopunama|izmjeni i dopuni|izmjenama i dopuni|izmjeni i dopunama|izmjenama|dopunama|izmjeni|dopuni)\s+/iu';

    public function attachMissing(): void
    {
        Statute::query()->whereNull('work_id')->orderBy('id')->each(function (Statute $statute): void {
            $statute->work_id = $this->workFor($statute->title, $statute->external_id, ! $this->isAmendment($statute->title))->id;
            $statute->save();
        });
    }

    public function workFor(string $publicationTitle, ?string $externalId, bool $baseAct): StatuteWork
    {
        $title = $this->workTitle($publicationTitle);
        $work = StatuteWork::query()->firstOrCreate(
            ['title_key' => $this->key($title)],
            ['title' => $title],
        );

        if ($baseAct && $work->base_external_id === null && is_string($externalId) && $externalId !== '') {
            $work->base_external_id = $externalId;
            $work->save();
        }

        return $work;
    }

    public function workTitle(string $title): string
    {
        $title = trim(preg_replace('/\s+/u', ' ', $title) ?? $title);
        if (! $this->isAmendment($title)) {
            return $title;
        }

        $rest = trim((string) preg_replace(self::AMENDMENT, '', $title));
        $rest = preg_replace('/^Zakona\b/u', 'Zakon', $rest) ?? $rest;

        return $rest !== '' ? $rest : $title;
    }

    public function isAmendment(string $title): bool
    {
        return preg_match(self::AMENDMENT, trim($title)) === 1;
    }

    public function key(string $title): string
    {
        $title = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $title) ?? $title));

        return sha1(rtrim($title, '.'));
    }
}
