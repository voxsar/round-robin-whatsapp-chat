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
            'session_id' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $session = ChatSession::query()
            ->where('session_id', $validated['session_id'])
            ->first();

        if (! $session) {
            return response()->json(['message' => 'Session not found.'], 404);
        }

        if (empty($session->whatsapp_group_id)) {
            return response()->json(['message' => 'Session has no WhatsApp group.'], 422);
        }

        try {
            $provider->sendGroupMessage($session->whatsapp_group_id, $validated['message']);
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
                ]);
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
