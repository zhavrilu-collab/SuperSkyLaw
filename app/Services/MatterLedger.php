<?php

namespace App\Services;

use App\Enums\FeeAudience;
use App\Enums\TimeEntryStatus;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Matter;
use App\Models\TariffCharge;
use App\Models\TimeEntry;
use App\Models\TrustMovement;
use Illuminate\Support\Collection;

class MatterLedger
{
    public function __construct(private readonly TrustLedger $trust) {}

    /**
     * @return array{
     *     times: Collection<int, TimeEntry>,
     *     expenses: Collection<int, Expense>,
     *     tariffs: Collection<int, TariffCharge>,
     *     invoices: Collection<int, Invoice>,
     *     trustMovements: Collection<int, TrustMovement>,
     *     unbilledTimeCents: int,
     *     pendingTimeCents: int,
     *     unbilledExpenseCents: int,
     *     unbilledTariffCents: int,
     *     trustBalanceCents: int
     * }
     */
    public function summarize(Matter $matter): array
    {
        $times = TimeEntry::query()->with('user')->where('matter_id', $matter->id)->orderByDesc('started_at')->get();
        $expenses = Expense::query()->where('matter_id', $matter->id)->orderByDesc('id')->get();
        $tariffs = TariffCharge::query()->where('matter_id', $matter->id)->orderByDesc('id')->get();
        $invoices = Invoice::query()->with('payments')->where('matter_id', $matter->id)->orderByDesc('issue_date')->get();
        $trustMovements = TrustMovement::query()->where('matter_id', $matter->id)->orderByDesc('occurred_on')->get();

        $unbilledTime = $times->filter(fn (TimeEntry $entry) => $entry->invoice_id === null && $entry->status === TimeEntryStatus::Approved);
        $pendingTime = $times->filter(fn (TimeEntry $entry) => $entry->invoice_id === null && $entry->status === TimeEntryStatus::Draft);
        $unbilledExpenses = $expenses->filter(fn (Expense $expense) => $expense->invoice_id === null && $expense->bill_to === FeeAudience::Client->value);
        $unbilledTariffs = $tariffs->filter(fn (TariffCharge $charge) => $charge->invoice_id === null && $charge->audience === FeeAudience::Client);

        return [
            'times' => $times,
            'expenses' => $expenses,
            'tariffs' => $tariffs,
            'invoices' => $invoices,
            'trustMovements' => $trustMovements,
            'unbilledTimeCents' => (int) $unbilledTime->sum(fn (TimeEntry $entry) => $entry->valueCents()),
            'pendingTimeCents' => (int) $pendingTime->sum(fn (TimeEntry $entry) => $entry->valueCents()),
            'unbilledExpenseCents' => (int) $unbilledExpenses->sum('amount_cents'),
            'unbilledTariffCents' => (int) $unbilledTariffs->sum('amount_cents'),
            'trustBalanceCents' => $this->trust->balance($matter->organization_id, $matter->id),
        ];
    }
}
