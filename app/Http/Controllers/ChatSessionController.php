<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
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
            'pusher_channel' => "session-" . uniqid('chat_', true),
			'session_id' => "session-" . uniqid('chat_', true),
            'person_id' => $person->id,
            'assigned_user_id' => $assignedUserId,
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
