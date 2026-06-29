<?php

namespace App\Services\Claude;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Enums\Tense;
use App\Models\Card;
use App\Models\Pattern;
use App\Models\Setting;
use App\Models\Verb;
use App\Models\Word;
use Illuminate\Support\Collection;

class CardGenerator
{
    public function __construct(private ClaudeClient $claude) {}

    /**
     * Open-ended generation: up to $count fresh sentences from unlocked concepts.
     * Returns the number of cards created (after the fence check).
     */
    public function generate(int $count, ?string $emphasis = null): int
    {
        $emphasisLine = $emphasis ? "\n\nEmphasis for this batch: {$emphasis}" : '';
        $instruction = "Generate {$count} new sentences using ONLY the allowlist below.{$emphasisLine}";

        return $this->runBatch($instruction, limit: $count);
    }

    /**
     * Gap-targeted generation: ask for sentences that TOGETHER cover the given
     * required uses, packing as many as natural into each sentence.
     *
     * @param  list<string>  $requiredVerbUses  human-readable verb-tense-person requirements
     * @param  list<string>  $requiredWords     words that must appear
     */
    public function generateForGaps(array $requiredVerbUses, array $requiredWords): int
    {
        $lines = "Write as few natural sentences as possible that TOGETHER cover ALL of the required uses below. ".
            "Pack multiple required uses into each sentence wherever it still reads naturally to a child.";

        if ($requiredVerbUses) {
            $lines .= "\n\nREQUIRED VERB USES (each must appear at least once, in the stated tense and person):\n- ".implode("\n- ", $requiredVerbUses);
        }
        if ($requiredWords) {
            $lines .= "\n\nREQUIRED WORDS (each must appear at least once):\n- ".implode("\n- ", $requiredWords);
        }

        return $this->runBatch($lines);
    }

    private function runBatch(string $instruction, ?int $limit = null): int
    {
        $verbs = Verb::unlocked()->get()->keyBy('id');
        $words = Word::unlocked()->get()->keyBy('id');

        if ($verbs->isEmpty()) {
            throw new ClaudeException('No verbs are unlocked yet — unlock some in the Verbs grid first.');
        }

        $system = $this->systemPrompt(Setting::get('house_style', ''), Pattern::enabled()->pluck('instruction')->all());
        $user = $this->userPrompt($verbs, $words, $instruction);

        $result = $this->claude->structured($system, $user, $this->schema());

        $created = 0;
        foreach ($result['cards'] ?? [] as $card) {
            if ($limit !== null && $created >= $limit) {
                break;
            }
            if ($this->persist($card, $verbs, $words)) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Validate a single generated card against the fence and store it.
     * uses_concepts is stored as a typed list: verb entries carry tense+person.
     */
    private function persist(array $card, Collection $verbs, Collection $words): bool
    {
        if (empty($card['spanish']) || empty($card['english']) || trim($card['spanish']) === '' || trim($card['english']) === '') {
            return false;
        }

        $uses = [];

        foreach ($card['verbs_used'] ?? [] as $vu) {
            $verb = $verbs->get($vu['id'] ?? null);
            $tense = $vu['tense'] ?? null;

            // Fence: verb must be unlocked AND used in an enabled tense.
            if (! $verb || ! in_array($tense, $verb->enabled_tenses ?? [], true)) {
                return false;
            }

            $person = $tense === Tense::Infinitive->value ? null : ($vu['person'] ?? null);
            $uses[] = ['type' => 'verb', 'id' => $verb->id, 'tense' => $tense, 'person' => $person];
        }

        foreach ($card['word_ids'] ?? [] as $wid) {
            if (! $words->has($wid)) {
                return false; // locked or unknown word
            }
            $uses[] = ['type' => 'word', 'id' => $wid];
        }

        Card::create([
            'source' => CardSource::Ai,
            'spanish' => trim($card['spanish']),
            'english' => trim($card['english']),
            'test_direction' => 'es_to_en',
            'uses_concepts' => $uses,
            'must_match' => [
                'tense' => $card['must_match']['tense'] ?? null,
                'subject' => $card['must_match']['subject'] ?? null,
                'gender' => $card['must_match']['gender'] ?? null,
            ],
            'status' => CardStatus::Active,
        ]);

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
        - For every verb you use, list it in verbs_used as {id, tense, person} using the allowlist id, the tense you used it in, and the grammatical person (one of: 1st_singular, 2nd_singular, 3rd_singular, 1st_plural, 3rd_plural; use null only for the infinitive). For every allowlist word you use, list its id in word_ids.

        For each card also return a must_match object naming the meaning-critical features a grader must enforce:
        - tense: the main tense ("present", "past", or null)
        - subject: the main grammatical person (same codes as above, or null)
        - gender: "m"/"f" only when gender genuinely changes the meaning, otherwise null
        TXT;

        $patternText = $patterns ? "\n\nACTIVE PATTERNS:\n- ".implode("\n- ", $patterns) : '';

        return trim($houseStyle."\n\n".$rules.$patternText);
    }

    private function userPrompt(Collection $verbs, Collection $words, string $instruction): string
    {
        $allowlist = [
            'verbs' => $verbs->map(fn (Verb $v) => [
                'id' => $v->id, 'spanish' => $v->spanish, 'english' => $v->english,
                'enabled_tenses' => $v->enabled_tenses,
            ])->values()->all(),
            'words' => $words->map(fn (Word $w) => [
                'id' => $w->id, 'spanish' => $w->spanish, 'english' => $w->english,
                'category' => $w->category->value, 'role' => $w->role->value,
            ])->values()->all(),
        ];

        $json = json_encode($allowlist, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return "{$instruction}\n\nALLOWLIST:\n{$json}";
    }

    /** @return array<string,mixed> */
    private function schema(): array
    {
        $verbUse = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['id', 'tense', 'person'],
            'properties' => [
                'id' => ['type' => 'integer'],
                'tense' => ['type' => 'string', 'enum' => array_map(fn (Tense $t) => $t->value, Tense::cases())],
                'person' => ['type' => ['string', 'null']],
            ],
        ];

        $card = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['spanish', 'english', 'verbs_used', 'word_ids', 'must_match'],
            'properties' => [
                'spanish' => ['type' => 'string'],
                'english' => ['type' => 'string'],
                'verbs_used' => ['type' => 'array', 'items' => $verbUse],
                'word_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
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
            'properties' => ['cards' => ['type' => 'array', 'items' => $card]],
        ];
    }
}
