<?php

namespace Tests\Feature;

use App\Livewire\Admin\MyPractice;
use App\Livewire\Admin\MyVerbs;
use App\Models\MyVerb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class MyPracticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected(): void
    {
        $this->get(route('admin.my-verbs'))->assertRedirect(route('home'));
        $this->get(route('admin.practice'))->assertRedirect(route('home'));
    }

    public function test_the_catalog_ships_preloaded_and_locked(): void
    {
        $this->assertSame(1157, MyVerb::count());
        $this->assertSame(0, MyVerb::where('unlocked', true)->count());
        $this->assertSame(0, MyVerb::where('mastered', true)->count());

        $ser = MyVerb::where('spanish', 'ser')->first();
        $this->assertSame(100, $ser->frequency_score);
    }

    public function test_only_unlocked_unmastered_verbs_enter_the_queue(): void
    {
        $this->actingAs(User::factory()->create());

        MyVerb::where('spanish', 'ser')->update(['unlocked' => true]);
        MyVerb::where('spanish', 'estar')->update(['unlocked' => true, 'mastered' => true]);
        // Everything else stays waiting.

        Livewire::test(MyPractice::class)
            ->assertSet('queue', [])
            ->assertSet('currentId', MyVerb::where('spanish', 'ser')->first()->id);
    }

    public function test_new_verbs_are_served_most_common_first(): void
    {
        $this->actingAs(User::factory()->create());

        MyVerb::whereIn('spanish', ['charlar', 'ser', 'comer'])->update(['unlocked' => true]);

        $component = Livewire::test(MyPractice::class);
        $ids = array_merge([$component->get('currentId')], $component->get('queue'));

        $ordered = MyVerb::whereIn('id', $ids)->get()->sortByDesc('frequency_score')->pluck('id')->values()->all();
        $this->assertSame($ordered, $ids);
    }

    public function test_got_it_schedules_with_the_same_ladder_as_the_kids(): void
    {
        $this->actingAs(User::factory()->create());
        MyVerb::where('spanish', 'ser')->update(['unlocked' => true]);
        $ser = MyVerb::where('spanish', 'ser')->first();

        Livewire::test(MyPractice::class)
            ->call('mark', true)
            ->assertSet('done', true);

        $ser->refresh();
        $this->assertSame(1, $ser->reps);
        $this->assertSame(1, $ser->interval_days);
        $this->assertSame('2.50', (string) $ser->ease);
        $this->assertTrue($ser->due->isSameDay(Carbon::today()->addDay()));
    }

    public function test_missed_it_requeues_and_penalizes(): void
    {
        $this->actingAs(User::factory()->create());
        MyVerb::where('spanish', 'ser')->update(['unlocked' => true]);
        $ser = MyVerb::where('spanish', 'ser')->first();

        Livewire::test(MyPractice::class)
            ->call('mark', false)
            // The only verb, so the miss cycles straight back as current.
            ->assertSet('currentId', $ser->id)
            ->assertSet('done', false);

        $ser->refresh();
        $this->assertSame(1, $ser->lapses);
        $this->assertSame(0, $ser->interval_days);
        $this->assertSame('2.30', (string) $ser->ease);
    }

    public function test_skip_moves_on_without_touching_the_schedule(): void
    {
        $this->actingAs(User::factory()->create());
        MyVerb::where('spanish', 'ser')->update(['unlocked' => true]);

        Livewire::test(MyPractice::class)
            ->call('skip')
            ->assertSet('done', true);

        $ser = MyVerb::where('spanish', 'ser')->first();
        $this->assertSame(0, $ser->reps);
        $this->assertSame(0, $ser->lapses);
        $this->assertNull($ser->due);
    }

    public function test_mastering_a_verb_pulls_it_out_of_the_queue_but_keeps_history(): void
    {
        $this->actingAs(User::factory()->create());

        MyVerb::where('spanish', 'ser')->update([
            'unlocked' => true, 'due' => Carbon::yesterday(), 'interval_days' => 3, 'reps' => 2, 'ease' => 2.5,
        ]);

        Livewire::test(MyVerbs::class)
            ->call('toggleMastered', MyVerb::where('spanish', 'ser')->first()->id);

        Livewire::test(MyPractice::class)->assertSet('done', true);

        $ser = MyVerb::where('spanish', 'ser')->first();
        $this->assertTrue($ser->mastered);
        $this->assertSame(2, $ser->reps, 'history survives mastering');
    }

    public function test_can_add_and_bulk_import_verbs(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(MyVerbs::class)
            ->call('openAdd')
            ->set('newSpanish', 'Madrugar')
            ->set('newEnglish', 'to get up early')
            ->set('newScore', 40)
            ->call('addVerb')
            ->assertHasNoErrors();

        $this->assertNotNull(MyVerb::where('spanish', 'madrugar')->first());

        Livewire::test(MyVerbs::class)
            ->call('openBulk')
            ->set('bulk', "trasnochar | to stay up late | 30\nser | duplicate ignored\nmalformed line")
            ->call('importBulk');

        $this->assertSame(30, MyVerb::where('spanish', 'trasnochar')->first()->frequency_score);
        $this->assertSame('to be', MyVerb::where('spanish', 'ser')->first()->english, 'duplicates are skipped');
        $this->assertSame(1159, MyVerb::count());
    }

    public function test_counts_and_filters(): void
    {
        $this->actingAs(User::factory()->create());

        MyVerb::where('spanish', 'ser')->update(['unlocked' => true, 'due' => Carbon::yesterday()]);
        MyVerb::where('spanish', 'estar')->update(['mastered' => true]);

        Livewire::test(MyVerbs::class)
            ->assertViewHas('known', 1)
            ->assertViewHas('training', 1)
            ->assertViewHas('dueToday', 1)
            ->set('filter', 'known')
            ->assertSee('estar')
            ->assertDontSee('tener —');
    }
}
