<?php

namespace Tests\Feature;

use App\Livewire\Admin\Lessons;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LessonsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_lessons_pages(): void
    {
        $lesson = Lesson::first();

        $this->get(route('admin.lessons'))->assertRedirect(route('home'));
        $this->get(route('admin.lessons.show', $lesson))->assertRedirect(route('home'));
        $this->get(route('admin.lessons.print', $lesson))->assertRedirect(route('home'));
    }

    public function test_lesson_one_ships_with_the_migration(): void
    {
        $lesson = Lesson::where('position', 1)->first();

        $this->assertNotNull($lesson);
        $this->assertSame('¿Quieres comer?', $lesson->title);
        $this->assertStringContainsString('¿Tienes hambre?', $lesson->body);
    }

    public function test_catalog_lists_lessons_in_order(): void
    {
        $this->actingAs(User::factory()->create());
        Lesson::create(['position' => 2, 'title' => 'La familia', 'body' => '<p>x</p>']);

        Livewire::test(Lessons::class)
            ->assertSeeInOrder(['¿Quieres comer?', 'La familia']);
    }

    public function test_lesson_page_renders_the_sheet(): void
    {
        $this->actingAs(User::factory()->create());
        $lesson = Lesson::where('position', 1)->first();

        $this->get(route('admin.lessons.show', $lesson))
            ->assertOk()
            ->assertSee('Lección 1', false)
            ->assertSee('¿Tienes hambre?', false);
    }

    public function test_print_view_renders_standalone(): void
    {
        $this->actingAs(User::factory()->create());
        $lesson = Lesson::where('position', 1)->first();

        $this->get(route('admin.lessons.print', $lesson))
            ->assertOk()
            ->assertSee('window.print()', false)
            ->assertSee('¿Quieres comer?', false);
    }

    public function test_can_add_a_lesson_and_position_defaults_to_next(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Lessons::class)
            ->call('openAdd')
            ->assertSet('position', 2)
            ->set('title', 'La familia')
            ->set('subtitle', 'Who is who, with ser')
            ->set('minutes', 20)
            ->set('body', '<h2>Warm-up</h2>')
            ->call('save')
            ->assertHasNoErrors();

        $lesson = Lesson::where('position', 2)->first();
        $this->assertSame('La familia', $lesson->title);
        $this->assertSame(20, $lesson->minutes);
    }

    public function test_duplicate_position_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Lessons::class)
            ->call('openAdd')
            ->set('position', 1) // taken by lesson 1
            ->set('title', 'Choque')
            ->set('body', '<p>x</p>')
            ->call('save')
            ->assertHasErrors(['position']);
    }

    public function test_can_edit_and_delete_a_lesson(): void
    {
        $this->actingAs(User::factory()->create());
        $lesson = Lesson::where('position', 1)->first();

        Livewire::test(Lessons::class)
            ->call('openEdit', $lesson->id)
            ->assertSet('title', '¿Quieres comer?')
            ->set('title', '¿Quieres comer algo?')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('¿Quieres comer algo?', $lesson->fresh()->title);

        Livewire::test(Lessons::class)->call('delete', $lesson->id);
        $this->assertNull($lesson->fresh());
    }
}
