<?php

namespace App\Services\Claude;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\Pattern;
use App\Models\Setting;
use App\Models\Verb;
use App\Models\Word;

class CardGenerator
{
    public function __construct(private ClaudeClient $claude) {}

    /**
     * Generate up to $count new cards from the currently-unlocked concepts.
     * Returns the number of cards actually created (after the fence check).
     */
    public function generate(int $count, ?string $emphasis = null): int
    {
        $verbs = Verb::unlocked()->get();
        $words = Word::unlocked()->get();

        if ($verbs->isEmpty()) {
            throw new ClaudeException('No verbs are unlocked yet — unlock some in the Verbs grid first.');
        }

        $allowlist = [
            'verbs' => $verbs->map(fn (Verb $v) => [
                'id' => $v->id,
                'spanish' => $v->spanish,
                'english' => $v->english,
                'enabled_tenses' => $v->enabled_tenses,
            ])->values()->all(),
            'words' => $words->map(fn (Word $w) => [
                'id' => $w->id,
                'spanish' => $w->spanish,
                'english' => $w->english,
                'category' => $w->category->value,
                'role' => $w->role->value,
            ])->values()->all(),
        ];

        $patterns = Pattern::enabled()->pluck('instruction')->all();

        $system = $this->systemPrompt(Setting::get('house_style', ''), $patterns);
        $user = $this->userPrompt($allowlist, $count, $emphasis);

        $result = $this->claude->structured($system, $user, $this->schema());

        $cards = $result['cards'] ?? [];

        // Build the fence: only these (type,id) pairs are permitted.
        $allowedVerbIds = $verbs->pluck('id')->flip();
        $allowedWordIds = $words->pluck('id')->flip();

        $created = 0;
        foreach ($cards as $card) {
            if ($created >= $count) {
                break;
            }
            if (! $this->passesFence($card, $allowedVerbIds, $allowedWordIds)) {
                continue;
            }

            Card::create([
                'source' => CardSource::Ai,
                'spanish' => trim($card['spanish']),
                'english' => trim($card['english']),
                'test_direction' => 'es_to_en',
                'uses_concepts' => $card['uses_concepts'],
                'must_match' => $card['must_match'],
                'status' => CardStatus::Active,
            ]);
            $created++;
        }

        return $created;
    }

    /** @param  array<string,mixed>  $card */
    private function passesFence(array $card, $allowedVerbIds, $allowedWordIds): bool
    {
        foreach (['spanish', 'english', 'uses_concepts', 'must_match'] as $key) {
            if (! isset($card[$key])) {
                return false;
            }
        }
        if (trim((string) $card['spanish']) === '' || trim((string) $card['english']) === '') {
            return false;
        }

        foreach ($card['uses_concepts'] as $ref) {
            $type = $ref['type'] ?? null;
            $id = $ref['id'] ?? null;
            if ($type === 'verb' && $allowedVerbIds->has($id)) {
                continue;
            }
            if ($type === 'word' && $allowedWordIds->has($id)) {
                continue;
            }

            return false; // references a locked or unknown concept — drop the card
        }

        return true;
    }

    /** @param  list<string>  $patterns */
    private function systemPrompt(string $houseStyle, array $patterns): string
    {
        $rules = <<<'TXT'
        You generate Spanish reading-practice sentences for a child's spaced-repetition deck.

        HARD RULES (the fence — never break these):
        - Use ONLY the verbs in the allowlist, and only in the tenses listed in each verb's enabled_tenses.
        - Use ONLY the words in the allowlist. Never introduce a verb or word that is not in the allowlist.
        - Every concept you actually use must be listed in that card's uses_concepts as {type, id} using the allowlist ids.
        - "target" words should be exercised meaningfully; "ingredient" words (mostly nouns) may be sprinkled in for flavor.

        For each card return: the Spanish sentence, a natural English translation, the uses_concepts list, and a must_match object naming the meaning-critical features a grader must enforce:
        - tense: the main tense ("present", "past", or null if not applicable)
        - subject: the grammatical person ("1st_singular", "2nd_singular", "3rd_singular", "1st_plural", "3rd_plural", or null)
        - gender: "m"/"f" only when gender genuinely changes the meaning, otherwise null
        TXT;

        $patternText = '';
        if (! empty($patterns)) {
            $patternText = "\n\nACTIVE PATTERNS:\n- ".implode("\n- ", $patterns);
        }

        return trim($houseStyle."\n\n".$rules.$patternText);
    }

    /** @param  array<string,mixed>  $allowlist */
    private function userPrompt(array $allowlist, int $count, ?string $emphasis): string
    {
        $json = json_encode($allowlist, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $emphasisLine = $emphasis ? "\n\nEmphasis for this batch: {$emphasis}" : '';

        return "Generate {$count} new sentences using ONLY the allowlist below.{$emphasisLine}\n\nALLOWLIST:\n{$json}";
    }

    /** @return array<string,mixed> */
    private function schema(): array
    {
        $conceptRef = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['type', 'id'],
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['verb', 'word']],
                'id' => ['type' => 'integer'],
            ],
        ];

        $card = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['spanish', 'english', 'uses_concepts', 'must_match'],
            'properties' => [
                'spanish' => ['type' => 'string'],
                'english' => ['type' => 'string'],
                'uses_concepts' => ['type' => 'array', 'items' => $conceptRef],
                'must_match' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['tense', 'subject', 'gender'],
                    'properties' => [
                        'tense' => ['type' => ['string', 'null']],
                        'subject' => ['type' => ['string', 'null']],
                        'gender' => ['type' => ['string', 'null']],
                    ],
                ],
            ],
        ];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['cards'],
            'properties' => [
                'cards' => ['type' => 'array', 'items' => $card],
            ],
        ];
    }
}
