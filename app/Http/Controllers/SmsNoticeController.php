<?php

namespace App\Http\Controllers;

use App\Enums\SmsStatus;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\CourtEvent;
use App\Models\Party;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SmsNoticeController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly SmsService $sms) {}

    public function store(Request $request, string $slug, int $event): RedirectResponse
    {
        $this->authorizePerm('calendar.manage');
        $model = CourtEvent::query()->findOrFail($event);
        if ($model->matter_id === null) {
            return back()->with('error', 'SMS ide samo uz predmet.');
        }
        $this->findVisibleMatter($model->matter_id);

        $data = $request->validate([
            'party_id' => ['required', 'integer'],
        ]);
        $party = Party::query()->findOrFail($data['party_id']);
        $linked = $model->matter()->whereHas('parties', fn ($query) => $query->where('party_id', $party->id))->exists();
        if (! $linked) {
            abort(404);
        }

        $message = $this->sms->notify(
            $party,
            $model->title.' '.$model->starts_at->timezone(config('app.timezone'))->format('d.m.Y. H:i'),
            $model,
        );

        $flash = $message->status === SmsStatus::Sent ? 'status' : 'error';

        return back()->with($flash, $message->status->label().'.');
    }
}
