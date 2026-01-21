<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:mobile'],
            'mobile' => ['nullable', 'string', 'max:30', 'required_without:email'],
            'group_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $session = ChatSession::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'mobile' => $validated['mobile'] ?? null,
            'status' => 'open',
            'group_id' => $validated['group_id'] ?? null,
        ]);

        return response()->json([
            'id' => $session->id,
            'status' => $session->status,
        ], 201);
    }
}
