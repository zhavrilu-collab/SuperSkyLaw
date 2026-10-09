<?php

namespace App\Services;

use App\Models\CourtEvent;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Matter;
use App\Models\MatterDocument;
use App\Models\MatterNote;
use App\Models\MatterStage;
use App\Models\Payment;
use App\Models\TariffCharge;
use App\Models\TimeEntry;
use App\Models\TimelineEntry;
use App\Models\TrustMovement;
use Illuminate\Support\Collection;

class MatterActivityFeed
{
    /**
     * @return Collection<int, MatterActivity>
     */
    public function for(Matter $matter, bool $includeMoney): Collection
    {
        $items = collect();

        TimelineEntry::query()->with('user')->where('matter_id', $matter->id)->get()->each(function (TimelineEntry $entry) use ($items): void {
            $kind = $entry->type->label().($entry->visible_to_client ? ' · vidi klijent' : '');
            $this->push($items, $entry->occurred_at, $kind, $entry->body, $entry->user?->name);
        });

        CourtEvent::query()->with('responsible')->where('matter_id', $matter->id)->get()->each(function (CourtEvent $event) use ($items): void {
            $text = $event->title;
            if ($event->court_name) {
                $text .= ' · '.$event->court_name;
            }
            $this->push($items, $event->starts_at, $event->type->label(), $text, $event->responsible?->name);
        });

        MatterDocument::query()->with('uploader')->where('matter_id', $matter->id)->get()->each(function (MatterDocument $document) use ($items): void {
            $this->push($items, $document->created_at, 'Dokument', $document->original_name, $document->uploader?->name);
        });

        MatterNote::query()->with('user')->where('matter_id', $matter->id)->get()->each(function (MatterNote $note) use ($items): void {
            $this->push($items, $note->created_at, 'Bilješka', $note->body, $note->user?->name);
        });

        MatterStage::query()->where('matter_id', $matter->id)->get()->each(function (MatterStage $stage) use ($items): void {
            $this->push($items, $stage->started_on, 'Stadij', $stage->name.($stage->body ? ' · '.$stage->body : ''));
        });

        TimeEntry::query()->with('user')->where('matter_id', $matter->id)->get()->each(function (TimeEntry $entry) use ($items): void {
            $this->push($items, $entry->started_at ?? $entry->created_at, 'Sati', $entry->minutes.' min · '.$entry->description, $entry->user?->name);
        });

        Expense::query()->where('matter_id', $matter->id)->get()->each(function (Expense $expense) use ($items, $includeMoney): void {
            $text = $expense->description.($includeMoney ? ' · '.$this->euros($expense->amount_cents) : '');
            $this->push($items, $expense->created_at, 'Trošak', $text);
        });

        TariffCharge::query()->where('matter_id', $matter->id)->get()->each(function (TariffCharge $charge) use ($items, $includeMoney): void {
            $text = $charge->description.($includeMoney ? ' · '.$this->euros($charge->amount_cents) : '');
            $this->push($items, $charge->created_at, 'Tarifa', $text);
        });

        Invoice::query()->with('payments')->where('matter_id', $matter->id)->get()->each(function (Invoice $invoice) use ($items, $includeMoney): void {
            $text = $invoice->number.' · '.$invoice->status->label();
            if ($includeMoney) {
                $text = $invoice->number.' · '.$this->euros($invoice->total_cents).' · '.$invoice->status->label();
            }
            $this->push($items, $invoice->issue_date, 'Račun', $text);
            $invoice->payments->each(function (Payment $payment) use ($items, $invoice, $includeMoney): void {
                $text = $invoice->number.($includeMoney ? ' · '.$this->euros($payment->amount_cents) : '');
                $this->push($items, $payment->paid_on, 'Uplata', $text);
            });
        });

        TrustMovement::query()->where('matter_id', $matter->id)->get()->each(function (TrustMovement $movement) use ($items, $includeMoney): void {
            $text = $movement->direction->label().' · '.$movement->purpose;
            if ($includeMoney) {
                $text .= ' · '.$this->euros($movement->amount_cents);
            }
            $this->push($items, $movement->occurred_on, 'Depozit', $text);
        });

        return $items->sortByDesc(fn (MatterActivity $item) => $item->at->getTimestamp())->values();
    }

    /**
     * @param  Collection<int, MatterActivity>  $items
     */
    private function push(Collection $items, mixed $at, string $kind, string $text, ?string $who = null): void
    {
        if ($at === null) {
            return;
        }

        $items->push(new MatterActivity($at, $kind, $text, $who));
    }

    private function euros(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').' EUR';
    }
}
