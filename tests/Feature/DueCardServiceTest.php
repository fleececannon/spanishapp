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

    private function makeCard(int $n, CardStatus $status = CardStatus::Active): Card
    {
        return Card::create([
            'source' => CardSource::Ai,
            'spanish' => "Frase {$n}",
            'english' => "Sentence {$n}",
            'test_direction' => 'es_to_en',
            'uses_concepts' => [],
            'must_match' => ['tense' => null, 'subject' => null, 'gender' => null],
            'status' => $status,
        ]);
    }

    public function test_draft_and_retired_cards_are_never_served(): void
    {
        $kid = Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => 5]);

        $this->makeCard(1);
        $this->makeCard(2, CardStatus::Draft);
        $this->makeCard(3, CardStatus::Retired);

        $queue = app(DueCardService::class)->queueFor($kid);

        $this->assertCount(1, $queue);
        $this->assertSame('Frase 1', $queue->first()->spanish);
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

    public function test_due_reviews_always_come_before_new_cards_despite_shuffle(): void
    {
        $kid = Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => 5]);

        $dueIds = collect(range(90, 92))->map(function ($n) use ($kid) {
            $card = $this->makeCard($n);
            ReviewState::create([
                'kid_id' => $kid->id, 'card_id' => $card->id, 'due' => Carbon::yesterday(),
                'interval_days' => 1, 'ease' => 2.5, 'reps' => 1, 'lapses' => 0,
            ]);

            return $card->id;
        });

        collect(range(1, 4))->each(fn ($n) => $this->makeCard($n)); // 4 fresh

        $queue = app(DueCardService::class)->queueFor($kid);

        // The first three slots are exactly the due reviews (in any order).
        $this->assertEqualsCanonicalizing($dueIds->all(), $queue->take(3)->pluck('id')->all());
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
