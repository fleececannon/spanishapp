<?php

namespace App\Enums;

enum ReviewResult: string
{
    case Pass = 'pass';
    case NeedsWork = 'needs_work';
}
