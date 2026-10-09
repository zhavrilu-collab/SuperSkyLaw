<?php

namespace App\Http\Controllers;

use App\Enums\TimeEntryStatus;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\Matter;
use App\Models\TimeEntry;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimeEntryController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly PlanFeatureService $plans) {}

    public function index(string $slug): View
    {
        $this->authorizePerm('time.view');

        $entries = TimeEntry::query()
            ->with(['matter', 'user'])
            ->whereIn('matter_id', Matter::query()->visibleTo($this->membership())->select('id'))
            ->orderByDesc('id')
            ->get();

        $running = TimeEntry::query()
            ->where('user_id', auth()->id())
            ->whereNull('ended_at')
            ->whereNotNull('started_at')
            ->first();

        return view('organization.time.index', [
            'entries' => $entries,
            'running' => $running,
            'matters' => Matter::query()->visibleTo($this->membership())->orderBy('internal_number')->get(),
            'wipCents' => (int) $entries
                ->filter(fn (TimeEntry $entry) => $entry->status === TimeEntryStatus::Approved && $entry->invoice_id === null && ! $entry->isRunning())
                ->sum(fn (TimeEntry $entry) => $entry->valueCents()),
            'draftCents' => (int) $entries
                ->filter(fn (TimeEntry $entry) => $entry->status === TimeEntryStatus::Draft && ! $entry->isRunning())
                ->sum(fn (TimeEntry $entry) => $entry->valueCents()),
            'requiresApproval' => $this->plans->allows($this->office(), 'time_approval'),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('time.manage');
        $data = $request->validate([
            'matter_id' => ['required', 'integer'],
            'description' => ['required', 'string', 'max:500'],
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $matter = $this->findVisibleMatter((int) $data['matter_id']);

        TimeEntry::query()->create([
            'matter_id' => $matter->id,
            'user_id' => auth()->id(),
            'description' => $data['description'],
            'minutes' => $data['minutes'],
            'hourly_rate_cents' => $matter->hourly_rate_cents,
            'status' => $this->initialStatus(),
        ]);

        return $this->redirectToMatterLedger($request, $matter->id, 'Radnja je unesena.');
    }

    public function start(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('time.manage');
        $data = $request->validate([
            'matter_id' => ['required', 'integer'],
            'description' => ['required', 'string', 'max:500'],
        ]);

        $existing = TimeEntry::query()
            ->where('user_id', auth()->id())
            ->whereNull('ended_at')
            ->whereNotNull('started_at')
            ->exists();

        if ($existing) {
            return back()->withErrors(['description' => 'Štoperica je već pokrenuta.']);
        }

        $matter = $this->findVisibleMatter((int) $data['matter_id']);

        TimeEntry::query()->create([
            'matter_id' => $matter->id,
            'user_id' => auth()->id(),
            'description' => $data['description'],
            'started_at' => now(),
            'minutes' => 0,
            'hourly_rate_cents' => $matter->hourly_rate_cents,
            'status' => $this->initialStatus(),
        ]);

        return $this->redirectToMatterLedger($request, $matter->id, 'Štoperica je pokrenuta.');
    }

    public function stop(Request $request, string $slug, int $entry): RedirectResponse
    {
        $this->authorizePerm('time.manage');
        $model = TimeEntry::query()->where('user_id', auth()->id())->findOrFail($entry);

        if (! $model->isRunning()) {
            return back();
        }

        $minutes = max(1, (int) abs($model->started_at->diffInMinutes(now())));
        $model->forceFill([
            'ended_at' => now(),
            'minutes' => $minutes,
        ])->save();

        return $this->redirectToMatterLedger($request, $model->matter_id, 'Štoperica je zaustavljena ('.$minutes.' min).');
    }

    public function approve(Request $request, string $slug, int $entry): RedirectResponse
    {
        return $this->changeStatus($request, $entry, TimeEntryStatus::Approved, 'Sati su odobreni za fakturiranje.');
    }

    public function writeOff(Request $request, string $slug, int $entry): RedirectResponse
    {
        return $this->changeStatus($request, $entry, TimeEntryStatus::WrittenOff, 'Sati su otpisani i ostaju vidljivi.');
    }

    public function nonBillable(Request $request, string $slug, int $entry): RedirectResponse
    {
        return $this->changeStatus($request, $entry, TimeEntryStatus::NonBillable, 'Sati su označeni kao nenaplativi.');
    }

    private function initialStatus(): TimeEntryStatus
    {
        return $this->plans->allows($this->office(), 'time_approval')
            ? TimeEntryStatus::Draft
            : TimeEntryStatus::Approved;
    }

    private function changeStatus(Request $request, int $entry, TimeEntryStatus $status, string $message): RedirectResponse
    {
        $this->authorizePerm('time.approve');
        $this->authorizeFeature('time_approval');
        $model = TimeEntry::query()->findOrFail($entry);

        if ($model->isRunning() || $model->invoice_id !== null) {
            return back()->withErrors(['status' => 'Ovu stavku nije moguće promijeniti.']);
        }

        $model->forceFill(['status' => $status])->save();

        return $this->redirectToMatterLedger($request, $model->matter_id, $message);
    }
}
