<?php

namespace App\Services;

use App\Enums\TimelineEntryType;
use App\Models\Matter;
use App\Models\Organization;
use App\Models\TimelineEntry;
use Illuminate\Validation\ValidationException;

class MailIntakeService
{
    public function file(Organization $organization, string $from, string $subject, string $body): TimelineEntry
    {
        if (! preg_match('/\[(\d{4}\/\d{1,6})\]/', $subject.' '.$body, $match)) {
            throw ValidationException::withMessages([
                'subject' => 'U predmetu poruke mora biti interni broj, npr. [2026/001].',
            ]);
        }

        $matter = Matter::query()
            ->where('organization_id', $organization->id)
            ->where('internal_number', $match[1])
            ->first();

        if ($matter === null) {
            throw ValidationException::withMessages([
                'subject' => 'Predmet '.$match[1].' nije pronađen u ovom uredu.',
            ]);
        }

        return TimelineEntry::query()->create([
            'organization_id' => $organization->id,
            'matter_id' => $matter->id,
            'type' => TimelineEntryType::Email,
            'body' => trim($from.' — '.$subject."\n\n".$body),
            'occurred_at' => now(),
            'visible_to_client' => false,
        ]);
    }
}
