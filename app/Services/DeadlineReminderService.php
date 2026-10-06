<?php

namespace App\Services;

use App\Mail\DeadlineReminderMail;
use App\Models\CourtEvent;
use App\Models\EthicalWall;
use App\Models\DeadlineReminder;
use App\Models\OfficeNotification;
use App\Models\OrganizationUser;
use App\Enums\OrganizationRole;
use Illuminate\Support\Facades\Mail;

class DeadlineReminderService
{
    /** @var list<int> */
    public const OFFSETS = [10080, 1440, 60];

    public function sendDue(): int
    {
        $sent = 0;

        $events = CourtEvent::query()
            ->withoutGlobalScope('organization')
            ->with(['responsible', 'reminders', 'matter'])
            ->whereNull('completed_at')
            ->where('starts_at', '>', now())
            ->get();

        foreach ($events as $event) {
            foreach (self::OFFSETS as $offset) {
                $dueAt = $event->starts_at->copy()->subMinutes($offset);

                if (now()->lt($dueAt)) {
                    continue;
                }

                $reminder = $event->reminders->firstWhere('offset_minutes', $offset)
                    ?? DeadlineReminder::query()->create([
                        'court_event_id' => $event->id,
                        'offset_minutes' => $offset,
                    ]);

                if ($reminder->in_app_sent_at !== null && $reminder->email_sent_at !== null) {
                    continue;
                }

                $users = $this->recipients($event)->reject(function ($user) use ($event) {
                    if ($event->matter_id === null || $user === null) {
                        return false;
                    }

                    return EthicalWall::query()
                        ->where('matter_id', $event->matter_id)
                        ->where('user_id', $user->id)
                        ->exists();
                });

                foreach ($users as $user) {
                    if ($reminder->in_app_sent_at === null) {
                        OfficeNotification::query()->withoutGlobalScope('organization')->create([
                            'organization_id' => $event->organization_id,
                            'user_id' => $user->id,
                            'title' => 'Rok: '.$event->title,
                            'body' => $reminder->label().' prije '.$event->starts_at->timezone(config('app.timezone'))->format('d.m.Y. H:i'),
                            'court_event_id' => $event->id,
                        ]);
                    }

                    if ($reminder->email_sent_at === null && $user->email) {
                        Mail::to($user->email)->send(new DeadlineReminderMail($event, $reminder->label()));
                    }
                }

                $reminder->forceFill([
                    'in_app_sent_at' => $reminder->in_app_sent_at ?? now(),
                    'email_sent_at' => $reminder->email_sent_at ?? now(),
                ])->save();

                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    private function recipients(CourtEvent $event)
    {
        if ($event->responsible !== null) {
            return collect([$event->responsible]);
        }

        return OrganizationUser::query()
            ->with('user')
            ->where('organization_id', $event->organization_id)
            ->where('role', OrganizationRole::Owner)
            ->get()
            ->pluck('user')
            ->filter();
    }
}
