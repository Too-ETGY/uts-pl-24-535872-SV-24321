<?php

namespace Tests\Feature\Api;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NoteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_tanpa_token_menghasilkan_401(): void
    {
        $this->getJson('/api/notes')->assertStatus(401);
        $this->postJson('/api/notes', ['title' => 'Test', 'content' => 'Content'])->assertStatus(401);
        $this->getJson('/api/notes/1')->assertStatus(401);
        $this->putJson('/api/notes/1', ['title' => 'Test', 'content' => 'Content'])->assertStatus(401);
        $this->deleteJson('/api/notes/1')->assertStatus(401);
    }

    public function test_user_bisa_membuat_note_dan_mendapat_201(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Pothole on Jalan Sudirman',
            'content' => 'Road work has been pending since three budget cycles ago.',
        ];

        $response = $this->postJson('/api/notes', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'user_id',
                'title',
                'content',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'user_id' => $user->id,
                'title' => $payload['title'],
                'content' => $payload['content'],
            ]);

        $this->assertDatabaseHas('notes', [
            'id' => $response->json('id'),
            'user_id' => $user->id,
            'title' => $payload['title'],
        ]);
    }

    public function test_validasi_gagal_menghasilkan_422(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Missing both fields
        $response = $this->postJson('/api/notes', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);

        // Empty/too short fields
        $response = $this->postJson('/api/notes', [
            'title' => 'ab',
            'content' => 'xy',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }

    public function test_crud_lengkap_berjalan(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // 1. Create (Store)
        $createResponse = $this->postJson('/api/notes', [
            'title' => 'Initial satirical entry',
            'content' => 'First paragraph about red tape and form duplicates.',
        ]);
        $createResponse->assertStatus(201);
        $noteId = $createResponse->json('id');

        // 2. Read All (Index)
        $listResponse = $this->getJson('/api/notes');
        $listResponse->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $noteId,
                'title' => 'Initial satirical entry',
            ]);

        // 3. Read Single (Show)
        $showResponse = $this->getJson("/api/notes/{$noteId}");
        $showResponse->assertStatus(200)
            ->assertJson([
                'id' => $noteId,
                'title' => 'Initial satirical entry',
                'content' => 'First paragraph about red tape and form duplicates.',
            ]);

        // 4. Update
        $updateResponse = $this->putJson("/api/notes/{$noteId}", [
            'title' => 'Updated entry with revised complaint',
            'content' => 'Added subsection regarding stamp duty fees.',
        ]);
        $updateResponse->assertStatus(200)
            ->assertJson([
                'id' => $noteId,
                'title' => 'Updated entry with revised complaint',
                'content' => 'Added subsection regarding stamp duty fees.',
            ]);

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'title' => 'Updated entry with revised complaint',
        ]);

        // 5. Delete (Destroy)
        $deleteResponse = $this->deleteJson("/api/notes/{$noteId}");
        $deleteResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Note deleted successfully',
            ]);

        $this->assertDatabaseMissing('notes', [
            'id' => $noteId,
        ]);
    }

    public function test_user_isolation_cannot_view_other_users_note(): void
    {
        $userAlice = User::factory()->create();
        $userBob = User::factory()->create();

        $bobsNote = Note::factory()->create([
            'user_id' => $userBob->id,
            'title' => 'Bobs Private Complaint',
            'content' => 'Content only Bob should see.',
        ]);

        Sanctum::actingAs($userAlice);

        $response = $this->getJson("/api/notes/{$bobsNote->id}");
        $response->assertStatus(403);
    }

    public function test_user_isolation_cannot_update_other_users_note(): void
    {
        $userAlice = User::factory()->create();
        $userBob = User::factory()->create();

        $bobsNote = Note::factory()->create([
            'user_id' => $userBob->id,
            'title' => 'Bobs Original Title',
            'content' => 'Bobs Original Content',
        ]);

        Sanctum::actingAs($userAlice);

        $response = $this->putJson("/api/notes/{$bobsNote->id}", [
            'title' => 'Malicious Overwrite',
            'content' => 'Attempted tamper by Alice',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('notes', [
            'id' => $bobsNote->id,
            'title' => 'Bobs Original Title',
        ]);
    }

    public function test_user_isolation_cannot_delete_other_users_note(): void
    {
        $userAlice = User::factory()->create();
        $userBob = User::factory()->create();

        $bobsNote = Note::factory()->create([
            'user_id' => $userBob->id,
        ]);

        Sanctum::actingAs($userAlice);

        $response = $this->deleteJson("/api/notes/{$bobsNote->id}");
        $response->assertStatus(403);

        $this->assertDatabaseHas('notes', [
            'id' => $bobsNote->id,
        ]);
    }

    public function test_user_list_only_contains_own_notes(): void
    {
        $userAlice = User::factory()->create();
        $userBob = User::factory()->create();

        Note::factory()->count(2)->create(['user_id' => $userBob->id]);
        $alicesNote = Note::factory()->create(['user_id' => $userAlice->id, 'title' => 'Alices Note']);

        Sanctum::actingAs($userAlice);

        $response = $this->getJson('/api/notes');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $alicesNote->id]);
    }
}
