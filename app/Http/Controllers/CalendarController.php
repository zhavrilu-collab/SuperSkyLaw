<?php

namespace App\Http\Controllers;

use App\Enums\CourtEventType;
use App\Enums\FeeAudience;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\CourtEvent;
use App\Models\Matter;
use App\Models\MatterNote;
use App\Models\MatterStage;
use App\Models\OfficeTask;
use App\Models\OrganizationUser;
use App\Models\TimelineEntry;
use App\Models\TariffAction;
use App\Models\TariffCharge;
use App\Services\DeadlineCalculator;
use App\Services\PlanFeatureService;
use App\Services\TariffCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class CalendarController extends Controller
{
    use ResolvesOffice;

    public function index(string $slug): View
    {
        $this->authorizePerm('calendar.view');

        $visibleMatterIds = Matter::query()->visibleTo($this->membership())->pluck('id');
        $events = CourtEvent::query()
            ->with(['matter.parties.party', 'responsible', 'stage'])
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (CourtEvent $event) => $event->matter_id === null || $visibleMatterIds->contains($event->matter_id))
            ->values();
        $tasks = OfficeTask::query()
            ->with(['assignee', 'matter', 'stage'])
            ->where(function ($query) use ($visibleMatterIds) {
                $query->whereNull('matter_id')->orWhereIn('matter_id', $visibleMatterIds);
            })
            ->orderBy('due_at')
            ->get();
        $records = TimelineEntry::query()
            ->with(['user', 'matter', 'stage'])
            ->whereIn('matter_id', $visibleMatterIds)
            ->orderBy('occurred_at')
            ->get();
        $notes = MatterNote::query()
            ->with(['user', 'matter', 'stage'])
            ->whereNotNull('noted_on')
            ->whereIn('matter_id', $visibleMatterIds)
            ->orderBy('noted_on')
            ->get();

        return view('organization.calendar.index', [
            'events' => $events,
            'calendarBoard' => [
                'events' => $events->map(fn (CourtEvent $event) => $this->calendarPayload($event))->values(),
                'tasks' => $tasks->map(fn (OfficeTask $task) => $this->taskPayload($task))->values(),
                'records' => $records->map(fn (TimelineEntry $entry) => $this->recordPayload($entry))->values(),
                'notes' => $notes->map(fn (MatterNote $note) => $this->notePayload($note))->values(),
            ],
            'matters' => Matter::query()->visibleTo($this->membership())->orderBy('internal_number')->get(),
            'stages' => MatterStage::query()->whereIn('matter_id', $visibleMatterIds)->orderBy('name')->get(['id', 'matter_id', 'name']),
            'members' => OrganizationUser::query()->with('user')->where('organization_id', $this->office()->id)->get(),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('calendar.manage');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CourtEventType::class)],
            'matter_id' => ['nullable', 'integer'],
            'stage_id' => ['nullable', 'integer'],
            'court_name' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required_without:term_days', 'nullable', 'date'],
            'receipt_on' => ['required_with:term_days', 'nullable', 'date'],
            'term_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'responsible_user_id' => ['nullable', 'integer'],
            'is_preclusive' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'e_oglasna_url' => ['nullable', 'url', 'max:500'],
        ]);

        $matterId = null;
        if (! empty($data['matter_id'])) {
            $matterId = $this->findVisibleMatter((int) $data['matter_id'])->id;
        }
        $stageId = null;
        if (! empty($data['stage_id'])) {
            if ($matterId === null) {
                throw ValidationException::withMessages(['stage_id' => 'Stadij traži predmet.']);
            }
            $stageId = MatterStage::query()->where('matter_id', $matterId)->find($data['stage_id'])?->id;
            if ($stageId === null) {
                throw ValidationException::withMessages(['stage_id' => 'Stadij ne pripada ovom predmetu.']);
            }
        }

        $startsAt = $data['starts_at'] ?? null;
        $origin = null;
        $receiptOn = null;
        if (! empty($data['term_days'])) {
            $due = app(DeadlineCalculator::class)->due(Carbon::parse($data['receipt_on']), (int) $data['term_days'], 'days');
            $startsAt = app(DeadlineCalculator::class)->expiresAt($due);
            $origin = 'court_ordered';
            $receiptOn = $data['receipt_on'];
        }

        CourtEvent::query()->create([
            'matter_id' => $matterId,
            'stage_id' => $stageId,
            'type' => $data['type'],
            'title' => $data['title'],
            'court_name' => $data['court_name'] ?? null,
            'starts_at' => $startsAt,
            'responsible_user_id' => $data['responsible_user_id'] ?? auth()->id(),
            'is_preclusive' => $request->boolean('is_preclusive'),
            'origin' => $origin,
            'receipt_on' => $receiptOn,
            'notes' => $data['notes'] ?? null,
            'e_oglasna_url' => $data['e_oglasna_url'] ?? null,
        ]);

        return back()->with('status', 'Termin je upisan u kalendar.');
    }

    public function complete(string $slug, int $event): RedirectResponse
    {
        $this->authorizePerm('calendar.manage');
        $model = CourtEvent::query()->findOrFail($event);
        if ($model->matter_id !== null) {
            $this->findVisibleMatter($model->matter_id);
        }
        $model->forceFill(['completed_at' => now()])->save();

        $canOfferFee = $model->type === CourtEventType::Hearing
            && $model->matter_id !== null
            && app(PlanFeatureService::class)->allows($this->office(), 'tariff_hok')
            && ! TariffCharge::query()->where('court_event_id', $model->id)->exists();

        if ($canOfferFee) {
            return back()->with('status', 'Ročište je označeno kao obavljeno.')->with('hearing_offer', $model->id);
        }

        return back()->with('status', 'Termin je označen kao obavljen.');
    }

    public function charge(Request $request, string $slug, int $event): RedirectResponse
    {
        $this->authorizePerm('finance.manage');
        $this->authorizeFeature('tariff_hok');
        $model = CourtEvent::query()->findOrFail($event);
        if ($model->type !== CourtEventType::Hearing || $model->matter_id === null || $model->completed_at === null) {
            return back()->withErrors(['hearing' => 'Nagrada se nudi samo za obavljeno ročište na predmetu.']);
        }

        $matter = $this->findVisibleMatter($model->matter_id);
        if (TariffCharge::query()->where('court_event_id', $model->id)->exists()) {
            return back()->with('status', 'Nagrada za ovo ročište je već upisana.');
        }

        $data = $request->validate([
            'tariff_action' => ['required', Rule::in(['rociste', 'rociste_procesno'])],
            'audience' => ['required', Rule::enum(FeeAudience::class)],
        ]);

        $action = TariffAction::query()->where('code', $data['tariff_action'])->firstOrFail();

        try {
            $quote = app(TariffCatalog::class)->quote($action, $matter->dispute_value_cents);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['hearing' => $exception->getMessage()]);
        }

        $date = $model->starts_at->timezone(config('app.timezone'))->format('d.m.Y.');
        TariffCharge::query()->create([
            'matter_id' => $matter->id,
            'tariff_version_id' => $action->tariff_version_id,
            'tariff_action_id' => $action->id,
            'audience' => $data['audience'],
            'description' => 'Zastupanje na ročištu dana '.$date.' — '.$quote['points'].' bodova.',
            'points' => $quote['points'],
            'amount_cents' => $quote['amount_cents'],
            'court_event_id' => $model->id,
            'created_by_user_id' => auth()->id(),
        ]);

        return back()->with('status', 'Nagrada za ročište je upisana: '.$quote['points'].' bodova.');
    }

    /**
     * @return array<string, mixed>
     */
    private function calendarPayload(CourtEvent $event): array
    {
        $starts = $event->starts_at->timezone(config('app.timezone'));
        $ends = ($event->ends_at ?? $event->starts_at)->timezone(config('app.timezone'));
        $group = match (true) {
            $event->type->isDeadline() => 'deadline',
            in_array($event->type, [CourtEventType::Hearing, CourtEventType::Meeting, CourtEventType::Inspection], true) => 'hearing',
            default => 'other',
        };

        return [
            'id' => $event->id,
            'kind' => 'event',
            'title' => $event->title,
            'type' => $event->type->label(),
            'typeKey' => $event->type->value,
            'group' => $group,
            'date' => $starts->toDateString(),
            'endDate' => $ends->toDateString(),
            'time' => $starts->format('H:i'),
            'matter' => $event->matter?->internal_number,
            'matterTitle' => $event->matter?->title,
            'matterUrl' => $this->matterUrl($event->matter),
            'stage' => $event->stage?->name,
            'court' => $event->court_name,
            'responsible' => $event->responsible?->name,
            'notes' => $event->notes,
            'oglasna' => $event->e_oglasna_url,
            'preclusive' => (bool) $event->is_preclusive,
            'completed' => $event->completed_at !== null,
            'parties' => $event->matter?->parties->map(fn ($link) => [
                'id' => $link->party_id,
                'name' => $link->party->name.($link->party->sms_consent_at ? '' : ' (nema privole)'),
            ])->values() ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function taskPayload(OfficeTask $task): array
    {
        $due = $task->due_at?->timezone(config('app.timezone'));

        return [
            'id' => $task->id,
            'kind' => 'task',
            'title' => $task->title,
            'type' => 'Zadatak',
            'typeKey' => 'task',
            'group' => 'task',
            'date' => $due?->toDateString(),
            'endDate' => $due?->toDateString(),
            'time' => $due ? $due->format('H:i') : '',
            'body' => $task->body,
            'matter' => $task->matter?->internal_number,
            'matterTitle' => $task->matter?->title,
            'matterUrl' => $this->matterUrl($task->matter),
            'stage' => $task->stage?->name,
            'responsible' => $task->assignee?->name,
            'completed' => $task->completed_at !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recordPayload(TimelineEntry $entry): array
    {
        $at = $entry->occurred_at->timezone(config('app.timezone'));

        return [
            'id' => $entry->id,
            'kind' => 'record',
            'title' => $entry->type->label(),
            'type' => $entry->type->label(),
            'body' => $entry->body,
            'date' => $at->toDateString(),
            'time' => $at->format('H:i'),
            'matter' => $entry->matter?->internal_number,
            'matterTitle' => $entry->matter?->title,
            'matterUrl' => $this->matterUrl($entry->matter),
            'stage' => $entry->stage?->name,
            'responsible' => $entry->user?->name,
            'client' => (bool) $entry->visible_to_client,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function notePayload(MatterNote $note): array
    {
        $on = $note->noted_on;

        return [
            'id' => $note->id,
            'kind' => 'note',
            'title' => 'Bilješka',
            'type' => 'Bilješka',
            'body' => $note->body,
            'date' => $on?->toDateString(),
            'time' => '',
            'matter' => $note->matter?->internal_number,
            'matterTitle' => $note->matter?->title,
            'matterUrl' => $this->matterUrl($note->matter),
            'stage' => $note->stage?->name,
            'responsible' => $note->user?->name,
        ];
    }

    private function matterUrl(?Matter $matter): ?string
    {
        if ($matter === null) {
            return null;
        }

        return route('organization.matters.show', [$this->office()->slug, $matter->id]);
    }
}
