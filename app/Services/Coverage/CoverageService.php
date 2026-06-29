<?php

namespace App\Services\Coverage;

use App\Enums\Subject;
use App\Enums\Tense;
use App\Models\Card;
use App\Models\Verb;
use App\Models\Word;
use Illuminate\Support\Collection;

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

    /** @return array<string,bool> set of slot keys covered by active cards */
    public function coveredKeys(): array
    {
        $drill = Verb::query()->pluck('drill_all_forms', 'id');
        $covered = [];

        foreach (Card::active()->get(['uses_concepts']) as $card) {
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

    /** @return array<string, array<string,mixed>> required slots not yet covered */
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
     * Phrase a batch of gaps as natural requirement strings for the generator.
     *
     * @return array{verbUses: list<string>, words: list<string>}
     */
    public function gapRequirements(int $limit = 12): array
    {
        $verbUses = [];
        $words = [];

        foreach ($this->gaps() as $slot) {
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

    /** A display-friendly breakdown for the coverage dashboard. */
    public function summary(): array
    {
        $required = $this->requiredSlots();
        $covered = $this->coveredKeys();

        $groups = []; // tense => ['total'=>, 'covered'=>, 'missing'=>[]]
        $wordTotal = 0;
        $wordCovered = 0;
        $wordMissing = [];

        foreach ($required as $key => $slot) {
            $isCovered = isset($covered[$key]);

            if ($slot['kind'] === 'word') {
                $wordTotal++;
                $isCovered ? $wordCovered++ : $wordMissing[] = $slot['word']->spanish;

                continue;
            }

            $tense = $slot['tense'];
            $groups[$tense] ??= ['total' => 0, 'covered' => 0, 'missing' => []];
            $groups[$tense]['total']++;
            if ($isCovered) {
                $groups[$tense]['covered']++;
            } else {
                $label = $slot['verb']->spanish.($slot['person'] ? ' ('.Subject::from($slot['person'])->label().')' : '');
                $groups[$tense]['missing'][] = $label;
            }
        }

        $totalSlots = count($required);
        $coveredSlots = count(array_intersect_key($covered, $required));

        return [
            'groups' => $groups,
            'words' => ['total' => $wordTotal, 'covered' => $wordCovered, 'missing' => $wordMissing],
            'total_slots' => $totalSlots,
            'covered_slots' => $coveredSlots,
            'percent' => $totalSlots > 0 ? (int) round($coveredSlots / $totalSlots * 100) : 100,
            'gap_count' => $totalSlots - $coveredSlots,
        ];
    }
}
