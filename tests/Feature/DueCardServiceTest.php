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

    public function test_whole_deck_is_available_from_day_one(): void
    {
        $kid = Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => 2]);
        collect(range(1, 5))->each(fn ($n) => $this->makeCard($n));

        $queue = app(DueCardService::class)->queueFor($kid);

        // No pace cap — every new card is in the queue.
        $this->assertCount(5, $queue);
    }

    public function test_due_reviews_and_all_new_cards_are_included(): void
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

        // 1 due + 4 new = 5, and the overdue review sorts first.
        $this->assertCount(5, $queue);
        $this->assertSame($dueCard->id, $queue->first()->id);
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
