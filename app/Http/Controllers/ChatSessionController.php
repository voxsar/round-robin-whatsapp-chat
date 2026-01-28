<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Services\ParticipantSelector;
use App\Services\PusherClient;
use App\Services\WhatsappClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class ChatSessionController extends Controller
{
    public function store(Request $request, WhatsappClient $whatsapp, ParticipantSelector $selector): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:mobile'],
            'mobile' => ['nullable', 'string', 'max:30', 'required_without:email'],
            'instance' => ['nullable', 'string', 'max:255'],
        ]);

        $instance = $validated['instance'] ?? config('services.whatsapp.instance');

        if (! $instance) {
            return response()->json(['message' => 'WhatsApp instance not configured.'], 422);
        }

        $participants = $selector->selectParticipants();
        $subject = 'Chat with ' . $validated['name'];
        $description = trim(implode(' | ', array_filter([
            $validated['email'] ?? null,
            $validated['mobile'] ?? null,
        ])));

        $groupResponse = $whatsapp->createGroup($instance, [
            'subject' => $subject,
            'description' => $description ?: 'Chat session created via POC widget',
            'participants' => $participants,
        ]);

        $groupJid = $groupResponse['remoteJid']
            ?? $groupResponse['jid']
            ?? $groupResponse['id']
            ?? null;

        $session = ChatSession::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'mobile' => $validated['mobile'] ?? null,
            'instance' => $instance,
            'group_jid' => $groupJid,
            'group_subject' => $subject,
            'status' => 'active',
            'pusher_channel' => "session-" . uniqid('chat_', true),
			'session_id' => "session-" . uniqid('chat_', true),
        ]);

        return response()->json([
            'session' => $session,
            'group' => $groupResponse,
            'channel' => $session->pusher_channel,
        ]);
    }

    public function sendMessage(Request $request, WhatsappClient $whatsapp, PusherClient $pusher): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:chat_sessions,id'],
            'text' => ['required', 'string'],
        ]);

        $session = ChatSession::findOrFail($validated['session_id']);

        if (! $session->group_jid) {
            return response()->json(['message' => 'Group not initialized'], 422);
        }
        if (! $session->instance) {
            return response()->json(['message' => 'WhatsApp instance not configured.'], 422);
        }

        $whatsapp->sendText($session->instance, [
            'number' => $session->group_jid,
            'text' => $validated['text'],
        ]);

        $pusher->trigger("session-{$session->id}", 'message', [
            'message' => [
                'id' => (string) Str::uuid(),
                'sender' => 'visitor',
                'text' => $validated['text'],
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        return response()->json(['status' => 'sent']);
    }
}
