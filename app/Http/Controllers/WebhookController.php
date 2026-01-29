<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Services\PusherClient;
use App\Services\WhatsappClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            ->whereIn('status', ['active', 'ended'])
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

        if ($session->status === 'blocked') {
            return response()->json(['status' => 'ignored', 'reason' => 'session_inactive']);
        }

        $trimmedMessage = trim($messageText);
        if (str_starts_with($trimmedMessage, '/')) {
            $command = strtolower(strtok($trimmedMessage, ' '));

            if ($command === '/endchat') {
                $endText = ChatSetting::current()->end_message
                    ?? config('chat.end_message', 'Chat ended due to inactivity.');

                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'sender' => 'system',
                    'text' => $endText,
                    'source' => 'whatsapp',
                    'sent_at' => now(),
                ]);

                $session->status = 'ended';
                $session->ended_at = now();
                $session->save();

                $channelKey = $session->group_id ?: $session->group_jid;
                $channel = $channelKey ? "group-{$channelKey}" : $session->pusher_channel;
                if ($channel) {
                    $pusher->trigger($channel, 'message', [
                        'message' => [
                            'id' => (string) Str::uuid(),
                            'sender' => 'system',
                            'text' => $endText,
                            'timestamp' => now()->toIso8601String(),
                        ],
                    ]);
                }

                $this->sendGroupMessage($session, $endText);

                return response()->json(['status' => 'ended']);
            }

            if ($command === '/block') {
                $blockText = 'Chat blocked by agent.';

                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'sender' => 'system',
                    'text' => $blockText,
                    'source' => 'whatsapp',
                    'sent_at' => now(),
                ]);

                $session->status = 'blocked';
                $session->save();

                $channelKey = $session->group_id ?: $session->group_jid;
                $channel = $channelKey ? "group-{$channelKey}" : $session->pusher_channel;
                if ($channel) {
                    $pusher->trigger($channel, 'message', [
                        'message' => [
                            'id' => (string) Str::uuid(),
                            'sender' => 'system',
                            'text' => $blockText,
                            'timestamp' => now()->toIso8601String(),
                        ],
                    ]);
                }

                return response()->json(['status' => 'blocked']);
            }

            return response()->json(['status' => 'ignored', 'reason' => 'command']);
        }

        // Check if message is from the bot itself
        $fromMe = $data['key']['fromMe'] ?? false;
        
        if ($fromMe) {
            return response()->json(['status' => 'ignored', 'reason' => 'from_me']);
        }

        $session->restoreFromEnded();

        // Extract sender information
        $sender = $payload['sender'] ?? 'unknown';
        $pushName = $data['pushName'] ?? 'User';
        $messageId = $data['key']['id'] ?? uniqid('msg_', true);
        $timestamp = $data['messageTimestamp'] ?? time();

        // Broadcast to group-based channel so all clients with the same group_id receive it
        $groupKey = $session->group_id ?: $session->group_jid;
        $groupChannel = $groupKey ? "group-{$groupKey}" : null;
        $legacyChannel = $session->pusher_channel && $session->pusher_channel !== $groupChannel
            ? $session->pusher_channel
            : null;
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
            'channel' => $groupChannel,
            'message' => $messageText,
            'sender' => $pushName
        ]);
        
        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'agent',
            'sender_name' => $pushName,
            'sender_number' => $sender,
            'text' => $messageText,
            'source' => 'whatsapp',
            'sent_at' => now(),
        ]);

        $pusherPayload['message']['id'] = (string) $message->id;

        if ($groupChannel) {
            $pusher->trigger($groupChannel, 'message', $pusherPayload);
        }

        if ($legacyChannel) {
            $pusher->trigger($legacyChannel, 'message', $pusherPayload);
        }

        if (! $session->first_response_at) {
            $session->first_response_at = now();
        }
        $session->last_response_at = now();
        $session->away_sent_at = null;

        if ($groupChannel && $session->pusher_channel !== $groupChannel) {
            $session->pusher_channel = $groupChannel;
        }

        // Update session last activity
        $session->save();

        Log::info('WhatsApp Webhook: Success', ['session_id' => $session->id]);

        return response()->json(['status' => 'ok', 'session_id' => $session->id]);
    }

    private function sendGroupMessage(ChatSession $session, string $text): void
    {
        if (! $session->instance || ! $session->group_jid) {
            return;
        }

        try {
            app(WhatsappClient::class)->sendText($session->instance, [
                'number' => $session->group_jid,
                'text' => $text,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('WhatsApp Webhook: Failed to send system message', [
                'session_id' => $session->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
