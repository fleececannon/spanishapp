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

    public function test_tense_cell_cycles_off_every_person_one_form_off_on_any_verb(): void
    {
        $verb = Verb::first(); // an ordinary Verb Set verb, no drill flag
        $grid = Livewire::test(VerbsGrid::class);

        $grid->call('toggleTense', $verb->id, 'present');          // off -> every person
        $this->assertContains('present', $verb->fresh()->enabled_tenses);
        $this->assertContains('present', $verb->fresh()->full_tenses);
        $this->assertTrue($verb->fresh()->drillsEveryForm('present'));

        $grid->call('toggleTense', $verb->id, 'present');          // every person -> one sampled form
        $this->assertContains('present', $verb->fresh()->enabled_tenses);
        $this->assertNotContains('present', $verb->fresh()->full_tenses);
        $this->assertFalse($verb->fresh()->drillsEveryForm('present'));

        $grid->call('toggleTense', $verb->id, 'present');          // one form -> off
        $this->assertNotContains('present', $verb->fresh()->enabled_tenses);
        $this->assertNotContains('present', $verb->fresh()->full_tenses);
    }

    public function test_infinitive_only_goes_on_and_off(): void
    {
        $verb = Verb::first(); // infinitive already on
        $grid = Livewire::test(VerbsGrid::class);

        $grid->call('toggleTense', $verb->id, 'infinitive');
        $this->assertNotContains('infinitive', $verb->fresh()->enabled_tenses);

        $grid->call('toggleTense', $verb->id, 'infinitive');
        $this->assertContains('infinitive', $verb->fresh()->enabled_tenses);
        $this->assertNotContains('infinitive', $verb->fresh()->full_tenses ?? [], 'the infinitive never drills persons');
    }

    public function test_cells_show_check_for_every_person_and_dash_for_one_form(): void
    {
        $verb = Verb::first();
        $verb->update(['enabled_tenses' => ['infinitive', 'present', 'past'], 'full_tenses' => ['present']]);

        $html = Livewire::test(VerbsGrid::class)->html();

        $this->assertStringContainsString('Every person (5 cards)', $html);      // present
        $this->assertStringContainsString('One sampled form (1 card)', $html);   // past
        $this->assertStringNotContainsString('Drill all', $html);
    }
}
