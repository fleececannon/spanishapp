<?php

namespace Tests\Feature;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Livewire\Admin\Cards;
use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CardsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_paste_import_creates_cards_from_pipe_and_tab_lines(): void
    {
        $paste = "Yo tengo agua | I have water\nElla corre\tShe runs\n\n   \n";

        Livewire::test(Cards::class)
            ->set('bulkText', $paste)
            ->call('importBulk');

        $this->assertSame(2, Card::count());

        $first = Card::where('spanish', 'Yo tengo agua')->first();
        $this->assertSame('I have water', $first->english);
        $this->assertSame(CardSource::Manual, $first->source);
        $this->assertSame(CardStatus::Active, $first->status);
    }

    public function test_paste_import_skips_malformed_lines(): void
    {
        $paste = "Buenas | Good\nthis line has no delimiter\nSolo español | ";

        Livewire::test(Cards::class)
            ->set('bulkText', $paste)
            ->call('importBulk');

        // Only the first line is a valid Spanish|English pair.
        $this->assertSame(1, Card::count());
    }

    private function draftCard(string $spanish = 'Yo tengo agua'): Card
    {
        return Card::create([
            'source' => CardSource::Ai,
            'spanish' => $spanish,
            'english' => 'x',
            'test_direction' => 'es_to_en',
            'uses_concepts' => [],
            'must_match' => ['tense' => null, 'subject' => null, 'gender' => null],
            'status' => CardStatus::Draft,
        ]);
    }

    public function test_approve_moves_a_draft_into_the_active_deck(): void
    {
        $card = $this->draftCard();

        Livewire::test(Cards::class)->call('approve', $card->id);

        $this->assertSame(CardStatus::Active, $card->fresh()->status);
    }

    public function test_approve_all_drafts_activates_every_draft_and_nothing_else(): void
    {
        $this->draftCard('Uno');
        $this->draftCard('Dos');
        $retired = Card::create([
            'source' => CardSource::Ai, 'spanish' => 'Viejo', 'english' => 'x', 'test_direction' => 'es_to_en',
            'uses_concepts' => [], 'must_match' => ['tense' => null, 'subject' => null, 'gender' => null],
            'status' => CardStatus::Retired,
        ]);

        Livewire::test(Cards::class)->call('approveAllDrafts');

        $this->assertSame(0, Card::draft()->count());
        $this->assertSame(2, Card::active()->count());
        $this->assertSame(CardStatus::Retired, $retired->fresh()->status);
    }

    public function test_draft_filter_lists_only_drafts(): void
    {
        $this->draftCard('Borrador');
        Card::create([
            'source' => CardSource::Manual, 'spanish' => 'Activa', 'english' => 'x', 'test_direction' => 'es_to_en',
            'uses_concepts' => [], 'must_match' => ['tense' => null, 'subject' => null, 'gender' => null],
            'status' => CardStatus::Active,
        ]);

        Livewire::test(Cards::class)
            ->set('filter', 'draft')
            ->assertSee('Borrador')
            ->assertDontSee('Activa');
    }

    public function test_paste_import_with_nothing_valid_shows_an_error(): void
    {
        Livewire::test(Cards::class)
            ->set('bulkText', "no delimiters here\nnor here")
            ->call('importBulk')
            ->assertHasErrors('bulkText');

        $this->assertSame(0, Card::count());
    }
}
