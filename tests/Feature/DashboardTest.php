<?php

namespace Tests\Feature;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Livewire\Dashboard;
use App\Models\Card;
use App\Models\Kid;
use App\Models\ReviewState;
use App\Models\User;
use App\Models\Verb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('home'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    private function makeCard(string $spanish, array $uses = [], CardStatus $status = CardStatus::Active): Card
    {
        return Card::create([
            'source' => CardSource::Ai, 'spanish' => $spanish, 'english' => 'x',
            'test_direction' => 'es_to_en', 'uses_concepts' => $uses,
            'must_match' => ['tense' => null, 'subject' => null, 'gender' => null],
            'status' => $status,
        ]);
    }

    private function state(Kid $kid, Card $card, array $attrs = []): ReviewState
    {
        return ReviewState::create(array_merge([
            'kid_id' => $kid->id, 'card_id' => $card->id, 'due' => Carbon::tomorrow(),
            'interval_days' => 1, 'ease' => 2.5, 'reps' => 1, 'lapses' => 0,
        ], $attrs));
    }

    public function test_shows_per_kid_metrics_and_switches_kids(): void
    {
        $this->actingAs(User::factory()->create());
        $ana = Kid::create(['name' => 'Ana', 'password' => 'x', 'daily_new_card_pace' => 2]);
        $ben = Kid::create(['name' => 'Ben', 'password' => 'x', 'daily_new_card_pace' => 2]);

        $card = $this->makeCard('Yo tengo agua');
        $this->state($ana, $card, ['due' => Carbon::yesterday()]); // due for Ana only

        Livewire::test(Dashboard::class, ['kid' => $ana->id])
            ->assertSee('due today')
            ->assertSet('kid', $ana->id)
            ->set('kid', $ben->id)
            ->assertSet('kid', $ben->id);
    }

    public function test_concept_is_mastered_only_when_every_seen_card_using_it_is_mastered(): void
    {
        $this->actingAs(User::factory()->create());
        $kid = Kid::create(['name' => 'Ana', 'password' => 'x', 'daily_new_card_pace' => 2]);

        $verb = Verb::create([
            'spanish' => 'Tener', 'english' => 'to have', 'tag' => 'Key Verbs',
            'verb_class' => 'ER', 'enabled_tenses' => ['present'],
            'drill_all_forms' => false, 'unlocked' => true,
        ]);
        $use = [['type' => 'verb', 'id' => $verb->id, 'tense' => 'present', 'person' => '1st_singular']];

        $mastered = $this->makeCard('Tengo agua', $use);
        $this->state($kid, $mastered, ['interval_days' => 10]);

        $component = Livewire::test(Dashboard::class, ['kid' => $kid->id]);
        $this->assertContains('Tener', $component->viewData('concepts')['verbs']['mastered']);

        // A second, struggling card with the same verb pulls it back to "learning".
        $struggling = $this->makeCard('No tengo pan', $use);
        $this->state($kid, $struggling, ['interval_days' => 1]);

        $component = Livewire::test(Dashboard::class, ['kid' => $kid->id]);
        $this->assertContains('Tener', $component->viewData('concepts')['verbs']['learning']);
        $this->assertNotContains('Tener', $component->viewData('concepts')['verbs']['mastered']);
    }

    public function test_draft_cards_are_invisible_to_dashboard_metrics(): void
    {
        $this->actingAs(User::factory()->create());
        $kid = Kid::create(['name' => 'Ana', 'password' => 'x', 'daily_new_card_pace' => 2]);

        $this->makeCard('Activa');
        $this->makeCard('Borrador', status: CardStatus::Draft);

        Livewire::test(Dashboard::class, ['kid' => $kid->id])
            ->assertViewHas('totalCards', 1)
            ->assertViewHas('newCount', 1);
    }

    public function test_me_tab_shows_the_parents_verb_project(): void
    {
        $this->actingAs(User::factory()->create());
        Kid::create(['name' => 'Ana', 'password' => 'x', 'daily_new_card_pace' => 2]);

        \App\Models\MyVerb::where('spanish', 'ser')->update(['mastered' => true]);
        \App\Models\MyVerb::where('spanish', 'estar')->update([
            'unlocked' => true, 'due' => Carbon::yesterday(), 'interval_days' => 1,
            'ease' => 2.5, 'reps' => 3, 'lapses' => 1,
        ]);

        Livewire::test(Dashboard::class, ['kid' => 'me'])
            ->assertSet('kid', 'me')
            ->assertViewHas('mode', 'me')
            ->assertViewHas('myKnown', 1)
            ->assertViewHas('myTraining', 1)
            ->assertViewHas('myDueToday', 1)
            ->assertViewHas('myAccuracy', 75)
            ->assertSee('verbs known')
            ->assertSee('estar'); // trickiest list

        // Switching back to a kid restores the kid metrics.
        Livewire::test(Dashboard::class, ['kid' => 'me'])
            ->set('kid', (string) Kid::first()->id)
            ->assertViewHas('mode', 'kid')
            ->assertViewHas('totalCards', 0);
    }

    public function test_history_on_retired_cards_does_not_inflate_the_metrics(): void
    {
        $this->actingAs(User::factory()->create());
        $kid = Kid::create(['name' => 'Ana', 'password' => 'x', 'daily_new_card_pace' => 2]);

        // One live card the kid has never seen.
        $this->makeCard('Activa');

        // A card the kid drilled to mastery that has since been retired — its
        // review history must not count as due, seen, or mastered any more.
        $retired = $this->makeCard('Retirada', status: CardStatus::Retired);
        $this->state($kid, $retired, [
            'due' => Carbon::yesterday(),
            'interval_days' => 30,
            'reps' => 6,
            'lapses' => 2,
        ]);

        Livewire::test(Dashboard::class, ['kid' => $kid->id])
            ->assertViewHas('dueToday', 0)
            ->assertViewHas('seen', 0)
            ->assertViewHas('masteredCards', 0)
            ->assertViewHas('accuracy', null)
            ->assertViewHas('totalCards', 1)
            ->assertViewHas('newCount', 1);
    }
}
