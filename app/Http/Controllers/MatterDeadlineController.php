<?php

namespace App\Http\Controllers;

use App\Enums\CourtEventType;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\CourtEvent;
use App\Services\DeadlineCalculator;
use App\Services\StatutoryDeadlineCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MatterDeadlineController extends Controller
{
    use ResolvesOffice;

    public function __construct(
        private readonly StatutoryDeadlineCatalog $catalog,
        private readonly DeadlineCalculator $calculator,
    ) {}

    public function store(Request $request, string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        $model->load('disputeCategory');
        $keys = array_column($this->catalog->forMatter($model), 'key');

        $data = $request->validate([
            'rule' => ['required', 'string', Rule::in($keys)],
            'receipt_on' => ['required', 'date'],
            'intent' => ['required', Rule::in(['preview', 'save'])],
        ]);

        $rule = $this->catalog->find($data['rule']);
        $due = $this->calculator->due(Carbon::parse($data['receipt_on']), (int) $rule['amount'], $rule['unit']);

        if ($data['intent'] === 'preview') {
            return back()->withInput()->with('deadline_preview', [
                'rule' => $rule['key'],
                'label' => $rule['label'],
                'basis' => $rule['basis'],
                'receipt_on' => $data['receipt_on'],
                'due_on' => $due->toDateString(),
            ]);
        }

        CourtEvent::query()->create([
            'matter_id' => $model->id,
            'type' => CourtEventType::from($rule['event_type']),
            'title' => $rule['label'],
            'court_name' => $model->court_name,
            'starts_at' => $this->calculator->expiresAt($due),
            'responsible_user_id' => auth()->id(),
            'is_preclusive' => true,
            'origin' => 'statutory',
            'statutory_rule' => $rule['key'],
            'receipt_on' => $data['receipt_on'],
            'notes' => $rule['basis'].' Okidač: '.$rule['trigger'].'.',
        ]);

        return back()->with('status', 'Rok je upisan: '.$due->format('d.m.Y.').' '.$rule['basis']);
    }
}
