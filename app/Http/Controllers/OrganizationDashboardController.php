<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\CourtEvent;
use App\Models\Invoice;
use App\Models\Matter;
use App\Models\OfficeNotification;
use App\Services\OrganizationRbacService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrganizationDashboardController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly OrganizationRbacService $rbac) {}

    public function __invoke(string $slug): View
    {
        $organization = $this->office();
        $membership = $this->membership();
        $userId = (int) Auth::id();
        $visibleIds = Matter::query()->visibleTo($membership)->pluck('id');

        $events = CourtEvent::query()
            ->with('matter')
            ->where(function ($query) use ($visibleIds) {
                $query->whereNull('matter_id')->orWhereIn('matter_id', $visibleIds);
            })
            ->whereNull('completed_at')
            ->where('starts_at', '>=', now()->startOfDay())
            ->where('starts_at', '<=', now()->endOfWeek())
            ->orderBy('starts_at')
            ->get();

        $overdue = CourtEvent::query()
            ->where(function ($query) use ($visibleIds) {
                $query->whereNull('matter_id')->orWhereIn('matter_id', $visibleIds);
            })
            ->whereNull('completed_at')
            ->where('starts_at', '<', now())
            ->orderBy('starts_at')
            ->limit(8)
            ->get();

        $invoices = Invoice::query()
            ->whereBetween('issue_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->get();

        return view('organization.dashboard', [
            'organization' => $organization,
            'membership' => $membership,
            'canManageTeam' => $this->rbac->can($organization->id, $userId, 'team.manage'),
            'canFinance' => $this->rbac->can($organization->id, $userId, 'finance.view'),
            'upcoming' => $events,
            'overdue' => $overdue,
            'unread' => OfficeNotification::query()->where('user_id', $userId)->whereNull('read_at')->count(),
            'invoiced' => $invoices->sum('total_cents'),
            'collected' => $invoices->sum('paid_cents'),
            'outstanding' => $invoices
                ->reject(fn (Invoice $invoice) => $invoice->status === InvoiceStatus::Paid)
                ->sum(fn (Invoice $invoice) => max(0, $invoice->total_cents - $invoice->paid_cents)),
        ]);
    }
}
