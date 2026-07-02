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

    public function test_paste_import_with_nothing_valid_shows_an_error(): void
    {
        Livewire::test(Cards::class)
            ->set('bulkText', "no delimiters here\nnor here")
            ->call('importBulk')
            ->assertHasErrors('bulkText');

        $this->assertSame(0, Card::count());
    }
}
