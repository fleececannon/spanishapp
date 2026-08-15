<?php

namespace Tests\Feature;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Enums\ReviewResult;
use App\Livewire\Kid\Review;
use App\Models\Card;
use App\Models\Kid;
use App\Models\ReviewState;
use App\Services\Claude\AnswerGrader;
use App\Services\Claude\GradeResult;
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

    private function fakeGrader(ReviewResult $verdict, ?string $nudge = null): void
    {
        $this->mock(AnswerGrader::class, function ($mock) use ($verdict, $nudge) {
            $mock->shouldReceive('grade')
                ->andReturn(new GradeResult($verdict, 'I have water', $nudge));
        });
    }

    public function test_guest_kid_is_redirected_to_kid_login(): void
    {
        $this->get(route('kid.review'))->assertRedirect(route('home'));
    }

    public function test_pass_advances_schedule_and_records_state(): void
    {
        $kid = $this->kid();
        $card = $this->card();
        $this->fakeGrader(ReviewResult::Pass);
        $this->actingAs($kid, 'kid');

        Livewire::test(Review::class)
            ->set('answer', 'i have water')
            ->call('submit')
            ->assertSet('showResult', true)
            ->assertSet('lastPassed', true);

        $state = ReviewState::where('kid_id', $kid->id)->where('card_id', $card->id)->first();
        $this->assertNotNull($state);
        $this->assertSame(1, $state->reps);
        $this->assertSame(ReviewResult::Pass, $state->last_result);
    }

    public function test_out_loud_got_it_marks_pass_without_ai(): void
    {
        $kid = $this->kid();
        $card = $this->card();
        // No grader mock — out-loud must not call the AI.
        $this->mock(AnswerGrader::class, fn ($m) => $m->shouldNotReceive('grade'));
        $this->actingAs($kid, 'kid');

        Livewire::test(Review::class)
            ->set('outLoud', true)
            ->call('reveal')
            ->assertSet('revealed', true)
            ->call('mark', true)
            // No result screen out loud — straight to the next card (here: done).
            ->assertSet('showResult', false)
            ->assertSet('done', true);

        $state = ReviewState::where('kid_id', $kid->id)->where('card_id', $card->id)->first();
        $this->assertSame(1, $state->reps);
        $this->assertSame(ReviewResult::Pass, $state->last_result);
    }

    public function test_skip_button_is_offered_in_both_answer_modes(): void
    {
        $this->kid();
        $this->card();
        $this->actingAs(Kid::first(), 'kid');

        $component = Livewire::test(Review::class)->assertSee('Skip for now');

        $component->set('outLoud', true)->assertSee('Skip for now');

        // ...but not on the result screen, where "Next" is the only way on.
        $this->fakeGrader(ReviewResult::Pass);
        Livewire::test(Review::class)
            ->set('answer', 'I have water')
            ->call('submit')
            ->assertSet('showResult', true)
            ->assertDontSee('Skip for now');
    }

    public function test_skip_moves_on_without_recording_anything(): void
    {
        $kid = $this->kid();
        $card = $this->card();
        $this->mock(AnswerGrader::class, fn ($m) => $m->shouldNotReceive('grade'));
        $this->actingAs($kid, 'kid');

        Livewire::test(Review::class)
            ->assertSet('currentId', $card->id)
            ->call('skip')
            // Only card in the queue, so skipping finishes the session — and
            // crucially it is not requeued the way a miss would be.
            ->assertSet('showResult', false)
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
        $this->mock(AnswerGrader::class, fn ($m) => $m->shouldNotReceive('grade'));

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

    public function test_out_loud_missed_it_requeues_and_penalizes(): void
    {
        $kid = $this->kid();
        $card = $this->card();
        $this->mock(AnswerGrader::class, fn ($m) => $m->shouldNotReceive('grade'));
        $this->actingAs($kid, 'kid');

        $component = Livewire::test(Review::class)
            ->set('outLoud', true)
            ->call('mark', false)
            // Advanced immediately; the missed (only) card cycled back as current.
            ->assertSet('showResult', false)
            ->assertSet('currentId', $card->id)
            ->assertSet('done', false);

        $state = ReviewState::where('kid_id', $kid->id)->where('card_id', $card->id)->first();
        $this->assertSame(1, $state->lapses);
    }

    public function test_miss_requeues_card_and_penalizes_ease(): void
    {
        $kid = $this->kid(pace: 1);
        $card = $this->card();
        $this->fakeGrader(ReviewResult::NeedsWork, 'Close — this one is present tense.');
        $this->actingAs($kid, 'kid');

        $component = Livewire::test(Review::class)
            ->set('answer', 'i had water')
            ->call('submit')
            ->assertSet('lastPassed', false)
            ->assertSet('nudge', 'Close — this one is present tense.');

        // The missed card was re-appended to the in-session queue.
        $this->assertContains($card->id, $component->get('queue'));

        $state = ReviewState::where('kid_id', $kid->id)->where('card_id', $card->id)->first();
        $this->assertSame(1, $state->lapses);
        $this->assertEqualsWithDelta(2.30, (float) $state->ease, 0.001);
    }
}
