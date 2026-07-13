<?php

declare(strict_types=1);

namespace App\Enums;

enum ValidationType: string
{
    case PhotoAi = 'photo_ai';
    case Checkin = 'checkin';
    case DataInput = 'data_input';
}
