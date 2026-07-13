<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Mark expired quests every hour.
Schedule::command('quests:expire')->hourly();
