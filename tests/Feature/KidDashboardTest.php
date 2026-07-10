<?php

namespace Tests\Feature;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Livewire\Kid\Dashboard;
use App\Models\Card;
use App\Models\Kid;
use App\Models\ReviewState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class KidDashboardTest extends TestCase
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

    public function test_guest_is_redirected_from_the_dashboard(): void
    {
        $this->get(route('kid.home'))->assertRedirect(route('home'));
    }

    public function test_dashboard_shows_new_due_and_mastery_counts(): void
    {
        $kid = Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => 5]);

        // 3 brand-new cards.
        collect(range(1, 3))->each(fn ($n) => $this->makeCard($n));

        // 1 due review that is mastered (long interval).
        $due = $this->makeCard(10);
        ReviewState::create([
            'kid_id' => $kid->id, 'card_id' => $due->id, 'due' => Carbon::yesterday(),
            'interval_days' => 30, 'ease' => 2.5, 'reps' => 4, 'lapses' => 0,
        ]);

        Livewire::actingAs($kid, 'kid')
            ->test(Dashboard::class)
            ->assertViewHas('newCount', 3)
            ->assertViewHas('reviewsDue', 1)
            ->assertViewHas('todo', 4)
            ->assertViewHas('mastered', 1)
            ->assertViewHas('total', 4);
    }

    public function test_start_learning_goes_to_the_review_screen(): void
    {
        $kid = Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => 5]);

        Livewire::actingAs($kid, 'kid')
            ->test(Dashboard::class)
            ->call('startLearning')
            ->assertRedirect(route('kid.review'));
    }
}
