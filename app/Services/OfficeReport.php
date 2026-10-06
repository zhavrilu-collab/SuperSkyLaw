<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Enums\TimeEntryStatus;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Payment;
use App\Models\TimeEntry;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class OfficeReport
{
    /**
     * @return array{
     *     month: string,
     *     available_minutes: int,
     *     billable_minutes: int,
     *     utilization: float,
     *     worked_cents: int,
     *     invoiced_time_cents: int,
     *     realization: float,
     *     issued_cents: int,
     *     collected_cents: int,
     *     collection: float,
     *     aging: array<string, int>
     * }
     */
    public function forMonth(Organization $organization, CarbonInterface $month): array
    {
        $start = Carbon::parse($month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $weekdays = $this->weekdays($start, $end);
        $feeEarners = OrganizationUser::query()
            ->where('organization_id', $organization->id)
            ->whereIn('role', [OrganizationRole::Owner, OrganizationRole::Lawyer, OrganizationRole::Trainee])
            ->count();
        $available = $feeEarners * $weekdays * 8 * 60;

        $entries = TimeEntry::query()
            ->where('organization_id', $organization->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $billable = $entries->filter(fn (TimeEntry $entry) => in_array($entry->status, [TimeEntryStatus::Approved, TimeEntryStatus::WrittenOff], true) || $entry->invoice_id !== null);
        $billableMinutes = (int) $entries
            ->filter(fn (TimeEntry $entry) => $entry->status === TimeEntryStatus::Approved || $entry->invoice_id !== null)
            ->sum('minutes');
        $workedCents = (int) $billable->sum(fn (TimeEntry $entry) => $entry->valueCents());
        $invoicedTime = (int) $entries
            ->filter(fn (TimeEntry $entry) => $entry->invoice_id !== null)
            ->sum(fn (TimeEntry $entry) => $entry->valueCents());

        $issued = (int) Invoice::query()
            ->where('organization_id', $organization->id)
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total_cents');
        $collected = (int) Payment::query()
            ->where('organization_id', $organization->id)
            ->whereBetween('paid_on', [$start->toDateString(), $end->toDateString()])
            ->sum('amount_cents');

        return [
            'month' => $start->format('Y-m'),
            'available_minutes' => $available,
            'billable_minutes' => $billableMinutes,
            'utilization' => $available > 0 ? $billableMinutes / $available : 0.0,
            'worked_cents' => $workedCents,
            'invoiced_time_cents' => $invoicedTime,
            'realization' => $workedCents > 0 ? $invoicedTime / $workedCents : 0.0,
            'issued_cents' => $issued,
            'collected_cents' => $collected,
            'collection' => $issued > 0 ? $collected / $issued : 0.0,
            'aging' => $this->aging($organization),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function aging(Organization $organization): array
    {
        $buckets = ['current' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0, 'd90plus' => 0];

        Invoice::query()
            ->where('organization_id', $organization->id)
            ->where('status', '!=', 'paid')
            ->get()
            ->each(function (Invoice $invoice) use (&$buckets): void {
                $open = max(0, $invoice->total_cents - $invoice->paid_cents);
                $days = $invoice->due_date->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);
                $key = match (true) {
                    $days <= 0 => 'current',
                    $days <= 30 => 'd30',
                    $days <= 60 => 'd60',
                    $days <= 90 => 'd90',
                    default => 'd90plus',
                };
                $buckets[$key] += $open;
            });

        return $buckets;
    }

    private function weekdays(CarbonInterface $start, CarbonInterface $end): int
    {
        $days = 0;
        for ($day = $start->copy()->startOfDay(); $day->lte($end); $day->addDay()) {
            if ($day->isWeekday()) {
                $days++;
            }
        }

        return $days;
    }
}
