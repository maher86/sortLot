<?php

namespace App\Enums;

enum ItemStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Returned = 'returned';
    case Damaged = 'damaged';
    case Missing = 'missing';
}
