<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Services\PusherBroadcaster;
use App\Services\WhatsAppProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChatMessageController extends Controller
{
    public function store(Request $request, WhatsAppProvider $provider, PusherBroadcaster $broadcaster): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // Support both numeric ID and session_id string
        $session = ChatSession::query()
            ->where('id', $validated['session_id'])
            ->orWhere('session_id', $validated['session_id'])
            ->first();

        if (! $session) {
            Log::warning('ChatMessage: Session not found', ['session_id' => $validated['session_id']]);
            return response()->json(['message' => 'Session not found.'], 404);
        }

        if (empty($session->whatsapp_group_id) && empty($session->group_jid)) {
            return response()->json(['message' => 'Session has no WhatsApp group.'], 422);
        }

        $groupId = $session->whatsapp_group_id ?: $session->group_jid;

        try {
            $provider->sendGroupMessage($groupId, $validated['message']);
            Log::info('ChatMessage: Message sent to WhatsApp', [
                'session_id' => $session->id,
                'group_id' => $groupId,
                'message' => substr($validated['message'], 0, 50)
            ]);
        } catch (RuntimeException $exception) {
            Log::error('Failed to send WhatsApp message.', [
                'session_id' => $session->session_id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to send message.'], 502);
        }

        if (! empty($session->pusher_channel)) {
            try {
                $broadcaster->broadcastMessage($session->pusher_channel, [
                    'session_id' => $session->session_id,
                    'message' => $validated['message'],
                    'sender' => 'visitor',
                    'timestamp' => now()->toIso8601String(),
                ]);
                Log::info('ChatMessage: Broadcast to Pusher', ['channel' => $session->pusher_channel]);
            } catch (RuntimeException $exception) {
                Log::warning('Failed to broadcast chat message.', [
                    'session_id' => $session->session_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json(['message' => 'Message sent.']);
    }
}
