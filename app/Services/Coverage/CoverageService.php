<?php

namespace App\Services\Coverage;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Enums\Subject;
use App\Enums\Tense;
use App\Models\Card;
use App\Models\Verb;
use App\Models\Word;

/**
 * Works out which (verb x tense [x person]) slots and target words the unlocked
 * curriculum requires, which are already covered by active cards, and what's
 * still missing. Key verbs (drill_all_forms) require every person per conjugated
 * tense; other verbs need only one card per tense.
 */
class CoverageService
{
    /**
     * Every slot the curriculum currently requires, keyed by a stable slot key.
     *
     * @return array<string, array<string,mixed>>
     */
    public function requiredSlots(): array
    {
        $slots = [];

        foreach (Verb::unlocked()->get() as $verb) {
            foreach ($verb->enabled_tenses ?? [] as $tense) {
                if ($verb->drill_all_forms && $tense !== Tense::Infinitive->value) {
                    foreach (Subject::values() as $person) {
                        $slots["verb:{$verb->id}:{$tense}:{$person}"] = [
                            'kind' => 'verb', 'verb' => $verb, 'tense' => $tense, 'person' => $person,
                        ];
                    }
                } else {
                    $slots["verb:{$verb->id}:{$tense}:any"] = [
                        'kind' => 'verb', 'verb' => $verb, 'tense' => $tense, 'person' => null,
                    ];
                }
            }
        }

        foreach (Word::unlocked()->targets()->get() as $word) {
            $slots["word:{$word->id}"] = ['kind' => 'word', 'word' => $word];
        }

        return $slots;
    }

    /** @return array<string,bool> set of slot keys covered by active (approved) cards */
    public function coveredKeys(): array
    {
        return $this->keysForStatuses([CardStatus::Active]);
    }

    /**
     * Slot keys that have an approved OR draft card. Drafts don't count as
     * coverage, but generation must not re-fill a slot whose card is simply
     * waiting for review.
     *
     * @return array<string,bool>
     */
    public function addressedKeys(): array
    {
        return $this->keysForStatuses([CardStatus::Active, CardStatus::Draft]);
    }

    /**
     * @param  list<CardStatus>  $statuses
     * @return array<string,bool>
     */
    private function keysForStatuses(array $statuses): array
    {
        $drill = Verb::query()->pluck('drill_all_forms', 'id');
        $covered = [];

        // Vocab cards are bare word drills — only sentence cards count as coverage,
        // so generation keeps weaving these concepts into real sentences.
        $cards = Card::query()
            ->whereIn('status', array_map(fn (CardStatus $s) => $s->value, $statuses))
            ->where('source', '!=', CardSource::Vocab->value)
            ->get(['uses_concepts']);

        foreach ($cards as $card) {
            foreach ($card->uses_concepts ?? [] as $use) {
                if (($use['type'] ?? null) === 'word') {
                    $covered["word:{$use['id']}"] = true;

                    continue;
                }

                if (($use['type'] ?? null) !== 'verb') {
                    continue;
                }

                $id = $use['id'] ?? null;
                $tense = $use['tense'] ?? null;
                if (! $id || ! $tense) {
                    continue; // legacy card without tense — can't credit
                }

                $person = $use['person'] ?? null;
                $isDrill = (bool) ($drill[$id] ?? false);

                if ($isDrill && $tense !== Tense::Infinitive->value && $person) {
                    $covered["verb:{$id}:{$tense}:{$person}"] = true;
                } else {
                    $covered["verb:{$id}:{$tense}:any"] = true;
                }
            }
        }

        return $covered;
    }

    /** @return array<string, array<string,mixed>> required slots not covered by an approved card */
    public function gaps(): array
    {
        $covered = $this->coveredKeys();

        return array_filter(
            $this->requiredSlots(),
            fn (string $key) => ! isset($covered[$key]),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Required slots with no card at all — neither approved nor draft. These
     * are the only slots generation should still write cards for.
     *
     * @return array<string, array<string,mixed>>
     */
    public function openGaps(): array
    {
        $addressed = $this->addressedKeys();

        return array_filter(
            $this->requiredSlots(),
            fn (string $key) => ! isset($addressed[$key]),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Phrase a batch of gaps as natural requirement strings for the generator.
     *
     * @return array{verbUses: list<string>, words: list<string>}
     */
    public function gapRequirements(int $limit = 12): array
    {
        $verbUses = [];
        $words = [];

        foreach ($this->openGaps() as $slot) {
            if (count($verbUses) + count($words) >= $limit) {
                break;
            }

            if ($slot['kind'] === 'word') {
                $w = $slot['word'];
                $words[] = "{$w->spanish} ({$w->english})";
            } else {
                $v = $slot['verb'];
                $tenseLabel = Tense::from($slot['tense'])->label();
                $personLabel = $slot['person']
                    ? ' as '.Subject::from($slot['person'])->label().' ('.$slot['person'].')'
                    : '';
                $verbUses[] = "{$v->spanish} ({$v->english}) in {$tenseLabel}{$personLabel}";
            }
        }

        return ['verbUses' => $verbUses, 'words' => $words];
    }

    /**
     * A display-friendly breakdown for the coverage dashboard. Only approved
     * cards count as covered; slots whose card is a draft awaiting review are
     * reported separately (drafted), and "missing" means no card at all.
     */
    public function summary(): array
    {
        $required = $this->requiredSlots();
        $covered = $this->coveredKeys();
        $addressed = $this->addressedKeys();

        $groups = []; // tense => ['total'=>, 'covered'=>, 'drafted'=>, 'missing'=>[]]
        $wordTotal = 0;
        $wordCovered = 0;
        $wordDrafted = 0;
        $wordMissing = [];

        foreach ($required as $key => $slot) {
            $isCovered = isset($covered[$key]);
            $isDrafted = ! $isCovered && isset($addressed[$key]);

            if ($slot['kind'] === 'word') {
                $wordTotal++;
                if ($isCovered) {
                    $wordCovered++;
                } elseif ($isDrafted) {
                    $wordDrafted++;
                } else {
                    $wordMissing[] = $slot['word']->spanish;
                }

                continue;
            }

            $tense = $slot['tense'];
            $groups[$tense] ??= ['total' => 0, 'covered' => 0, 'drafted' => 0, 'missing' => []];
            $groups[$tense]['total']++;
            if ($isCovered) {
                $groups[$tense]['covered']++;
            } elseif ($isDrafted) {
                $groups[$tense]['drafted']++;
            } else {
                $label = $slot['verb']->spanish.($slot['person'] ? ' ('.Subject::from($slot['person'])->label().')' : '');
                $groups[$tense]['missing'][] = $label;
            }
        }

        $totalSlots = count($required);
        $coveredSlots = count(array_intersect_key($covered, $required));
        $draftSlots = count(array_intersect_key($addressed, $required)) - $coveredSlots;

        return [
            'groups' => $groups,
            'words' => ['total' => $wordTotal, 'covered' => $wordCovered, 'drafted' => $wordDrafted, 'missing' => $wordMissing],
            'total_slots' => $totalSlots,
            'covered_slots' => $coveredSlots,
            'draft_slots' => $draftSlots,
            'percent' => $totalSlots > 0 ? (int) round($coveredSlots / $totalSlots * 100) : 100,
            'gap_count' => $totalSlots - $coveredSlots,
            'open_gap_count' => $totalSlots - $coveredSlots - $draftSlots,
        ];
    }
}
