<?php

namespace Tests\Feature;

use App\Livewire\Landing;
use App\Models\Kid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_is_the_public_front_door(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('Amani Spanish');
    }

    public function test_kid_can_sign_in_from_the_landing(): void
    {
        Kid::create(['name' => 'Kai', 'password' => 'secret', 'daily_new_card_pace' => 5]);

        Livewire::test(Landing::class)
            ->set('name', 'Kai')
            ->set('password', 'secret')
            ->call('login')
            ->assertRedirect(route('kid.review'));

        $this->assertTrue(auth('kid')->check());
    }

    public function test_wrong_kid_password_is_rejected(): void
    {
        Kid::create(['name' => 'Kai', 'password' => 'secret', 'daily_new_card_pace' => 5]);

        Livewire::test(Landing::class)
            ->set('name', 'Kai')
            ->set('password', 'nope')
            ->call('login')
            ->assertHasErrors('password');

        $this->assertFalse(auth('kid')->check());
    }

    public function test_signed_in_admin_is_sent_to_dashboard(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(Landing::class)
            ->assertRedirect(route('dashboard'));
    }
}
