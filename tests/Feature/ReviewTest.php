<?php

namespace Tests\Feature;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Enums\ReviewResult;
use App\Livewire\Kid\Review;
use App\Models\Card;
use App\Models\Kid;
use App\Models\ReviewState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function kid(int $pace = 5): Kid
    {
        return Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => $pace]);
    }

    private function card(): Card
    {
        return Card::create([
            'source' => CardSource::Ai,
            'spanish' => 'Yo tengo agua',
            'english' => 'I have water',
            'test_direction' => 'es_to_en',
            'uses_concepts' => [],
            'must_match' => ['tense' => 'present', 'subject' => '1st_singular', 'gender' => null],
            'status' => CardStatus::Active,
        ]);
    }

    public function test_guest_kid_is_redirected_to_kid_login(): void
    {
        $this->get(route('kid.review'))->assertRedirect(route('home'));
    }

    public function test_review_is_spoken_only_with_no_typing_box(): void
    {
        $this->card();
        $this->actingAs($this->kid(), 'kid');

        Livewire::test(Review::class)
            ->assertSee('Yo tengo agua')
            ->assertSee('Show answer')
            ->assertDontSee('I have water')
            ->assertDontSee('Type what it means')
            ->assertDontSee('Check')
            ->call('reveal')
            ->assertSee('I have water')
            ->assertSee('Got it')
            ->assertSee('Missed it');
    }

    public function test_got_it_marks_pass_and_moves_on(): void
    {
        $kid = $this->kid();
        $card = $this->card();
        $this->actingAs($kid, 'kid');

        Livewire::test(Review::class)
            ->call('reveal')
            ->assertSet('revealed', true)
            ->call('mark', true)
            // Straight to the next card (here: done).
            ->assertSet('revealed', false)
            ->assertSet('done', true);

        $state = ReviewState::where('kid_id', $kid->id)->where('card_id', $card->id)->first();
        $this->assertSame(1, $state->reps);
        $this->assertSame(ReviewResult::Pass, $state->last_result);
    }

    public function test_skip_button_is_offered_before_and_after_the_reveal(): void
    {
        $this->card();
        $this->actingAs($this->kid(), 'kid');

        Livewire::test(Review::class)
            ->assertSee('Skip for now')
            ->call('reveal')
            ->assertSee('Skip for now');
    }

    public function test_skip_moves_on_without_recording_anything(): void
    {
        $kid = $this->kid();
        $card = $this->card();
        $this->actingAs($kid, 'kid');

        Livewire::test(Review::class)
            ->assertSet('currentId', $card->id)
            ->call('skip')
            // Only card in the queue, so skipping finishes the session — and
            // crucially it is not requeued the way a miss would be.
            ->assertSet('currentId', null)
            ->assertSet('done', true);

        $this->assertNull(
            ReviewState::where('kid_id', $kid->id)->where('card_id', $card->id)->first(),
            'skipping must not create a review state',
        );
    }

    public function test_skip_leaves_an_existing_schedule_untouched(): void
    {
        $kid = $this->kid();
        $card = $this->card();

        $state = ReviewState::create([
            'kid_id' => $kid->id, 'card_id' => $card->id, 'due' => Carbon::yesterday(),
            'interval_days' => 6, 'ease' => 2.4, 'reps' => 3, 'lapses' => 1,
        ]);

        $this->actingAs($kid, 'kid');
        Livewire::test(Review::class)->call('skip');

        $fresh = $state->fresh();
        $this->assertSame(6, $fresh->interval_days);
        $this->assertSame(3, $fresh->reps);
        $this->assertSame(1, $fresh->lapses, 'skipping must not count as a miss');
        $this->assertSame('2.40', (string) $fresh->ease);
        $this->assertTrue($fresh->due->isSameDay(Carbon::yesterday()), 'still due, so it returns next session');
    }

    public function test_missed_it_requeues_and_penalizes(): void
    {
        $kid = $this->kid();
        $card = $this->card();
        $this->actingAs($kid, 'kid');

        Livewire::test(Review::class)
            ->call('mark', false)
            // Advanced immediately; the missed (only) card cycled back as current.
            ->assertSet('currentId', $card->id)
            ->assertSet('done', false);

        $state = ReviewState::where('kid_id', $kid->id)->where('card_id', $card->id)->first();
        $this->assertSame(1, $state->lapses);
        $this->assertEqualsWithDelta(2.30, (float) $state->ease, 0.001);
    }
}
