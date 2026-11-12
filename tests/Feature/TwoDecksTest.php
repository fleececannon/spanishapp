<?php

namespace Tests\Feature;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Enums\Direction;
use App\Livewire\Admin\Cards;
use App\Livewire\Admin\Generation;
use App\Livewire\Admin\Progress;
use App\Livewire\Dashboard as ParentDashboard;
use App\Livewire\Kid\Dashboard as KidDashboard;
use App\Livewire\Kid\Review;
use App\Models\Card;
use App\Models\Kid;
use App\Models\ReviewState;
use App\Models\User;
use App\Services\Claude\CardGenerator;
use App\Services\Coverage\CoverageService;
use App\Services\Srs\DueCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Spanish → English and English → Spanish are two separate decks: separate
 * cards, separate schedules, separate numbers.
 */
class TwoDecksTest extends TestCase
{
    use RefreshDatabase;

    private function kid(): Kid
    {
        return Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => 5]);
    }

    private function card(Direction $direction, string $spanish, string $english, array $uses = []): Card
    {
        return Card::create([
            'source' => CardSource::Manual,
            'spanish' => $spanish,
            'english' => $english,
            'test_direction' => $direction,
            'uses_concepts' => $uses,
            'must_match' => ['tense' => null, 'subject' => null, 'gender' => null],
            'status' => CardStatus::Active,
        ]);
    }

    private function state(Kid $kid, Card $card, array $attrs = []): ReviewState
    {
        return ReviewState::create(array_merge([
            'kid_id' => $kid->id, 'card_id' => $card->id, 'due' => Carbon::yesterday(),
            'interval_days' => 30, 'ease' => 2.5, 'reps' => 4, 'lapses' => 0,
        ], $attrs));
    }

    public function test_each_queue_only_serves_its_own_deck(): void
    {
        $kid = $this->kid();
        $reading = $this->card(Direction::EsToEn, 'Yo tengo agua', 'I have water');
        $reverse = $this->card(Direction::EnToEs, 'Tengo hambre', 'I am hungry');

        $service = app(DueCardService::class);

        $this->assertSame([$reading->id], $service->queueFor($kid)->pluck('id')->all());
        $this->assertSame([$reverse->id], $service->queueFor($kid, Direction::EnToEs)->pluck('id')->all());
    }

    public function test_english_to_spanish_session_prompts_in_english_and_reveals_spanish(): void
    {
        $kid = $this->kid();
        $this->card(Direction::EsToEn, 'Yo tengo agua', 'I have water');
        $reverse = $this->card(Direction::EnToEs, 'Tengo hambre', 'I am hungry');
        $this->actingAs($kid, 'kid');

        Livewire::withQueryParams(['direction' => 'en_to_es'])
            ->test(Review::class)
            ->assertSet('currentId', $reverse->id)
            ->assertSee('I am hungry')
            ->assertDontSee('Tengo hambre')
            ->assertDontSee('Yo tengo agua')
            ->call('reveal')
            ->assertSee('Tengo hambre')
            ->call('mark', true)
            // The other deck's card never joins this session.
            ->assertSet('done', true);

        $this->assertSame(1, ReviewState::where('card_id', $reverse->id)->value('reps'));
        $this->assertSame(1, ReviewState::count(), 'the Spanish → English card is untouched');
    }

    public function test_a_question_and_reply_card_is_shown_on_separate_lines(): void
    {
        $this->assertSame(['¿Tienes agua?', 'Sí, tengo agua.'], Direction::lines('¿Tienes agua? — Sí, tengo agua.'));
        $this->assertSame(['¿Tienes agua?', 'Sí, tengo agua.'], Direction::lines("¿Tienes agua?\nSí, tengo agua."));
        $this->assertSame(['Tengo hambre'], Direction::lines('Tengo hambre'));
        // A hyphen inside a word or phrase is not a separator.
        $this->assertSame(['Ex-presidente - hoy'], Direction::lines('Ex-presidente - hoy'));

        $kid = $this->kid();
        $this->card(Direction::EnToEs, '¿Tienes agua? — Sí, tengo agua.', 'Do you have water? — Yes, I have water.');
        $this->actingAs($kid, 'kid');

        Livewire::withQueryParams(['direction' => 'en_to_es'])
            ->test(Review::class)
            ->assertSee('Do you have water?')
            ->assertSee('Yes, I have water.')
            ->assertDontSee('Do you have water? — Yes, I have water.')
            ->call('reveal')
            ->assertSee('¿Tienes agua?')
            ->assertSee('Sí, tengo agua.')
            ->assertDontSee('¿Tienes agua? — Sí, tengo agua.');
    }

    public function test_an_unknown_direction_falls_back_to_spanish_to_english(): void
    {
        $kid = $this->kid();
        $reading = $this->card(Direction::EsToEn, 'Yo tengo agua', 'I have water');
        $this->card(Direction::EnToEs, 'Tengo hambre', 'I am hungry');
        $this->actingAs($kid, 'kid');

        Livewire::withQueryParams(['direction' => 'sideways'])
            ->test(Review::class)
            ->assertSet('direction', 'es_to_en')
            ->assertSet('currentId', $reading->id);
    }

    public function test_kid_home_tabs_track_each_deck_on_its_own(): void
    {
        $kid = $this->kid();

        // Spanish → English: one mastered card that is not due, nothing new.
        $this->state($kid, $this->card(Direction::EsToEn, 'Uno', 'One'), ['due' => Carbon::today()->addDays(5)]);

        // English → Spanish: two never-seen cards and one due review.
        $this->card(Direction::EnToEs, 'Dos', 'Two');
        $this->card(Direction::EnToEs, 'Tres', 'Three');
        $this->state($kid, $this->card(Direction::EnToEs, 'Cuatro', 'Four'), ['interval_days' => 2]);

        Livewire::actingAs($kid, 'kid')
            ->test(KidDashboard::class)
            ->assertSee('Spanish → English')
            ->assertSee('English → Spanish')
            ->assertViewHas('total', 1)
            ->assertViewHas('todo', 0)
            ->assertViewHas('mastered', 1)
            ->assertViewHas('tabs', fn ($tabs) => $tabs->pluck('todo', 'value')->all() === ['es_to_en' => 0, 'en_to_es' => 3])
            ->set('tab', 'en_to_es')
            ->assertViewHas('total', 3)
            ->assertViewHas('newCount', 2)
            ->assertViewHas('reviewsDue', 1)
            ->assertViewHas('todo', 3)
            ->assertViewHas('mastered', 0)
            ->call('startLearning')
            ->assertRedirect(route('kid.review', ['direction' => 'en_to_es']));
    }

    public function test_kid_home_says_so_when_a_deck_has_no_cards(): void
    {
        $kid = $this->kid();
        $this->card(Direction::EsToEn, 'Uno', 'One');

        Livewire::actingAs($kid, 'kid')
            ->test(KidDashboard::class)
            ->set('tab', 'en_to_es')
            ->assertSee('No cards here yet')
            ->assertDontSee('Start learning');
    }

    public function test_parent_dashboard_shows_one_deck_at_a_time(): void
    {
        $this->actingAs(User::factory()->create());
        $kid = $this->kid();

        $this->state($kid, $this->card(Direction::EsToEn, 'Uno', 'One'));
        $this->card(Direction::EnToEs, 'Dos', 'Two');
        $this->state($kid, $this->card(Direction::EnToEs, 'Tres', 'Three'), ['lapses' => 2]);

        Livewire::test(ParentDashboard::class)
            ->assertViewHas('totalCards', 1)
            ->assertViewHas('seen', 1)
            ->assertViewHas('concepts', fn ($concepts) => is_array($concepts))
            ->set('deck', 'en_to_es')
            ->assertViewHas('totalCards', 2)
            ->assertViewHas('seen', 1)
            ->assertViewHas('newCount', 1)
            // Verbs and words are only tracked through the Spanish → English deck.
            ->assertViewHas('concepts', null)
            ->assertViewHas('trouble', fn ($trouble) => $trouble->pluck('spanish')->all() === ['Tres']);
    }

    public function test_progress_page_reports_each_deck_separately(): void
    {
        $this->actingAs(User::factory()->create());
        $kid = $this->kid();

        $this->state($kid, $this->card(Direction::EsToEn, 'Uno', 'One'));
        $this->card(Direction::EnToEs, 'Dos', 'Two');

        Livewire::test(Progress::class)->assertViewHas('stats', function ($stats) {
            $decks = $stats->first()['decks']->keyBy(fn ($d) => $d['direction']->value);

            return $decks['es_to_en']['seen'] === 1
                && $decks['es_to_en']['due_now'] === 1
                && $decks['en_to_es']['seen'] === 0
                && $decks['en_to_es']['due_now'] === 1;
        });
    }

    public function test_cards_admin_lists_and_adds_to_the_selected_deck(): void
    {
        $this->actingAs(User::factory()->create());
        $this->card(Direction::EsToEn, 'Vamos al parque', 'We go to the park');
        $this->card(Direction::EnToEs, 'Tengo hambre', 'I am hungry');

        Livewire::test(Cards::class)
            ->assertSee('Vamos al parque')
            ->assertDontSee('Tengo hambre')
            ->set('deck', 'en_to_es')
            ->assertSee('Tengo hambre')
            ->assertDontSee('Vamos al parque')
            ->call('openAdd')
            ->set('cEnglish', 'I am tired')
            ->set('cSpanish', 'Estoy cansado')
            ->call('save');

        $this->assertSame(Direction::EnToEs, Card::where('spanish', 'Estoy cansado')->sole()->test_direction);
    }

    public function test_paste_into_the_english_to_spanish_deck_reads_english_first(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Cards::class)
            ->set('deck', 'en_to_es')
            ->set('bulkText', "I am hungry | Tengo hambre\nShe runs\tElla corre")
            ->call('importBulk');

        $card = Card::where('english', 'I am hungry')->sole();
        $this->assertSame('Tengo hambre', $card->spanish);
        $this->assertSame(Direction::EnToEs, $card->test_direction);
        $this->assertSame(2, Card::direction(Direction::EnToEs)->count());
    }

    public function test_rebuild_leaves_the_english_to_spanish_deck_and_its_progress_alone(): void
    {
        $this->actingAs(User::factory()->create());
        $kid = $this->kid();

        $reading = $this->card(Direction::EsToEn, 'Uno', 'One');
        $this->state($kid, $reading);
        $reverse = $this->card(Direction::EnToEs, 'Dos', 'Two');
        $reverseState = $this->state($kid, $reverse);

        $this->mock(CardGenerator::class, fn ($m) => $m->shouldReceive('generate')->once()->andReturn(0));

        Livewire::test(Generation::class)->call('rebuild');

        $this->assertNull($reading->fresh());
        $this->assertNotNull($reverse->fresh());
        $this->assertNotNull($reverseState->fresh());
        $this->assertSame(1, ReviewState::count());
    }

    public function test_refresh_never_retires_english_to_spanish_cards(): void
    {
        $this->actingAs(User::factory()->create());
        $kid = $this->kid();

        // Missed more often than passed: "weak" by the refresh rule.
        $reverse = $this->card(Direction::EnToEs, 'Dos', 'Two');
        $this->state($kid, $reverse, ['reps' => 0, 'lapses' => 3]);

        $this->mock(CardGenerator::class, fn ($m) => $m->shouldNotReceive('generate'));

        Livewire::test(Generation::class)->call('refresh');

        $this->assertSame(CardStatus::Active, $reverse->fresh()->status);
    }

    public function test_english_to_spanish_cards_do_not_count_toward_coverage(): void
    {
        $uses = [['type' => 'word', 'id' => 7]];
        $this->card(Direction::EnToEs, 'Dos', 'Two', $uses);

        $coverage = app(CoverageService::class);
        $method = new \ReflectionMethod($coverage, 'keysForStatuses');

        $this->assertSame([], $method->invoke($coverage, [CardStatus::Active]));

        $this->card(Direction::EsToEn, 'Uno', 'One', $uses);
        $this->assertSame(['word:7' => true], $method->invoke($coverage, [CardStatus::Active]));
    }
}
