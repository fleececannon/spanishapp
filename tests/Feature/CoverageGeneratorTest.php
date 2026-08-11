<?php

namespace Tests\Feature;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\Verb;
use App\Services\Claude\CardGenerator;
use App\Services\Coverage\CoverageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoverageGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function infinitiveVerb(): Verb
    {
        return Verb::create([
            'spanish' => 'Caminar', 'english' => 'to walk', 'tag' => 'Verb Set 1',
            'verb_class' => 'AR', 'enabled_tenses' => ['infinitive'],
            'drill_all_forms' => false, 'unlocked' => true,
        ]);
    }

    public function test_does_nothing_when_already_fully_covered(): void
    {
        $verb = $this->infinitiveVerb();
        Card::create([
            'source' => CardSource::Ai, 'spanish' => 's', 'english' => 'e', 'test_direction' => 'es_to_en',
            'uses_concepts' => [['type' => 'verb', 'id' => $verb->id, 'tense' => 'infinitive', 'person' => null]],
            'must_match' => ['tense' => null, 'subject' => null, 'gender' => null], 'status' => CardStatus::Active,
        ]);

        $this->mock(CardGenerator::class, fn ($m) => $m->shouldNotReceive('generateForGaps'));

        $result = app(CoverageGenerator::class)->fill();

        $this->assertTrue($result['done']);
        $this->assertSame(0, $result['created']);
    }

    public function test_fills_the_gap_then_reports_done(): void
    {
        $verb = $this->infinitiveVerb();

        // The fake generator "covers" the gap by inserting the needed card.
        $this->mock(CardGenerator::class, function ($m) use ($verb) {
            $m->shouldReceive('generateForGaps')->andReturnUsing(function () use ($verb) {
                Card::create([
                    'source' => CardSource::Ai, 'spanish' => 's', 'english' => 'e', 'test_direction' => 'es_to_en',
                    'uses_concepts' => [['type' => 'verb', 'id' => $verb->id, 'tense' => 'infinitive', 'person' => null]],
                    'must_match' => ['tense' => null, 'subject' => null, 'gender' => null], 'status' => CardStatus::Active,
                ]);

                return 1;
            });
        });

        $result = app(CoverageGenerator::class)->fill();

        $this->assertTrue($result['done']);
        $this->assertSame(1, $result['created']);
    }

    public function test_draft_cards_count_as_progress_and_finish_the_run(): void
    {
        $verb = $this->infinitiveVerb();

        // Real behavior now: the generator writes DRAFTS, not active cards.
        $this->mock(CardGenerator::class, function ($m) use ($verb) {
            $m->shouldReceive('generateForGaps')->once()->andReturnUsing(function () use ($verb) {
                Card::create([
                    'source' => CardSource::Ai, 'spanish' => 's', 'english' => 'e', 'test_direction' => 'es_to_en',
                    'uses_concepts' => [['type' => 'verb', 'id' => $verb->id, 'tense' => 'infinitive', 'person' => null]],
                    'must_match' => ['tense' => null, 'subject' => null, 'gender' => null], 'status' => CardStatus::Draft,
                ]);

                return 1;
            });
        });

        $result = app(CoverageGenerator::class)->fill();

        // Done means every gap has at least a draft — approval is a separate step.
        $this->assertTrue($result['done']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['awaiting_review']);
    }

    public function test_stops_after_dry_rounds_when_nothing_gets_covered(): void
    {
        $this->infinitiveVerb();

        // Generator that never actually covers anything.
        $this->mock(CardGenerator::class, fn ($m) => $m->shouldReceive('generateForGaps')->andReturn(0));

        $result = app(CoverageGenerator::class)->fill();

        $this->assertFalse($result['done']);
        $this->assertGreaterThan(0, $result['remaining']);
        $this->assertLessThanOrEqual(2, $result['rounds']); // dry-streak cap
    }
}
