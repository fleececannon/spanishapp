<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Verb;
use App\Models\Word;
use App\Services\Claude\CardGenerator;
use App\Services\Claude\ClaudeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function unlockedVerb(): Verb
    {
        return Verb::create([
            'spanish' => 'Tener', 'english' => 'to have', 'tag' => 'Key Verbs',
            'verb_class' => 'ER', 'enabled_tenses' => ['present'],
            'drill_all_forms' => true, 'unlocked' => true,
        ]);
    }

    private function fakeClaude(array $cards): void
    {
        $this->mock(ClaudeClient::class, function ($mock) use ($cards) {
            $mock->shouldReceive('structured')->andReturn(['cards' => $cards]);
        });
    }

    public function test_creates_cards_returned_by_claude(): void
    {
        $verb = $this->unlockedVerb();
        $this->fakeClaude([[
            'spanish' => 'Yo tengo agua',
            'english' => 'I have water',
            'uses_concepts' => [['type' => 'verb', 'id' => $verb->id]],
            'must_match' => ['tense' => 'present', 'subject' => '1st_singular', 'gender' => null],
        ]]);

        $created = app(CardGenerator::class)->generate(5);

        $this->assertSame(1, $created);
        $this->assertSame(1, Card::active()->count());
    }

    public function test_fence_drops_cards_referencing_locked_or_unknown_concepts(): void
    {
        $verb = $this->unlockedVerb();
        $lockedVerb = Verb::create([
            'spanish' => 'Volar', 'english' => 'to fly', 'tag' => 'Verb Set 9',
            'verb_class' => 'AR', 'enabled_tenses' => ['present'],
            'drill_all_forms' => false, 'unlocked' => false,
        ]);

        $this->fakeClaude([
            [ // valid
                'spanish' => 'Yo tengo agua', 'english' => 'I have water',
                'uses_concepts' => [['type' => 'verb', 'id' => $verb->id]],
                'must_match' => ['tense' => 'present', 'subject' => '1st_singular', 'gender' => null],
            ],
            [ // references a LOCKED verb -> must be dropped
                'spanish' => 'Yo vuelo', 'english' => 'I fly',
                'uses_concepts' => [['type' => 'verb', 'id' => $lockedVerb->id]],
                'must_match' => ['tense' => 'present', 'subject' => '1st_singular', 'gender' => null],
            ],
            [ // references an UNKNOWN id -> must be dropped
                'spanish' => 'Algo raro', 'english' => 'Something weird',
                'uses_concepts' => [['type' => 'word', 'id' => 99999]],
                'must_match' => ['tense' => null, 'subject' => null, 'gender' => null],
            ],
        ]);

        $created = app(CardGenerator::class)->generate(5);

        $this->assertSame(1, $created);
        $this->assertSame('Yo tengo agua', Card::active()->first()->spanish);
    }

    public function test_respects_requested_count(): void
    {
        $verb = $this->unlockedVerb();
        $valid = [
            'spanish' => 'Yo tengo agua', 'english' => 'I have water',
            'uses_concepts' => [['type' => 'verb', 'id' => $verb->id]],
            'must_match' => ['tense' => 'present', 'subject' => '1st_singular', 'gender' => null],
        ];
        $this->fakeClaude(array_fill(0, 10, $valid));

        $created = app(CardGenerator::class)->generate(3);

        $this->assertSame(3, $created);
    }
}
