<?php

namespace Tests\Unit;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Enums\ReviewResult;
use App\Models\Card;
use App\Models\Kid;
use App\Models\ReviewState;
use App\Services\Srs\Scheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    private function makeState(array $overrides = []): ReviewState
    {
        $kid = Kid::create(['name' => 'Tester', 'password' => 'secret', 'daily_new_card_pace' => 5]);
        $card = Card::create([
            'source' => CardSource::Ai,
            'spanish' => 'Yo tengo agua',
            'english' => 'I have water',
            'test_direction' => 'es_to_en',
            'uses_concepts' => [],
            'must_match' => ['tense' => 'present', 'subject' => '1st_singular', 'gender' => null],
            'status' => CardStatus::Active,
        ]);

        return ReviewState::create(array_merge([
            'kid_id' => $kid->id,
            'card_id' => $card->id,
            'due' => Carbon::today(),
            'interval_days' => 0,
            'ease' => 2.50,
            'reps' => 0,
            'lapses' => 0,
        ], $overrides));
    }

    public function test_first_pass_on_new_card_sets_interval_to_one(): void
    {
        $state = $this->makeState(['reps' => 0, 'interval_days' => 0]);

        app(Scheduler::class)->apply($state, ReviewResult::Pass);

        $this->assertSame(1, $state->interval_days);
        $this->assertSame(1, $state->reps);
        $this->assertEquals(Carbon::today()->addDay()->toDateString(), $state->due->toDateString());
        $this->assertSame(ReviewResult::Pass, $state->last_result);
    }

    public function test_subsequent_pass_grows_interval_by_ease(): void
    {
        $state = $this->makeState(['reps' => 2, 'interval_days' => 6, 'ease' => 2.50]);

        app(Scheduler::class)->apply($state, ReviewResult::Pass);

        // 6 * 2.5 = 15
        $this->assertSame(15, $state->interval_days);
        $this->assertSame(3, $state->reps);
    }

    public function test_miss_resets_to_today_and_penalizes_ease(): void
    {
        $state = $this->makeState(['reps' => 3, 'interval_days' => 12, 'ease' => 2.50, 'lapses' => 0]);

        app(Scheduler::class)->apply($state, ReviewResult::NeedsWork);

        $this->assertSame(0, $state->interval_days);
        $this->assertEquals(Carbon::today()->toDateString(), $state->due->toDateString());
        $this->assertEqualsWithDelta(2.30, (float) $state->ease, 0.001);
        $this->assertSame(1, $state->lapses);
        $this->assertSame(ReviewResult::NeedsWork, $state->last_result);
    }

    public function test_ease_never_drops_below_minimum(): void
    {
        $state = $this->makeState(['ease' => 1.35]);

        app(Scheduler::class)->apply($state, ReviewResult::NeedsWork);

        $this->assertEqualsWithDelta(1.30, (float) $state->ease, 0.001);
    }
}
