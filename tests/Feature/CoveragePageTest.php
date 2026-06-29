<?php

namespace Tests\Feature;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Livewire\Admin\Coverage;
use App\Models\Card;
use App\Models\User;
use App\Models\Verb;
use App\Services\Claude\CardGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CoveragePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_step_stops_when_already_fully_covered(): void
    {
        $verb = Verb::create([
            'spanish' => 'Caminar', 'english' => 'to walk', 'tag' => 'Verb Set 1',
            'verb_class' => 'AR', 'enabled_tenses' => ['infinitive'], 'drill_all_forms' => false, 'unlocked' => true,
        ]);
        Card::create([
            'source' => CardSource::Ai, 'spanish' => 's', 'english' => 'e', 'test_direction' => 'es_to_en',
            'uses_concepts' => [['type' => 'verb', 'id' => $verb->id, 'tense' => 'infinitive', 'person' => null]],
            'must_match' => ['tense' => null, 'subject' => null, 'gender' => null], 'status' => CardStatus::Active,
        ]);

        // No generation should happen — already covered.
        $this->mock(CardGenerator::class, fn ($m) => $m->shouldNotReceive('generateForGaps'));

        Livewire::test(Coverage::class)
            ->call('start')
            ->assertSet('running', true)
            ->call('step')
            ->assertSet('running', false);
    }

    public function test_step_does_nothing_when_not_running(): void
    {
        $this->mock(CardGenerator::class, fn ($m) => $m->shouldNotReceive('generateForGaps'));

        Livewire::test(Coverage::class)
            ->call('step')
            ->assertSet('running', false);
    }
}
