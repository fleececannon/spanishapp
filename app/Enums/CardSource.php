<?php

namespace App\Enums;

enum CardSource: string
{
    case Ai = 'ai';
    case Manual = 'manual';
    case Vocab = 'vocab';
}
