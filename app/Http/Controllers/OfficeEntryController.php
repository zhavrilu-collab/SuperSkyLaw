<?php

namespace App\Http\Controllers;

use App\Enums\CourtEventType;
use App\Enums\TimelineEntryType;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\CourtEvent;
use App\Models\MatterNote;
use App\Models\MatterStage;
use App\Models\OfficeTask;
use App\Models\OrganizationUser;
use App\Models\TimelineEntry;
use App\Services\DeadlineCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OfficeEntryController extends Controller
{
    use ResolvesOffice;

    public function store(Request $request, string $slug): RedirectResponse
    {
        $kind = $request->validate([
            'kind' => ['required', Rule::in(['termin', 'zadatak', 'zapis', 'biljeska'])],
        ])['kind'];

        return match ($kind) {
            'termin' => $this->storeTermin($request),
            'zadatak' => $this->storeZadatak($request),
            'zapis' => $this->storeZapis($request),
            'biljeska' => $this->storeBiljeska($request),
        };
    }

    public function completeTask(string $slug, int $task): RedirectResponse
    {
        $this->authorizePerm('calendar.manage');
        $model = OfficeTask::query()->findOrFail($task);
        if ($model->matter_id !== null) {
            $this->findVisibleMatter($model->matter_id);
        }
        $model->forceFill(['completed_at' => now()])->save();

        return back()->with('status', 'Zadatak je označen kao obavljen.');
    }

    private function storeTermin(Request $request): RedirectResponse
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
            'responsible_user_id' => ['nullable', 'integer', $this->memberRule()],
            'is_preclusive' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'e_oglasna_url' => ['nullable', 'url', 'max:500'],
        ]);

        $matterId = $this->matterId($data['matter_id'] ?? null);
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
            'stage_id' => $this->stageId($data['stage_id'] ?? null, $matterId),
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

    private function storeZadatak(Request $request): RedirectResponse
    {
        $this->authorizePerm('calendar.manage');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
            'matter_id' => ['nullable', 'integer'],
            'stage_id' => ['nullable', 'integer'],
            'user_id' => ['required', 'integer', $this->memberRule()],
        ]);

        $matterId = $this->matterId($data['matter_id'] ?? null);
        OfficeTask::query()->create([
            'matter_id' => $matterId,
            'stage_id' => $this->stageId($data['stage_id'] ?? null, $matterId),
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'due_at' => $data['due_at'] ?? null,
        ]);

        return back()->with('status', 'Zadatak je upisan.');
    }

    private function storeZapis(Request $request): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $data = $request->validate([
            'matter_id' => ['required', 'integer'],
            'stage_id' => ['nullable', 'integer'],
            'type' => ['required', Rule::enum(TimelineEntryType::class)],
            'body' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date'],
            'visible_to_client' => ['nullable', 'boolean'],
        ]);

        $matterId = $this->matterId($data['matter_id']);
        TimelineEntry::query()->create([
            'matter_id' => $matterId,
            'stage_id' => $this->stageId($data['stage_id'] ?? null, $matterId),
            'type' => $data['type'],
            'body' => $data['body'],
            'occurred_at' => $data['occurred_at'],
            'user_id' => auth()->id(),
            'visible_to_client' => $request->boolean('visible_to_client'),
        ]);

        return back()->with('status', 'Zapis je dodan.');
    }

    private function storeBiljeska(Request $request): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $data = $request->validate([
            'matter_id' => ['required', 'integer'],
            'stage_id' => ['nullable', 'integer'],
            'body' => ['required', 'string', 'max:5000'],
            'noted_on' => ['nullable', 'date'],
        ]);

        $matterId = $this->matterId($data['matter_id']);
        MatterNote::query()->create([
            'matter_id' => $matterId,
            'stage_id' => $this->stageId($data['stage_id'] ?? null, $matterId),
            'user_id' => auth()->id(),
            'body' => $data['body'],
            'noted_on' => $data['noted_on'] ?? null,
        ]);

        return back()->with('status', 'Bilješka je spremljena.');
    }

    private function matterId(mixed $matterId): ?int
    {
        if ($matterId === null || $matterId === '') {
            return null;
        }

        return $this->findVisibleMatter((int) $matterId)->id;
    }

    private function stageId(mixed $stageId, ?int $matterId): ?int
    {
        if ($stageId === null || $stageId === '') {
            return null;
        }

        if ($matterId === null) {
            throw ValidationException::withMessages(['stage_id' => 'Stadij traži predmet.']);
        }

        $stage = MatterStage::query()->where('matter_id', $matterId)->find($stageId);
        if ($stage === null) {
            throw ValidationException::withMessages(['stage_id' => 'Stadij ne pripada ovom predmetu.']);
        }

        return $stage->id;
    }

    private function memberRule(): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists(OrganizationUser::class, 'user_id')->where(
            fn ($query) => $query->where('organization_id', $this->office()->id)
        );
    }
}
