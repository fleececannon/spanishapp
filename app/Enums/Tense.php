<?php

namespace App\Enums;

/**
 * The forms a verb can be enabled in. Stored as an open list on each verb
 * (verbs.enabled_tenses); Phase 1 uses infinitive/present/past, the rest are
 * here so the matrix can grow without a schema change.
 */
enum Tense: string
{
    case Infinitive = 'infinitive';
    case Present = 'present';
    case Past = 'past';
    case Future = 'future';
    case Gerund = 'gerund';
    case Imperfect = 'imperfect';
    case Commands = 'commands';
    case Conditional = 'conditional';

    public function label(): string
    {
        return match ($this) {
            self::Infinitive => 'Infinitive',
            self::Present => 'Present',
            self::Past => 'Past (preterite)',
            self::Future => 'Future',
            self::Gerund => 'Gerund (-ing)',
            self::Imperfect => 'Imperfect',
            self::Commands => 'Commands',
            self::Conditional => 'Conditional',
        };
    }
}
