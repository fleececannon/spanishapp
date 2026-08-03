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

    public function test_upcoming_chart_buckets_reviews_by_day_for_14_days(): void
    {
        $kid = Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => 5]);

        $state = fn (Card $card, $due) => ReviewState::create([
            'kid_id' => $kid->id, 'card_id' => $card->id, 'due' => $due,
            'interval_days' => 3, 'ease' => 2.5, 'reps' => 1, 'lapses' => 0,
        ]);

        $state($this->makeCard(1), Carbon::today()->addDays(3));
        $state($this->makeCard(2), Carbon::today()->addDays(3));
        $state($this->makeCard(3), Carbon::today()->addDays(14));
        $state($this->makeCard(4), Carbon::today());               // today: not "upcoming"
        $state($this->makeCard(5), Carbon::today()->addDays(15));  // beyond window

        $upcoming = Livewire::actingAs($kid, 'kid')
            ->test(Dashboard::class)
            ->viewData('upcoming');

        $this->assertCount(14, $upcoming);
        $byDate = $upcoming->keyBy('date');
        $this->assertSame(2, $byDate[Carbon::today()->addDays(3)->toDateString()]['count']);
        $this->assertSame(1, $byDate[Carbon::today()->addDays(14)->toDateString()]['count']);
        $this->assertSame(0, $byDate[Carbon::today()->addDays(1)->toDateString()]['count']);
        $this->assertSame(3, $upcoming->sum('count'));
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
