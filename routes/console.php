<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Process scheduled trips - safety net for missed jobs
Schedule::command('trips:process-scheduled')
    ->everyFiveMinutes()
    ->onOneServer()
    ->runInBackground()
    ->withoutOverlapping();
