<?php

namespace Tests\Feature;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Livewire\Admin\VerbsGrid;
use App\Livewire\Admin\WordsLibrary;
use App\Models\Card;
use App\Models\User;
use App\Models\Verb;
use App\Models\Word;
use App\Services\Coverage\CoverageService;
use App\Services\Vocab\VocabCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VocabCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function makeWord(bool $vocab = false): Word
    {
        return Word::create([
            'spanish' => 'fiesta', 'english' => 'party', 'category' => 'noun',
            'role' => 'ingredient', 'unlocked' => false, 'vocab_card' => $vocab,
        ]);
    }

    public function test_adding_a_word_with_vocab_checked_creates_a_card(): void
    {
        Livewire::test(WordsLibrary::class)
            ->call('openAdd')
            ->set('wSpanish', 'beso')
            ->set('wEnglish', 'kiss')
            ->set('wCategory', 'noun')
            ->set('wRole', 'ingredient')
            ->set('wVocab', true)
            ->call('save');

        $card = Card::sole();
        $this->assertSame('beso', $card->spanish);
        $this->assertSame('kiss', $card->english);
        $this->assertSame(CardSource::Vocab, $card->source);
        $this->assertSame(CardStatus::Active, $card->status);
        $this->assertSame('word', $card->uses_concepts[0]['type']);
    }

    public function test_adding_a_word_without_vocab_checked_creates_no_card(): void
    {
        Livewire::test(WordsLibrary::class)
            ->call('openAdd')
            ->set('wSpanish', 'beso')
            ->set('wEnglish', 'kiss')
            ->call('save');

        $this->assertSame(0, Card::count());
    }

    public function test_unchecking_retires_and_rechecking_reactivates_the_same_card(): void
    {
        $word = $this->makeWord();

        Livewire::test(WordsLibrary::class)->call('toggleVocab', $word->id);
        $card = Card::sole();
        $this->assertSame(CardStatus::Active, $card->status);

        Livewire::test(WordsLibrary::class)->call('toggleVocab', $word->id);
        $this->assertSame(CardStatus::Retired, $card->fresh()->status);

        Livewire::test(WordsLibrary::class)->call('toggleVocab', $word->id);
        $this->assertSame(CardStatus::Active, $card->fresh()->status);
        $this->assertSame(1, Card::count());
    }

    public function test_editing_a_word_updates_its_vocab_card_text(): void
    {
        $word = $this->makeWord(vocab: true);
        app(VocabCardService::class)->sync($word);

        Livewire::test(WordsLibrary::class)
            ->call('openEdit', $word->id)
            ->set('wEnglish', 'celebration')
            ->call('save');

        $this->assertSame('celebration', Card::sole()->english);
    }

    public function test_deleting_a_word_deletes_its_vocab_card(): void
    {
        $word = $this->makeWord(vocab: true);
        app(VocabCardService::class)->sync($word);

        Livewire::test(WordsLibrary::class)->call('delete', $word->id);

        $this->assertSame(0, Card::count());
    }

    public function test_verb_vocab_toggle_creates_an_infinitive_card(): void
    {
        $verb = Verb::create([
            'spanish' => 'Caminar', 'english' => 'to walk', 'tag' => 'Verb Set 1',
            'verb_class' => 'AR', 'enabled_tenses' => ['infinitive'],
            'drill_all_forms' => false, 'unlocked' => false,
        ]);

        Livewire::test(VerbsGrid::class)->call('toggleVocab', $verb->id);

        $card = Card::sole();
        $this->assertSame('Caminar', $card->spanish);
        $this->assertSame(CardSource::Vocab, $card->source);
        $this->assertSame('verb', $card->uses_concepts[0]['type']);
        $this->assertSame('infinitive', $card->uses_concepts[0]['tense']);
    }

    public function test_adding_a_verb_with_vocab_checked_creates_a_card(): void
    {
        Livewire::test(VerbsGrid::class)
            ->set('newSpanish', 'Bailar')
            ->set('newEnglish', 'to dance')
            ->set('newGroup', '__new__')
            ->set('newGroupName', 'Verb Set 9')
            ->set('newVocab', true)
            ->call('addVerb');

        $this->assertSame(1, Card::where('source', CardSource::Vocab->value)->count());
    }

    public function test_vocab_cards_do_not_count_toward_coverage(): void
    {
        $word = Word::create([
            'spanish' => 'porque', 'english' => 'because', 'category' => 'connector',
            'role' => 'target', 'unlocked' => true, 'vocab_card' => true,
        ]);
        app(VocabCardService::class)->sync($word);

        $covered = app(CoverageService::class)->coveredKeys();

        $this->assertArrayNotHasKey("word:{$word->id}", $covered);
    }

    public function test_resync_all_recreates_cards_after_a_wipe(): void
    {
        $word = $this->makeWord(vocab: true);
        $service = app(VocabCardService::class);
        $service->sync($word);

        Card::query()->delete();
        $service->resyncAll();

        $this->assertSame(1, Card::where('source', CardSource::Vocab->value)->count());
    }
}
