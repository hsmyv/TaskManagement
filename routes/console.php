<?php

use App\Console\Commands\SendDeadlineReminders;
use Illuminate\Support\Facades\Schedule;

Schedule::command(SendDeadlineReminders::class)->dailyAt('08:00');

Schedule::command('tis:deadline-reminders')->hourly();
