<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Person;
use App\Models\User;
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
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'add_to_group' => ['nullable', 'boolean'],
        ]);

        $instance = $validated['instance'] ?? config('services.whatsapp.instance');

        if (! $instance) {
            return response()->json(['message' => 'WhatsApp instance not configured.'], 422);
        }

        $sessionQuery = ChatSession::query()
            ->when(! empty($validated['mobile']), fn ($query) => $query->orWhere('mobile', $validated['mobile']))
            ->when(! empty($validated['email']), fn ($query) => $query->orWhere('email', $validated['email']))
            ->orderByDesc('id');

        $existingSession = (clone $sessionQuery)
            ->where('status', 'active')
            ->first();

        if ($existingSession) {
            $groupChannel = "group-" . ($existingSession->group_id ?: $existingSession->group_jid);
            if (! $existingSession->pusher_channel && $groupChannel !== 'group-') {
                $existingSession->pusher_channel = $groupChannel;
                $existingSession->save();
            }
            return response()->json([
                'session' => $existingSession,
                'group' => null,
                'channel' => $existingSession->pusher_channel,
                'messages' => $this->serializeMessages($existingSession),
            ]);
        }

        $endedSession = (clone $sessionQuery)
            ->where('status', 'ended')
            ->first();

        if ($endedSession) {
            $endedSession->restoreFromEnded();

            $groupChannel = "group-" . ($endedSession->group_id ?: $endedSession->group_jid);
            if (! $endedSession->pusher_channel && $groupChannel !== 'group-') {
                $endedSession->pusher_channel = $groupChannel;
            }
            $endedSession->save();

            return response()->json([
                'session' => $endedSession,
                'group' => null,
                'channel' => $endedSession->pusher_channel,
                'messages' => $this->serializeMessages($endedSession),
            ]);
        }

        $participants = $selector->selectParticipants();
        $addToGroup = (bool) ($validated['add_to_group'] ?? false);

        if ($addToGroup && ! empty($validated['mobile'])) {
            $participant = $this->formatParticipant($validated['mobile']);
            if ($participant) {
                $participants[] = $participant;
            }
        }
        $participants = array_values(array_unique(array_filter($participants)));
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

        $assignedUserId = $validated['assigned_user_id']
            ?? User::query()
                ->where('role', 'agent')
                ->orderBy('id')
                ->value('id');

        $personQuery = Person::query();

        if (! empty($validated['email'])) {
            $personQuery->orWhere('email', $validated['email']);
        }

        if (! empty($validated['mobile'])) {
            $personQuery->orWhere('mobile', $validated['mobile']);
        }

        $person = $personQuery->first();

        if (! $person) {
            $person = Person::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'stage' => 'new',
                'assigned_user_id' => $assignedUserId,
            ]);
        } else {
            $person->fill([
                'name' => $person->name ?: $validated['name'],
                'email' => $person->email ?: ($validated['email'] ?? null),
                'mobile' => $person->mobile ?: ($validated['mobile'] ?? null),
                'assigned_user_id' => $person->assigned_user_id ?: $assignedUserId,
            ]);
            $person->save();
        }

        $pusherChannel = $groupJid ? "group-{$groupJid}" : "session-" . uniqid('chat_', true);

        $session = ChatSession::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'mobile' => $validated['mobile'] ?? null,
            'instance' => $instance,
            'group_jid' => $groupJid,
            'group_id' => $groupJid,
            'provider' => 'whatsapp',
            'group_subject' => $subject,
            'status' => 'active',
            'pusher_channel' => $pusherChannel,
            'session_id' => "session-" . uniqid('chat_', true),
            'whatsapp_group_id' => $groupJid,
            'person_id' => $person->id,
            'assigned_user_id' => $assignedUserId,
        ]);

        return response()->json([
            'session' => $session,
            'group' => $groupResponse,
            'channel' => $pusherChannel,
            'messages' => $this->serializeMessages($session),
        ]);
    }

    public function sendMessage(Request $request, WhatsappClient $whatsapp, PusherClient $pusher): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:chat_sessions,id'],
            'text' => ['nullable', 'string'],
            'message' => ['nullable', 'string'],
        ]);

        $session = ChatSession::findOrFail($validated['session_id']);
        $messageText = $validated['text'] ?? $validated['message'] ?? null;

        if (! $messageText) {
            return response()->json(['message' => 'Message text is required.'], 422);
        }

        if ($session->status === 'blocked') {
            return response()->json(['status' => 'ignored', 'reason' => 'chat_not_active']);
        }

        if (! $session->group_jid) {
            return response()->json(['message' => 'Group not initialized'], 422);
        }
        if (! $session->instance) {
            return response()->json(['message' => 'WhatsApp instance not configured.'], 422);
        }

        $session->restoreFromEnded();

        $whatsapp->sendText($session->instance, [
            'number' => $session->group_jid,
            'text' => $messageText,
        ]);

        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'visitor',
            'text' => $messageText,
            'source' => 'web',
            'sent_at' => now(),
        ]);

        if (! $session->first_response_at) {
            $session->first_response_at = now();
        }
        $session->last_response_at = now();

        $groupKey = $session->group_id ?: $session->group_jid;
        $groupChannel = $groupKey ? "group-{$groupKey}" : null;
        $legacyChannel = $session->pusher_channel && $session->pusher_channel !== $groupChannel
            ? $session->pusher_channel
            : null;

        if ($groupChannel) {
            $pusher->trigger($groupChannel, 'message', [
                'message' => [
                    'id' => (string) $message->id,
                    'sender' => 'visitor',
                    'sender_name' => $session->name,
                    'text' => $messageText,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        }

        if ($legacyChannel) {
            $pusher->trigger($legacyChannel, 'message', [
                'message' => [
                    'id' => (string) $message->id,
                    'sender' => 'visitor',
                    'sender_name' => $session->name,
                    'text' => $messageText,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        }

        if ($groupChannel && $session->pusher_channel !== $groupChannel) {
            $session->forceFill(['pusher_channel' => $groupChannel])->save();
        } else {
            $session->save();
        }

        return response()->json(['status' => 'sent']);
    }

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['nullable'],
            'email' => ['nullable', 'email'],
            'mobile' => ['nullable', 'string', 'max:30'],
        ]);

        $session = ChatSession::query()
            ->when(! empty($validated['session_id']), function ($query) use ($validated) {
                $query->where('id', $validated['session_id'])
                    ->orWhere('session_id', $validated['session_id']);
            })
            ->when(! empty($validated['mobile']), fn ($query) => $query->orWhere('mobile', $validated['mobile']))
            ->when(! empty($validated['email']), fn ($query) => $query->orWhere('email', $validated['email']))
            ->orderByDesc('id')
            ->first();

        if (! $session || $session->status === 'blocked') {
            return response()->json(['message' => 'Session not found.'], 404);
        }

        $groupChannel = "group-" . ($session->group_id ?: $session->group_jid);
        if (! $session->pusher_channel && $groupChannel !== 'group-') {
            $session->pusher_channel = $groupChannel;
            $session->save();
        }

        return response()->json([
            'session' => $session,
            'channel' => $session->pusher_channel,
            'messages' => $this->serializeMessages($session),
        ]);
    }

    public function end(Request $request, PusherClient $pusher): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required'],
        ]);

        $session = ChatSession::query()
            ->where('id', $validated['session_id'])
            ->orWhere('session_id', $validated['session_id'])
            ->first();

        if (! $session) {
            return response()->json(['message' => 'Session not found.'], 404);
        }

        if ($session->status !== 'ended') {
            $session->status = 'ended';
            $session->ended_at = now();
            $session->save();
        }

        $settings = ChatSetting::current();
        $messageText = $settings->user_end_message ?? config('chat.user_end_message', 'User Ended Chat.');
        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'system',
            'text' => $messageText,
            'source' => 'system',
            'sent_at' => now(),
        ]);

        $channel = $session->pusher_channel ?: ("group-" . ($session->group_id ?: $session->group_jid));
        $pusher->trigger($channel, 'message', [
            'message' => [
                'id' => (string) $message->id,
                'sender' => 'system',
                'text' => $messageText,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        if ($session->instance && $session->group_jid) {
            try {
                app(WhatsappClient::class)->sendText($session->instance, [
                    'number' => $session->group_jid,
                    'text' => $messageText,
                ]);
            } catch (\Throwable $exception) {
                // Ignore provider failures for end messages
            }
        }

        return response()->json(['status' => 'ended']);
    }

    private function serializeMessages(ChatSession $session): array
    {
        return $session->messages()
            ->orderBy('sent_at')
            ->limit(200)
            ->get()
            ->map(fn (ChatMessage $message) => [
                'id' => (string) $message->id,
                'sender' => $message->sender,
                'sender_name' => $message->sender_name,
                'text' => $message->text,
                'timestamp' => optional($message->sent_at ?? $message->created_at)->toIso8601String(),
            ])
            ->all();
    }

    private function formatParticipant(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (str_contains($raw, '@')) {
            return $raw;
        }
        $digits = preg_replace('/\\D+/', '', $raw);
        if ($digits === '') {
            return null;
        }

        return $digits . '@s.whatsapp.net';
    }
}
