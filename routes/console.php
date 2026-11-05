<?php

use App\Models\HealthCheck;
use Illuminate\Support\Facades\Schedule;

// Runs from the schedule:work loop in nixpacks.toml's start command.
Schedule::command('health:ping')->everyMinute()->withoutOverlapping(2);

// Keep two weeks of pings (~6 systems x 1,440 a day).
Schedule::call(fn () => HealthCheck::where('checked_at', '<', now()->subDays(14))->delete())
    ->daily()
    ->name('prune-health-checks');
