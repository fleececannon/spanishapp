<?php

namespace App\Enums;

enum WordCategory: string
{
    case Pronoun = 'pronoun';
    case Connector = 'connector';
    case Adverb = 'adverb';
    case Noun = 'noun';
    case Adjective = 'adjective';
    case Question = 'question';

    /** The default role for a freshly added word of this category. */
    public function defaultRole(): WordRole
    {
        return $this === self::Noun ? WordRole::Ingredient : WordRole::Target;
    }
}
