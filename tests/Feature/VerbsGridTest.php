<?php

namespace Tests\Feature;

use App\Enums\VerbClass;
use App\Livewire\Admin\VerbsGrid;
use App\Models\User;
use App\Models\Verb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VerbsGridTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Verb::create([
            'spanish' => 'Hablar', 'english' => 'to speak', 'tag' => 'Key Verbs',
            'verb_class' => 'AR', 'enabled_tenses' => ['infinitive'], 'drill_all_forms' => false, 'unlocked' => true,
        ]);
    }

    public function test_add_verb_into_an_existing_group(): void
    {
        Livewire::test(VerbsGrid::class)
            ->set('newSpanish', 'Correr')
            ->set('newEnglish', 'to run')
            ->set('newGroup', 'Key Verbs')
            ->call('addVerb');

        $verb = Verb::where('spanish', 'Correr')->first();
        $this->assertSame('Key Verbs', $verb->tag);
        $this->assertSame(VerbClass::ER, $verb->verb_class);
        $this->assertSame(['infinitive'], $verb->enabled_tenses);
        $this->assertFalse((bool) $verb->unlocked);
    }

    public function test_add_verb_with_a_brand_new_group(): void
    {
        Livewire::test(VerbsGrid::class)
            ->set('newSpanish', 'Vivir')
            ->set('newEnglish', 'to live')
            ->set('newGroup', '__new__')
            ->set('newGroupName', 'Verb Set 5')
            ->call('addVerb');

        $verb = Verb::where('spanish', 'Vivir')->first();
        $this->assertSame('Verb Set 5', $verb->tag);
        $this->assertSame(VerbClass::IR, $verb->verb_class);
    }

    public function test_new_group_requires_a_name(): void
    {
        Livewire::test(VerbsGrid::class)
            ->set('newSpanish', 'Nadar')
            ->set('newEnglish', 'to swim')
            ->set('newGroup', '__new__')
            ->set('newGroupName', '')
            ->call('addVerb')
            ->assertHasErrors('newGroupName');

        $this->assertNull(Verb::where('spanish', 'Nadar')->first());
    }

    public function test_group_is_required(): void
    {
        Livewire::test(VerbsGrid::class)
            ->set('newSpanish', 'Nadar')
            ->set('newEnglish', 'to swim')
            ->set('newGroup', '')
            ->call('addVerb')
            ->assertHasErrors('newGroup');
    }
}
