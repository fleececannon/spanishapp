<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Verb;
use App\Models\Word;
use App\Services\Vocab\VocabCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintCardsTest extends TestCase
{
    use RefreshDatabase;

    private function makeVocabCards(): void
    {
        $word = Word::create([
            'spanish' => 'fiesta', 'english' => 'party', 'category' => 'noun',
            'role' => 'ingredient', 'unlocked' => false, 'vocab_card' => true,
        ]);
        $verb = Verb::create([
            'spanish' => 'Caminar', 'english' => 'to walk', 'tag' => 'Verb Set 1',
            'verb_class' => 'AR', 'enabled_tenses' => ['infinitive'],
            'drill_all_forms' => false, 'unlocked' => false, 'vocab_card' => true,
        ]);
        $service = app(VocabCardService::class);
        $service->sync($word);
        $service->sync($verb);
    }

    public function test_requires_login(): void
    {
        $this->get('/admin/print-cards')->assertRedirect();
    }

    public function test_words_filter_shows_only_word_vocab_cards(): void
    {
        $this->makeVocabCards();
        $this->actingAs(User::factory()->create());

        $this->get('/admin/print-cards?type=words')
            ->assertOk()
            ->assertSee('fiesta')
            ->assertDontSee('Caminar');
    }

    public function test_verbs_filter_shows_only_verb_vocab_cards(): void
    {
        $this->makeVocabCards();
        $this->actingAs(User::factory()->create());

        $this->get('/admin/print-cards?type=verbs')
            ->assertOk()
            ->assertSee('Caminar')
            ->assertDontSee('fiesta');
    }
}
