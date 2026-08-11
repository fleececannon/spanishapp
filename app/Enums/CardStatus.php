<?php

namespace App\Enums;

enum CardStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Retired = 'retired';
}
