<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Schedule city caching to run weekly on Sundays at 2am
Schedule::command('weather:cache-cities')->weekly()->sundays()->at('02:00');
