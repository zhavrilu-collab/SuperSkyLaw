<?php

namespace App\Http\Controllers;

use App\Enums\CourtEventType;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\CourtEvent;
use App\Models\LimitationEstimate;
use App\Services\LimitationAssistant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LimitationController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly LimitationAssistant $assistant) {}

    public function store(Request $request, string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        $data = $request->validate([
            'basis' => ['required', 'string', Rule::in(array_keys(config('limitation.bases')))],
            'starts_on' => ['required', 'date'],
        ]);

        $starts = \Carbon\Carbon::parse($data['starts_on'], config('app.timezone'))->startOfDay();
        $suggested = $this->assistant->suggest($data['basis'], $starts);
        $label = (string) config('limitation.bases.'.$data['basis'].'.label');
        $disclaimer = (string) config('limitation.disclaimer');

        $event = CourtEvent::query()->create([
            'matter_id' => $model->id,
            'type' => CourtEventType::Limitation,
            'title' => 'Zastara: '.$label,
            'starts_at' => $suggested->copy()->setTime(9, 0),
            'responsible_user_id' => auth()->id(),
            'is_preclusive' => true,
            'notes' => $disclaimer,
        ]);

        LimitationEstimate::query()->updateOrCreate(
            ['matter_id' => $model->id],
            [
                'basis' => $data['basis'],
                'starts_on' => $starts->toDateString(),
                'suggested_on' => $suggested->toDateString(),
                'court_event_id' => $event->id,
                'confirmed_by_user_id' => auth()->id(),
            ],
        );

        return back()->with('status', 'Orijentacijski rok je '.$suggested->format('d.m.Y.').'. '.$disclaimer);
    }
}
