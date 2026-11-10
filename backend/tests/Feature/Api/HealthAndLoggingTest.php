<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class HealthAndLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_200_when_healthy(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'services' => [
                    'database' => 'up',
                    'cache' => 'up',
                ],
            ]);
    }

    public function test_validation_failure_logs_warning_without_sensitive_data(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Validation failed on request'
                    && isset($context['failed_fields'])
                    && in_array('email', $context['failed_fields'])
                    && !isset($context['password']);
            });

        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email-format',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422);
    }

    public function test_note_creation_logs_structured_info(): void
    {
        $user = User::factory()->create();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($user) {
                return $message === 'Note created successfully'
                    && $context['user_id'] === $user->id
                    && $context['event'] === 'note_created';
            });

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/notes', [
            'title' => 'Keluhan Jalan Berlubang',
            'content' => 'Jalanan rusak parah mohon perbaikan.',
        ]);

        $response->assertStatus(201);
    }
}
