<?php

namespace App\Enums;

/**
 * Grammatical person used for conjugation coverage. Mirrors the spreadsheet's
 * five practice persons (Usted groups with 3rd singular, Ustedes with 3rd plural).
 */
enum Subject: string
{
    case FirstSingular = '1st_singular';
    case SecondSingular = '2nd_singular';
    case ThirdSingular = '3rd_singular';
    case FirstPlural = '1st_plural';
    case ThirdPlural = '3rd_plural';

    public function label(): string
    {
        return match ($this) {
            self::FirstSingular => 'yo',
            self::SecondSingular => 'tú',
            self::ThirdSingular => 'él / ella',
            self::FirstPlural => 'nosotros',
            self::ThirdPlural => 'ellos / ellas',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
