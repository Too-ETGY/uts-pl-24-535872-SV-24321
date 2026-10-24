<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * Display a listing of the authenticated user's notes.
     */
    public function index(Request $request): JsonResponse
    {
        $notes = $request->user()
            ->notes()
            ->latest()
            ->get();

        return response()->json($notes, 200);
    }

    /**
     * Store a newly created note for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:3|max:255',
            'content' => 'required|string|min:3',
        ]);

        $note = $request->user()->notes()->create($validated);

        return response()->json($note, 201);
    }

    /**
     * Display the specified note.
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        $note = Note::find($id);

        if (! $note) {
            return response()->json([
                'message' => 'Note not found.',
            ], 404);
        }

        if ($note->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden. This note belongs to another user.',
            ], 403);
        }

        return response()->json($note, 200);
    }

    /**
     * Update the specified note.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $note = Note::find($id);

        if (! $note) {
            return response()->json([
                'message' => 'Note not found.',
            ], 404);
        }

        if ($note->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden. This note belongs to another user.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|min:3|max:255',
            'content' => 'required|string|min:3',
        ]);

        $note->update($validated);

        return response()->json($note, 200);
    }

    /**
     * Remove the specified note.
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $note = Note::find($id);

        if (! $note) {
            return response()->json([
                'message' => 'Note not found.',
            ], 404);
        }

        if ($note->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden. This note belongs to another user.',
            ], 403);
        }

        $note->delete();

        return response()->json([
            'message' => 'Note deleted successfully',
        ], 200);
    }
}
