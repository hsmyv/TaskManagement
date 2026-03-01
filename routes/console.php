<?php

use App\Console\Commands\SendDeadlineReminders;
use Illuminate\Support\Facades\Schedule;

// Hər gün saat 08:00-da deadline xatırlatması
Schedule::command(SendDeadlineReminders::class)->dailyAt('08:00');

// Hər saat email queue-nu işlət
Schedule::command('tis:deadline-reminders')->hourly();
