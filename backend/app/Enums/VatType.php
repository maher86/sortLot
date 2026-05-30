<?php

namespace App\Enums;

enum VatType: string
{
    case Mainland = 'mainland';
    case FreeZone = 'free_zone';
    case International = 'international';
}
