<?php

namespace Tests\Feature\Web\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('projects.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}

