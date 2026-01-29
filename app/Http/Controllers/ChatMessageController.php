<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
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

        if ($session->status === 'blocked') {
            return response()->json(['status' => 'ignored', 'reason' => 'chat_not_active']);
        }

        if (empty($session->whatsapp_group_id) && empty($session->group_jid)) {
            return response()->json(['message' => 'Session has no WhatsApp group.'], 422);
        }

        $groupId = $session->whatsapp_group_id ?: $session->group_jid;

        $session->restoreFromEnded();

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

        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'visitor',
            'sender_name' => $session->name,
            'sender_number' => $session->mobile,
            'text' => $validated['message'],
            'source' => 'web',
            'sent_at' => now(),
        ]);

        if (! $session->first_response_at) {
            $session->first_response_at = now();
        }
        $session->last_response_at = now();
        $session->away_sent_at = null;
        $session->save();

        $groupKey = $session->group_id ?: ($session->group_jid ?: $session->whatsapp_group_id);
        $groupChannel = $groupKey ? "group-{$groupKey}" : null;
        $legacyChannel = $session->pusher_channel && $session->pusher_channel !== $groupChannel
            ? $session->pusher_channel
            : null;

        if (! empty($groupChannel) || ! empty($legacyChannel)) {
            try {
                $payload = [
                    'message' => [
                        'id' => (string) $message->id,
                        'sender' => 'visitor',
                        'sender_name' => $session->name,
                        'text' => $validated['message'],
                        'timestamp' => now()->toIso8601String(),
                    ],
                ];

                if (! empty($groupChannel)) {
                    $broadcaster->broadcastMessage($groupChannel, $payload);
                }

                if ($legacyChannel) {
                    $broadcaster->broadcastMessage($legacyChannel, $payload);
                }

                if ($groupChannel && $session->pusher_channel !== $groupChannel) {
                    $session->forceFill(['pusher_channel' => $groupChannel])->save();
                }

                Log::info('ChatMessage: Broadcast to Pusher', ['channel' => $groupChannel]);
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
