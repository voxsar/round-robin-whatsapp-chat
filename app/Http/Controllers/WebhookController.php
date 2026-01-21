<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Services\PusherClient;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handleWhatsApp(Request $request, PusherClient $pusher)
    {
        $payload = $request->all();
        $groupJid = $payload['remoteJid']
            ?? $payload['groupId']
            ?? $payload['chatId']
            ?? null;

        if (! $groupJid) {
            return response()->json(['status' => 'ignored']);
        }

        $session = ChatSession::where('group_jid', $groupJid)->first();

        if (! $session) {
            return response()->json(['status' => 'unknown_session']);
        }

        $messageText = $payload['text']
            ?? $payload['message']
            ?? $payload['body']
            ?? '';

        $pusher->trigger("session-{$session->id}", 'message', [
            'message' => [
                'id' => $payload['messageId'] ?? $payload['id'] ?? uniqid('msg_', true),
                'sender' => $payload['sender'] ?? 'group',
                'text' => $messageText,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        return response()->json(['status' => 'ok']);
    }
}
