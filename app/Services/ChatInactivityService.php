<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\ChatSetting;
use Illuminate\Support\Carbon;

class ChatInactivityService
{
    public function handle(): array
    {
        $settings = ChatSetting::current();
        $awayAfter = (int) ($settings->away_after_minutes ?? config('chat.away_after_minutes', 5));
        $endAfter = (int) ($settings->end_after_minutes ?? config('chat.end_after_minutes', 10));
        $awayMessage = (string) ($settings->away_message ?? config('chat.away_message'));
        $endMessage = (string) ($settings->end_message ?? config('chat.end_message'));

        $now = now();
        $awayCutoff = $now->copy()->subMinutes($awayAfter);
        $endCutoff = $now->copy()->subMinutes($endAfter);

        $affected = [
            'away' => 0,
            'ended' => 0,
        ];

        ChatSession::query()
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->get()
            ->each(function (ChatSession $session) use ($awayCutoff, $endCutoff, $awayMessage, $endMessage, &$affected) {
                $lastActivity = $session->last_response_at ?? $session->created_at;

                if (! $lastActivity instanceof Carbon) {
                    $lastActivity = now()->subYears(1);
                }

                if ($lastActivity->lte($endCutoff)) {
                    $this->postSystemMessage($session, $endMessage, 'ended');
                    $session->status = 'ended';
                    $session->ended_at = now();
                    $session->save();
                    $affected['ended']++;
                    return;
                }

                if ($session->away_sent_at === null && $lastActivity->lte($awayCutoff)) {
                    $this->postSystemMessage($session, $awayMessage, 'away');
                    $session->away_sent_at = now();
                    $session->save();
                    $affected['away']++;
                }
            });

        return $affected;
    }

    private function postSystemMessage(ChatSession $session, string $text, string $tag): void
    {
        if (trim($text) === '') {
            return;
        }

        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'system',
            'text' => $text,
            'source' => $tag,
            'sent_at' => now(),
        ]);

        $channel = $session->pusher_channel ?: ("group-" . ($session->group_id ?: $session->group_jid));

        app(PusherClient::class)->trigger($channel, 'message', [
            'message' => [
                'id' => (string) $message->id,
                'sender' => 'system',
                'text' => $text,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        if (! empty($session->instance) && ! empty($session->group_jid)) {
            try {
                app(WhatsAppProvider::class)->sendGroupMessage($session->group_jid, $text);
            } catch (\Throwable $exception) {
                // ignore provider failures for system messages
            }
        }
    }
}
