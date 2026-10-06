<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\CalendarSubscription;
use App\Models\CourtEvent;
use App\Models\Organization;
use App\Services\IcsCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class CalendarFeedController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly IcsCalendar $calendar) {}

    public function export(string $slug): Response
    {
        $this->authorizePerm('calendar.view');
        $this->authorizeFeature('calendar_sync');

        return $this->ics($this->office());
    }

    public function publicFeed(string $token): Response
    {
        $organization = Organization::query()->where('calendar_feed_token', $token)->firstOrFail();
        app()->instance('currentOrganization', $organization);

        return $this->ics($organization);
    }

    public function import(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('calendar.manage');
        $this->authorizeFeature('calendar_sync');
        $data = $request->validate([
            'feed_url' => ['required', 'url', 'max:500'],
        ]);

        $body = Http::timeout(10)->get($data['feed_url'])->throw()->body();
        $created = $this->calendar->import($this->office(), $body);
        CalendarSubscription::query()->updateOrCreate(
            ['feed_url' => $data['feed_url']],
            ['last_synced_at' => now()],
        );

        return back()->with('status', 'Uvezeno novih termina: '.$created.'.');
    }

    private function ics(Organization $organization): Response
    {
        $events = CourtEvent::query()
            ->with('matter')
            ->where('organization_id', $organization->id)
            ->where('source', 'office')
            ->orderBy('starts_at')
            ->get();

        return response($this->calendar->export($organization, $events), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
        ]);
    }
}
