<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_reach_admin_pages(): void
    {
        $this->get(route('admin.verbs'))->assertRedirect(route('home'));
        $this->get(route('admin.generate'))->assertRedirect(route('home'));
    }

    public function test_admin_can_view_verbs_grid_and_generation(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $this->get(route('admin.verbs'))->assertOk()->assertSee('Verbs grid');
        $this->get(route('admin.generate'))->assertOk()->assertSee('Generate cards');
    }
}
