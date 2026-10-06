<?php

namespace App\Services;

use App\Enums\CourtEventType;
use App\Models\CourtEvent;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class IcsCalendar
{
    public function export(Organization $organization, Collection $events): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//SuperSkyLaw//Kalendar//HR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        foreach ($events as $event) {
            $lines = array_merge($lines, $this->eventLines($event));
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * @return list<array{uid: string, title: string, starts_at: Carbon, location: ?string}>
     */
    public function parse(string $ics): array
    {
        $events = [];
        foreach (preg_split('/BEGIN:VEVENT/i', $ics) as $index => $block) {
            if ($index === 0 || ! str_contains(strtoupper($block), 'END:VEVENT')) {
                continue;
            }
            $uid = $this->field($block, 'UID');
            $title = $this->field($block, 'SUMMARY') ?: 'Vanjski termin';
            $start = $this->moment($this->field($block, 'DTSTART'));
            if ($uid === null || $start === null) {
                continue;
            }
            $events[] = [
                'uid' => $uid,
                'title' => $title,
                'starts_at' => $start,
                'location' => $this->field($block, 'LOCATION'),
            ];
        }

        return $events;
    }

    public function import(Organization $organization, string $ics): int
    {
        $created = 0;
        foreach ($this->parse($ics) as $event) {
            $exists = CourtEvent::query()
                ->where('organization_id', $organization->id)
                ->where('external_uid', $event['uid'])
                ->exists();
            if ($exists) {
                continue;
            }
            CourtEvent::query()->create([
                'organization_id' => $organization->id,
                'type' => CourtEventType::Meeting,
                'title' => mb_substr($event['title'], 0, 255),
                'court_name' => $event['location'],
                'starts_at' => $event['starts_at'],
                'source' => 'external',
                'external_uid' => $event['uid'],
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * @return list<string>
     */
    private function eventLines(CourtEvent $event): array
    {
        $uid = $event->external_uid ?: 'ured-'.$event->id.'@superskylaw';
        $end = $event->ends_at ?? $event->starts_at->copy()->addHour();

        return [
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.$event->updated_at->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$event->starts_at->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$end->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escape($event->title),
            'LOCATION:'.$this->escape((string) $event->court_name),
            'DESCRIPTION:'.$this->escape(trim((string) ($event->matter?->internal_number ?? '').' '.$event->type->label())),
            'END:VEVENT',
        ];
    }

    private function field(string $block, string $name): ?string
    {
        if (! preg_match('/^'.$name.'(?:;[^:]*)?:(.+)$/mi', $block, $match)) {
            return null;
        }

        return trim(str_replace(['\\n', '\\,', '\\;'], ["\n", ',', ';'], $match[1]));
    }

    private function moment(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = preg_replace('/^TZID=[^:]+:/', '', $value) ?? $value;
        foreach (['Ymd\THis\Z', 'Ymd\THis', 'Ymd'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value, str_ends_with($value, 'Z') ? 'UTC' : config('app.timezone'));
                if ($parsed instanceof Carbon) {
                    return $parsed;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function escape(string $value): string
    {
        return str_replace(["\r", "\n", ',', ';'], ['', '\n', '\,', '\;'], $value);
    }
}
