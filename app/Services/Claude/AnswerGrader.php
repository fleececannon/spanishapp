<?php

namespace App\Services\Claude;

use App\Enums\ReviewResult;
use App\Models\Card;
use App\Models\Setting;

class AnswerGrader
{
    public function __construct(private ClaudeClient $claude) {}

    public function grade(Card $card, string $typedEnglish): GradeResult
    {
        $system = $this->systemPrompt(Setting::get('house_style', ''));
        $user = $this->userPrompt($card, $typedEnglish);

        $result = $this->claude->structured($system, $user, $this->schema(), maxTokens: 1024);

        $verdict = ($result['verdict'] ?? 'needs_work') === 'pass'
            ? ReviewResult::Pass
            : ReviewResult::NeedsWork;

        return new GradeResult(
            verdict: $verdict,
            acceptedEnglish: (string) ($result['accepted_english'] ?? $card->english),
            nudge: $result['nudge'] ?? null,
        );
    }

    private function systemPrompt(string $houseStyle): string
    {
        $rules = <<<'TXT'
        You grade a child's English translation of a Spanish sentence. Judge COMPREHENSION, not precision.

        PASS if the child got the overall meaning right AND honored every feature in must_match.
        FORGIVE: spelling, accents, synonyms, natural paraphrase, word order, and ingredient noun choices.
        ENFORCE only the must_match features: tense, subject (person/number), and gender when listed.

        Return:
        - verdict: "pass" or "needs_work"
        - accepted_english: a natural correct English translation to show the child
        - nudge: if needs_work, ONE short, kind sentence naming the missed feature (e.g. "Close — this one is past tense."); otherwise null
        TXT;

        return trim($houseStyle."\n\n".$rules);
    }

    private function userPrompt(Card $card, string $typedEnglish): string
    {
        $mustMatch = json_encode($card->must_match, JSON_UNESCAPED_UNICODE);

        return <<<TXT
        Spanish sentence: {$card->spanish}
        Reference English: {$card->english}
        must_match (features to enforce): {$mustMatch}

        The child typed: {$typedEnglish}

        Grade it.
        TXT;
    }

    /** @return array<string,mixed> */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['verdict', 'accepted_english', 'nudge'],
            'properties' => [
                'verdict' => ['type' => 'string', 'enum' => ['pass', 'needs_work']],
                'accepted_english' => ['type' => 'string'],
                'nudge' => ['type' => ['string', 'null']],
            ],
        ];
    }
}
