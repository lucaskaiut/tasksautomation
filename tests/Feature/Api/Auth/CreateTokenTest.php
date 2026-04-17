<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_token_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'secret-password',
        ]);

        $this->postJson('/api/tokens/create', [
            'email' => $user->email,
            'password' => 'secret-password',
            'token_name' => 'worker',
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => ['token', 'token_type'],
                'message',
            ]);
    }

    public function test_cannot_create_token_with_invalid_payload(): void
    {
        $this->postJson('/api/tokens/create', [
            'email' => 'not-an-email',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }
}

