<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Verb;
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

    private function card(int $verbId, string $tense = 'present', ?string $person = '1st_singular', array $wordIds = []): array
    {
        return [
            'spanish' => 'Yo tengo agua',
            'english' => 'I have water',
            'verbs_used' => [['id' => $verbId, 'tense' => $tense, 'person' => $person]],
            'word_ids' => $wordIds,
            'must_match' => ['tense' => $tense, 'subject' => $person, 'gender' => null],
        ];
    }

    public function test_creates_cards_and_stores_tense_and_person(): void
    {
        $verb = $this->unlockedVerb();
        $this->fakeClaude([$this->card($verb->id)]);

        $created = app(CardGenerator::class)->generate(5);

        $this->assertSame(1, $created);
        $stored = Card::active()->first();
        $this->assertSame([
            ['type' => 'verb', 'id' => $verb->id, 'tense' => 'present', 'person' => '1st_singular'],
        ], $stored->uses_concepts);
    }

    public function test_fence_drops_locked_verbs_unknown_words_and_disallowed_tenses(): void
    {
        $verb = $this->unlockedVerb(); // enabled: present only
        $locked = Verb::create([
            'spanish' => 'Volar', 'english' => 'to fly', 'tag' => 'X',
            'verb_class' => 'AR', 'enabled_tenses' => ['present'], 'drill_all_forms' => false, 'unlocked' => false,
        ]);

        $this->fakeClaude([
            $this->card($verb->id),                                   // valid
            $this->card($locked->id),                                 // locked verb -> drop
            $this->card($verb->id, tense: 'past'),                    // tense not enabled -> drop
            $this->card($verb->id, wordIds: [99999]),                 // unknown word -> drop
        ]);

        $created = app(CardGenerator::class)->generate(10);

        $this->assertSame(1, $created);
    }

    public function test_infinitive_use_stores_null_person(): void
    {
        $verb = Verb::create([
            'spanish' => 'Caminar', 'english' => 'to walk', 'tag' => 'Verb Set 1',
            'verb_class' => 'AR', 'enabled_tenses' => ['infinitive'], 'drill_all_forms' => false, 'unlocked' => true,
        ]);
        $this->fakeClaude([$this->card($verb->id, tense: 'infinitive', person: '1st_singular')]);

        app(CardGenerator::class)->generate(5);

        $this->assertNull(Card::active()->first()->uses_concepts[0]['person']);
    }

    public function test_respects_requested_count(): void
    {
        $verb = $this->unlockedVerb();
        $this->fakeClaude(array_fill(0, 10, $this->card($verb->id)));

        $this->assertSame(3, app(CardGenerator::class)->generate(3));
    }
}
