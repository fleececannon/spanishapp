<?php

namespace Tests\Feature;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Enums\WordRole;
use App\Livewire\Admin\Cards as CardsPage;
use App\Livewire\Admin\Generation;
use App\Livewire\Admin\Patterns as PatternsPage;
use App\Livewire\Admin\Settings as SettingsPage;
use App\Livewire\Admin\WordsLibrary;
use App\Models\Card;
use App\Models\Kid;
use App\Models\Pattern;
use App\Models\ReviewState;
use App\Models\Setting;
use App\Models\User;
use App\Models\Word;
use App\Services\Claude\CardGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_all_admin_pages_render(): void
    {
        foreach (['admin.verbs', 'admin.words', 'admin.patterns', 'admin.cards', 'admin.generate', 'admin.progress', 'admin.settings'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_can_add_and_toggle_a_word(): void
    {
        Livewire::test(WordsLibrary::class)
            ->call('openAdd')
            ->set('wSpanish', 'porque')
            ->set('wEnglish', 'because')
            ->set('wCategory', 'connector')
            ->set('wRole', 'target')
            ->call('save');

        $word = Word::where('spanish', 'porque')->first();
        $this->assertNotNull($word);
        $this->assertSame(WordRole::Target, $word->role);
        $this->assertFalse($word->unlocked);

        Livewire::test(WordsLibrary::class)->call('toggleUnlocked', $word->id);
        $this->assertTrue($word->fresh()->unlocked);

        Livewire::test(WordsLibrary::class)->call('toggleRole', $word->id);
        $this->assertSame(WordRole::Ingredient, $word->fresh()->role);
    }

    public function test_can_toggle_a_pattern(): void
    {
        $pattern = Pattern::create(['name' => 'Negation', 'instruction' => 'You may use no.', 'enabled' => false]);

        Livewire::test(PatternsPage::class)->call('toggle', $pattern->id);

        $this->assertTrue($pattern->fresh()->enabled);
    }

    public function test_can_add_and_retire_a_manual_card(): void
    {
        Livewire::test(CardsPage::class)
            ->call('openAdd')
            ->set('cSpanish', 'Yo tengo un gato')
            ->set('cEnglish', 'I have a cat')
            ->set('mmTense', 'present')
            ->call('save');

        $card = Card::where('spanish', 'Yo tengo un gato')->first();
        $this->assertNotNull($card);
        $this->assertSame(CardSource::Manual, $card->source);
        $this->assertSame('present', $card->must_match['tense']);

        Livewire::test(CardsPage::class)->call('retire', $card->id);
        $this->assertSame(CardStatus::Retired, $card->fresh()->status);
    }

    public function test_settings_save_persists_house_style_and_pace(): void
    {
        $kid = Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => 5]);

        Livewire::test(SettingsPage::class)
            ->set('houseStyle', 'Be kind and forgiving.')
            ->set('startingEase', 2.6)
            ->set('paces', [$kid->id => 9])
            ->call('save');

        $this->assertSame('Be kind and forgiving.', Setting::get('house_style'));
        $this->assertSame(9, $kid->fresh()->daily_new_card_pace);
        $this->assertEqualsWithDelta(2.6, Setting::get('srs_tuning')['starting_ease'], 0.001);
    }

    public function test_refresh_retires_weak_cards_and_backfills(): void
    {
        $kid = Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => 5]);

        $weak = Card::create([
            'source' => CardSource::Ai, 'spanish' => 'frase', 'english' => 'phrase',
            'test_direction' => 'es_to_en', 'uses_concepts' => [],
            'must_match' => ['tense' => null, 'subject' => null, 'gender' => null],
            'status' => CardStatus::Active,
        ]);
        ReviewState::create([
            'kid_id' => $kid->id, 'card_id' => $weak->id, 'due' => now(),
            'interval_days' => 0, 'ease' => 2.0, 'reps' => 0, 'lapses' => 3, // lapses > reps => weak
        ]);

        $this->mock(CardGenerator::class, fn ($m) => $m->shouldReceive('generate')->once()->andReturn(1));

        Livewire::test(Generation::class)->call('refresh');

        $this->assertSame(CardStatus::Retired, $weak->fresh()->status);
    }

    public function test_rebuild_wipes_cards_and_schedules(): void
    {
        $kid = Kid::create(['name' => 'Kai', 'password' => 'x', 'daily_new_card_pace' => 5]);
        $card = Card::create([
            'source' => CardSource::Ai, 'spanish' => 'frase', 'english' => 'phrase',
            'test_direction' => 'es_to_en', 'uses_concepts' => [],
            'must_match' => ['tense' => null, 'subject' => null, 'gender' => null],
            'status' => CardStatus::Active,
        ]);
        ReviewState::create([
            'kid_id' => $kid->id, 'card_id' => $card->id, 'due' => now(),
            'interval_days' => 5, 'ease' => 2.5, 'reps' => 2, 'lapses' => 0,
        ]);

        $this->mock(CardGenerator::class, fn ($m) => $m->shouldReceive('generate')->once()->andReturn(3));

        Livewire::test(Generation::class)->set('count', 3)->call('rebuild');

        $this->assertSame(0, Card::count());
        $this->assertSame(0, ReviewState::count());
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('register'));
    }
}
