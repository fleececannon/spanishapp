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

    public function test_tense_cell_cycles_off_on_sampled_off_on_a_drill_verb(): void
    {
        $verb = Verb::first();
        $verb->update(['drill_all_forms' => true]);

        $grid = Livewire::test(VerbsGrid::class);

        $grid->call('toggleTense', $verb->id, 'present');          // off -> on
        $this->assertContains('present', $verb->fresh()->enabled_tenses);
        $this->assertNotContains('present', $verb->fresh()->sample_tenses ?? []);

        $grid->call('toggleTense', $verb->id, 'present');          // on -> sampled
        $this->assertContains('present', $verb->fresh()->enabled_tenses);
        $this->assertContains('present', $verb->fresh()->sample_tenses);

        $grid->call('toggleTense', $verb->id, 'present');          // sampled -> off
        $this->assertNotContains('present', $verb->fresh()->enabled_tenses);
        $this->assertNotContains('present', $verb->fresh()->sample_tenses ?? []);
    }

    public function test_tense_cell_is_a_plain_on_off_without_drill_all(): void
    {
        $verb = Verb::first(); // drill_all_forms = false

        $grid = Livewire::test(VerbsGrid::class);
        $grid->call('toggleTense', $verb->id, 'present');
        $this->assertContains('present', $verb->fresh()->enabled_tenses);

        $grid->call('toggleTense', $verb->id, 'present');          // straight back off, no dash stop
        $this->assertNotContains('present', $verb->fresh()->enabled_tenses);
        $this->assertEmpty($verb->fresh()->sample_tenses ?? []);
    }

    public function test_infinitive_never_gets_the_dash_state(): void
    {
        $verb = Verb::first();
        $verb->update(['drill_all_forms' => true, 'enabled_tenses' => ['infinitive']]);

        Livewire::test(VerbsGrid::class)->call('toggleTense', $verb->id, 'infinitive'); // on -> off (no sampled stop)
        $this->assertNotContains('infinitive', $verb->fresh()->enabled_tenses);
    }

    public function test_turning_drill_all_off_clears_sampled_tenses(): void
    {
        $verb = Verb::first();
        $verb->update(['drill_all_forms' => true, 'enabled_tenses' => ['present'], 'sample_tenses' => ['present']]);

        Livewire::test(VerbsGrid::class)->call('toggleDrill', $verb->id);

        $this->assertFalse($verb->fresh()->drill_all_forms);
        $this->assertSame([], $verb->fresh()->sample_tenses);
        $this->assertContains('present', $verb->fresh()->enabled_tenses, 'the tense itself stays on');
    }
}
