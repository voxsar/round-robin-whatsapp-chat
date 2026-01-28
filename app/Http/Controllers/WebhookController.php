<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Services\PusherClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleWhatsApp(Request $request, PusherClient $pusher)
    {
        $payload = $request->all();
        
        // Log incoming webhook for debugging
        Log::info('WhatsApp Webhook Received', ['payload' => $payload]);

        // Handle array wrapper (when webhook data comes as array)
        if (is_array($payload) && isset($payload[0]['body'])) {
            $payload = $payload[0]['body'];
        }

        // Extract event type
        $event = $payload['event'] ?? null;
        
        // Only process message upsert events OR send.message
		switch($event) {
            case 'messages.upsert':
            case 'send.message':
                break;
            default:
                return response()->json(['status' => 'ignored', 'reason' => 'not_message_event', 'event' => $event]);
        }

        // Extract data from the webhook payload
        $data = $payload['data'] ?? null;
        
        if (!$data) {
            return response()->json(['status' => 'ignored', 'reason' => 'no_data']);
        }

        // Extract group JID (remoteJid)
        $groupJid = $data['key']['remoteJid'] ?? null;
        
        if (!$groupJid) {
            return response()->json(['status' => 'ignored', 'reason' => 'no_group_jid']);
        }

        // Find active chat session by group JID
        $session = ChatSession::where('group_jid', $groupJid)
            ->where('status', 'active')
            ->first();

        if (!$session) {
            Log::warning('WhatsApp Webhook: Session not found', ['group_jid' => $groupJid]);
            return response()->json(['status' => 'unknown_session', 'group_jid' => $groupJid]);
        }
        
        Log::info('WhatsApp Webhook: Session found', ['session_id' => $session->id, 'group_jid' => $groupJid]);

        // Extract message text
        $messageText = $data['message']['conversation'] 
            ?? $data['message']['extendedTextMessage']['text']
            ?? $data['message']['imageMessage']['caption']
            ?? $data['message']['videoMessage']['caption']
            ?? null;

        if (!$messageText) {
            return response()->json(['status' => 'ignored', 'reason' => 'no_text_content']);
        }

        // Check if message is from the bot itself
        $fromMe = $data['key']['fromMe'] ?? false;
        
        if ($fromMe) {
            return response()->json(['status' => 'ignored', 'reason' => 'from_me']);
        }

        // Extract sender information
        $sender = $payload['sender'] ?? 'unknown';
        $pushName = $data['pushName'] ?? 'User';
        $messageId = $data['key']['id'] ?? uniqid('msg_', true);
        $timestamp = $data['messageTimestamp'] ?? time();

        // Broadcast to Pusher channel
        $channel = "session-{$session->id}";
        $pusherPayload = [
            'message' => [
                'id' => $messageId,
                'sender' => 'agent',
                'sender_name' => $pushName,
                'sender_number' => $sender,
                'text' => $messageText,
                'timestamp' => date('c', $timestamp),
            ],
        ];
        
        Log::info('WhatsApp Webhook: Broadcasting to Pusher', [
            'channel' => $channel,
            'message' => $messageText,
            'sender' => $pushName
        ]);
        
        $pusher->trigger($channel, 'message', $pusherPayload);

        // Update session last activity
        $session->touch();

        Log::info('WhatsApp Webhook: Success', ['session_id' => $session->id]);

        return response()->json(['status' => 'ok', 'session_id' => $session->id]);
    }
}
