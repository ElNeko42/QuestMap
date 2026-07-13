<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Expired = 'expired';
    case Exhausted = 'exhausted';
}
