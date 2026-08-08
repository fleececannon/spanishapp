<?php

namespace App\Services\Vocab;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Enums\Tense;
use App\Models\Card;
use App\Models\Verb;
use App\Models\Word;

/**
 * Keeps single-word vocab cards in lockstep with the "vocab card" checkbox on
 * words and verbs. Checked = an active card exists (created instantly, no AI).
 * Unchecked = the card is retired, keeping any review history for later.
 */
class VocabCardService
{
    /** Make reality match the concept's vocab_card flag. */
    public function sync(Word|Verb $concept): void
    {
        $card = $this->findFor($concept);

        if (! $concept->vocab_card) {
            $card?->update(['status' => CardStatus::Retired]);

            return;
        }

        if ($card) {
            // Keep the card's text in step with edits to the word itself.
            $card->update([
                'spanish' => $concept->spanish,
                'english' => $concept->english,
                'status' => CardStatus::Active,
            ]);

            return;
        }

        Card::create([
            'source' => CardSource::Vocab,
            'spanish' => $concept->spanish,
            'english' => $concept->english,
            'test_direction' => 'es_to_en',
            'uses_concepts' => [$this->conceptRef($concept)],
            'must_match' => ['tense' => null, 'subject' => null, 'gender' => null],
            'status' => CardStatus::Active,
        ]);
    }

    /** The concept is being deleted — its vocab card goes with it. */
    public function deleteFor(Word|Verb $concept): void
    {
        $this->findFor($concept)?->delete();
    }

    /** Recreate vocab cards for every flagged word and verb (used after Rebuild). */
    public function resyncAll(): void
    {
        Word::where('vocab_card', true)->get()->each(fn (Word $w) => $this->sync($w));
        Verb::where('vocab_card', true)->get()->each(fn (Verb $v) => $this->sync($v));
    }

    private function findFor(Word|Verb $concept): ?Card
    {
        $ref = $this->conceptRef($concept);

        return Card::where('source', CardSource::Vocab->value)
            ->get()
            ->first(fn (Card $c) => ($c->uses_concepts[0]['type'] ?? null) === $ref['type']
                && ($c->uses_concepts[0]['id'] ?? null) === $ref['id']);
    }

    /** @return array<string, mixed> */
    private function conceptRef(Word|Verb $concept): array
    {
        return $concept instanceof Verb
            ? ['type' => 'verb', 'id' => $concept->id, 'tense' => Tense::Infinitive->value]
            : ['type' => 'word', 'id' => $concept->id];
    }
}
