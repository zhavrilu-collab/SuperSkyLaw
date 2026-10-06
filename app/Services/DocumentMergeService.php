<?php

namespace App\Services;

use App\Enums\PartySide;
use App\Models\DocumentTemplate;
use App\Models\Matter;
use App\Models\Organization;
use App\Models\Party;

class DocumentMergeService
{
    public function render(DocumentTemplate $template, Organization $organization, Matter $matter, Party $party): string
    {
        $opposing = $matter->parties()->with('party')->get()
            ->first(fn ($link) => $link->side === PartySide::Opposing)?->party;

        $value = $matter->dispute_value_cents === null
            ? '—'
            : number_format($matter->dispute_value_cents / 100, 2, ',', '.').' EUR';

        $replacements = [
            '{{ured.naziv}}' => $organization->name,
            '{{ured.oib}}' => $organization->oib ?: '—',
            '{{ured.adresa}}' => $organization->address ?: '—',
            '{{ured.grad}}' => $organization->city ?: '—',
            '{{predmet.naziv}}' => $matter->title,
            '{{predmet.broj}}' => $matter->internal_number,
            '{{predmet.sud}}' => $matter->court_name ?: '—',
            '{{predmet.oznaka}}' => $matter->courtReference(),
            '{{predmet.vrijednost}}' => $value,
            '{{stranka.naziv}}' => $party->name,
            '{{stranka.oib}}' => $party->oib ?: '—',
            '{{stranka.adresa}}' => trim(($party->address ?? '').' '.($party->city ?? '')) ?: '—',
            '{{protustranka.naziv}}' => $opposing?->name ?: '—',
            '{{datum}}' => now()->timezone(config('app.timezone'))->format('d.m.Y.'),
        ];

        return strtr($template->body, $replacements);
    }
}
