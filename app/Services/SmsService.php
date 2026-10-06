<?php

namespace App\Services;

use App\Enums\SmsStatus;
use App\Models\CourtEvent;
use App\Models\Party;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\Http;

class SmsService
{
    public function notify(Party $party, string $body, ?CourtEvent $event = null): SmsMessage
    {
        $message = new SmsMessage([
            'organization_id' => $party->organization_id,
            'party_id' => $party->id,
            'court_event_id' => $event?->id,
            'phone' => $party->phone,
            'body' => $body,
            'cost_cents' => 0,
            'status' => SmsStatus::SkippedNoConsent,
        ]);

        if ($party->sms_consent_at === null || blank($party->phone)) {
            $message->status = SmsStatus::SkippedNoConsent;
            $message->save();

            return $message;
        }

        $url = (string) config('services.sms.url');
        if ($url === '') {
            $message->status = SmsStatus::SkippedNoProvider;
            $message->save();

            return $message;
        }

        $response = Http::withToken((string) config('services.sms.token'))
            ->post($url, [
                'to' => $party->phone,
                'body' => $body,
            ]);

        $message->status = $response->successful() ? SmsStatus::Sent : SmsStatus::Failed;
        $message->cost_cents = $response->successful() ? (int) config('services.sms.cost_cents') : 0;
        $message->save();

        return $message;
    }
}
