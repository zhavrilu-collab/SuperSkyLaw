<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('legal:send-deadline-reminders')->everyMinute();
Schedule::command('legal:send-invoice-reminders')->dailyAt('07:15');
