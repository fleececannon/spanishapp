<?php

namespace App\Enums;

use App\Models\Card;

/**
 * Which side of a card is the prompt. The two directions are separate decks:
 * a card belongs to exactly one, and is scheduled only within it.
 */
enum Direction: string
{
    case EsToEn = 'es_to_en';
    case EnToEs = 'en_to_es';

    /** Anything unrecognised (a stale link, a typo'd query string) is the original deck. */
    public static function fromInput(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::EsToEn;
    }

    public function label(): string
    {
        return match ($this) {
            self::EsToEn => 'Spanish → English',
            self::EnToEs => 'English → Spanish',
        };
    }

    /** The side shown first. */
    public function prompt(Card $card): string
    {
        return $this === self::EsToEn ? $card->spanish : $card->english;
    }

    /** The side revealed after the kid has said their answer. */
    public function answer(Card $card): string
    {
        return $this === self::EsToEn ? $card->english : $card->spanish;
    }

    /**
     * A card side can hold a short exchange — a question and its reply —
     * written either on separate lines or as "question — reply". Split it so
     * each part can be shown on its own line.
     *
     * @return list<string>
     */
    public static function lines(string $text): array
    {
        $parts = preg_split('/\R|\s+—\s+/u', $text) ?: [$text];

        return array_values(array_filter(array_map('trim', $parts), fn (string $part) => $part !== ''));
    }

    public function answerLabel(): string
    {
        return $this === self::EsToEn ? 'In English' : 'In Spanish';
    }
}
