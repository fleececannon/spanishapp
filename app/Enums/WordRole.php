<?php

namespace App\Enums;

enum WordRole: string
{
    /** Taught and tested — grading may enforce it. */
    case Target = 'target';

    /** Flavor only — sprinkled into sentences, never the point of a card. */
    case Ingredient = 'ingredient';
}
