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
        $this->get(route('kid.review'))->assertRedirect(route('kid.login'));
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
