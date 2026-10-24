<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials_and_receives_token(): void
    {
        $user = User::factory()->create([
            'email' => 'author@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'author@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email'],
            ])
            ->assertJson([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'email' => 'author@example.com',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_fails_with_invalid_credentials_returns_401(): void
    {
        User::factory()->create([
            'email' => 'author@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'author@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials.',
            ]);
    }

    public function test_login_validation_fails_with_missing_fields_returns_422(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_authenticated_user_can_logout_and_revokes_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logout successful',
            ]);
    }

    public function test_unauthenticated_user_cannot_logout_returns_401(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Citizen Critic',
            'email' => 'citizen@example.com',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => 'Citizen Critic',
                    'email' => 'citizen@example.com',
                ],
            ]);
    }
}
