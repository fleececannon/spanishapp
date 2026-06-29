<?php

namespace Tests\Feature;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\Kid;
use App\Models\ReviewState;
use App\Services\Srs\DueCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DueCardServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeCard(int $n): Card
    {
        return Card::create([
            'source' => CardSource::Ai,
            'spanish' => "Frase {$n}",
            'english' => "Sentence {$n}",
            'test_direction' => 'es_to_en',
            'uses_concepts' => [],
            'must_match' => ['tense' => null, 'subject' => null, 'gender' => null],
            'status' => CardStatus::Active,
        ]);
    }

    public function test_new_cards_are_capped_by_pace(): void
    {
        $kid = Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => 2]);
        collect(range(1, 5))->each(fn ($n) => $this->makeCard($n));

        $queue = app(DueCardService::class)->queueFor($kid);

        $this->assertCount(2, $queue);
    }

    public function test_due_cards_are_included_beyond_the_new_cap(): void
    {
        $kid = Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => 2]);

        $dueCard = $this->makeCard(99);
        ReviewState::create([
            'kid_id' => $kid->id,
            'card_id' => $dueCard->id,
            'due' => Carbon::yesterday(),
            'interval_days' => 1,
            'ease' => 2.5,
            'reps' => 1,
            'lapses' => 0,
        ]);

        collect(range(1, 4))->each(fn ($n) => $this->makeCard($n)); // 4 fresh

        $queue = app(DueCardService::class)->queueFor($kid);

        // 1 due + 2 new (capped) = 3
        $this->assertCount(3, $queue);
        $this->assertTrue($queue->contains('id', $dueCard->id));
    }

    public function test_cards_not_yet_due_are_excluded(): void
    {
        $kid = Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => 0]);

        $futureCard = $this->makeCard(7);
        ReviewState::create([
            'kid_id' => $kid->id,
            'card_id' => $futureCard->id,
            'due' => Carbon::tomorrow(),
            'interval_days' => 3,
            'ease' => 2.5,
            'reps' => 2,
            'lapses' => 0,
        ]);

        $queue = app(DueCardService::class)->queueFor($kid);

        $this->assertCount(0, $queue);
    }
}
