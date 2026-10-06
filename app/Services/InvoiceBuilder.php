<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\TimeEntryStatus;
use App\Enums\FeeAudience;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Matter;
use App\Models\Organization;
use App\Models\Party;
use App\Models\TariffCharge;
use App\Models\TimeEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceBuilder
{
    /**
     * @param  list<int>  $timeEntryIds
     * @param  list<int>  $expenseIds
     * @param  list<int>  $tariffChargeIds
     */
    public function create(
        Organization $organization,
        Matter $matter,
        Party $buyer,
        array $timeEntryIds,
        array $expenseIds,
        int $vatRate = 25,
        array $tariffChargeIds = [],
    ): Invoice {
        return DB::transaction(function () use ($organization, $matter, $buyer, $timeEntryIds, $expenseIds, $vatRate, $tariffChargeIds) {
            $entries = TimeEntry::query()
                ->where('matter_id', $matter->id)
                ->whereIn('id', $timeEntryIds)
                ->where('status', TimeEntryStatus::Approved)
                ->whereNull('invoice_id')
                ->get();

            $expenses = Expense::query()
                ->where('matter_id', $matter->id)
                ->where('bill_to', FeeAudience::Client->value)
                ->whereIn('id', $expenseIds)
                ->whereNull('invoice_id')
                ->get();

            $charges = TariffCharge::query()
                ->where('matter_id', $matter->id)
                ->where('audience', FeeAudience::Client)
                ->whereIn('id', $tariffChargeIds)
                ->whereNull('invoice_id')
                ->get();

            if ($entries->isEmpty() && $expenses->isEmpty() && $charges->isEmpty()) {
                throw ValidationException::withMessages([
                    'time_entry_ids' => 'Nema odobrenih sati ni troškova za račun.',
                ]);
            }

            $year = now()->year;
            $seq = Invoice::query()->where('number', 'like', $year.'-%')->count() + 1;

            $invoice = Invoice::query()->create([
                'matter_id' => $matter->id,
                'party_id' => $buyer->id,
                'number' => sprintf('%d-%03d', $year, $seq),
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(15)->toDateString(),
                'status' => InvoiceStatus::Unpaid,
                'vat_rate' => $vatRate,
                'buyer_name' => $buyer->name,
                'buyer_oib' => $buyer->oib,
                'buyer_address' => trim(($buyer->address ?? '').' '.($buyer->city ?? '')),
            ]);

            $subtotal = 0;

            foreach ($entries as $entry) {
                $rate = $entry->hourly_rate_cents ?? $matter->hourly_rate_cents ?? 0;
                $line = (int) round($entry->minutes / 60 * $rate);
                $subtotal += $line;

                InvoiceLine::query()->create([
                    'invoice_id' => $invoice->id,
                    'description' => $entry->description,
                    'quantity' => round($entry->minutes / 60, 2),
                    'unit_price_cents' => $rate,
                    'line_total_cents' => $line,
                    'vat_rate' => $vatRate,
                    'time_entry_id' => $entry->id,
                ]);

                $entry->forceFill(['invoice_id' => $invoice->id])->save();
            }

            foreach ($expenses as $expense) {
                $subtotal += $expense->amount_cents;

                InvoiceLine::query()->create([
                    'invoice_id' => $invoice->id,
                    'description' => $expense->description,
                    'quantity' => 1,
                    'unit_price_cents' => $expense->amount_cents,
                    'line_total_cents' => $expense->amount_cents,
                    'vat_rate' => $vatRate,
                    'expense_id' => $expense->id,
                ]);

                $expense->forceFill(['invoice_id' => $invoice->id])->save();
            }

            foreach ($charges as $charge) {
                $subtotal += $charge->amount_cents;

                InvoiceLine::query()->create([
                    'invoice_id' => $invoice->id,
                    'description' => $charge->description.' ('.$charge->points.' bodova)',
                    'quantity' => 1,
                    'unit_price_cents' => $charge->amount_cents,
                    'line_total_cents' => $charge->amount_cents,
                    'vat_rate' => $vatRate,
                    'tariff_charge_id' => $charge->id,
                ]);

                $charge->forceFill(['invoice_id' => $invoice->id])->save();
            }

            $vat = (int) round($subtotal * $vatRate / 100);
            $invoice->forceFill([
                'subtotal_cents' => $subtotal,
                'vat_cents' => $vat,
                'total_cents' => $subtotal + $vat,
            ])->save();

            return $invoice->fresh(['lines', 'matter', 'party']);
        });
    }
}
