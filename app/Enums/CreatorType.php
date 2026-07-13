<?php

declare(strict_types=1);

namespace App\Enums;

enum CreatorType: string
{
    case Ai = 'ai';
    case Business = 'business';
    case Admin = 'admin';
}
