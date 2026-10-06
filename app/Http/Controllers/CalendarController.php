<?php

namespace App\Http\Controllers;

use App\Enums\CourtEventType;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\CourtEvent;
use App\Models\Matter;
use App\Models\OrganizationUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CalendarController extends Controller
{
    use ResolvesOffice;

    public function index(Request $request, string $slug): View
    {
        $this->authorizePerm('calendar.view');
        $filter = $request->query('filter', 'all');

        $events = CourtEvent::query()
            ->with(['matter.parties.party', 'responsible'])
            ->when($filter === 'hearings', fn ($query) => $query->whereIn('type', [
                CourtEventType::Hearing, CourtEventType::Meeting, CourtEventType::Inspection,
            ]))
            ->when($filter === 'deadlines', fn ($query) => $query->whereIn('type', [
                CourtEventType::AppealDeadline, CourtEventType::ObjectionDeadline, CourtEventType::Limitation,
            ]))
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (CourtEvent $event) => $event->matter_id === null || Matter::query()->visibleTo($this->membership())->whereKey($event->matter_id)->exists());

        return view('organization.calendar.index', [
            'events' => $events,
            'filter' => $filter,
            'matters' => Matter::query()->visibleTo($this->membership())->orderBy('internal_number')->get(),
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
            'court_name' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'responsible_user_id' => ['nullable', 'integer'],
            'is_preclusive' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'e_oglasna_url' => ['nullable', 'url', 'max:500'],
        ]);

        if (! empty($data['matter_id'])) {
            $this->findVisibleMatter((int) $data['matter_id']);
        }

        CourtEvent::query()->create([
            'matter_id' => $data['matter_id'] ?? null,
            'type' => $data['type'],
            'title' => $data['title'],
            'court_name' => $data['court_name'] ?? null,
            'starts_at' => $data['starts_at'],
            'responsible_user_id' => $data['responsible_user_id'] ?? auth()->id(),
            'is_preclusive' => $request->boolean('is_preclusive'),
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

        return back()->with('status', 'Termin je označen kao obavljen.');
    }
}
