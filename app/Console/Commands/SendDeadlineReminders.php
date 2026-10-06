<?php

namespace App\Console\Commands;

use App\Services\DeadlineReminderService;
use Illuminate\Console\Command;

class SendDeadlineReminders extends Command
{
    protected $signature = 'legal:send-deadline-reminders';

    protected $description = 'Šalje in-app i e-mail podsjetnike za ročišta i rokove.';

    public function handle(DeadlineReminderService $reminders): int
    {
        $count = $reminders->sendDue();
        $this->info('Poslano podsjetnika: '.$count);

        return self::SUCCESS;
    }
}
