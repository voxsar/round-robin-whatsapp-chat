<?php

namespace App\Filament\Admin\Pages;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\PusherClient;
use App\Services\WhatsappClient;
use Filament\Pages\Page;

class ChatConsole extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string|\UnitEnum|null $navigationGroup = 'Operations';
    protected static ?string $navigationLabel = 'Chat Console';
    protected string $view = 'filament.pages.chat-console';

    public array $sessions = [];
    public ?int $activeSessionId = null;
    public array $messages = [];
    public string $newMessage = '';

    public function mount(): void
    {
        $this->refreshSessions();

        if (! empty($this->sessions)) {
            $this->selectSession($this->sessions[0]['id']);
        }
    }

    public function refreshSessions(): void
    {
        $query = ChatSession::query()->orderByDesc('updated_at');
        $user = auth()->user();

        if ($user?->role === 'manager') {
            $agentIds = $user->directReports()->pluck('id')->push($user->id);
            $query->whereIn('assigned_user_id', $agentIds);
        } elseif ($user?->role === 'agent') {
            $query->where('assigned_user_id', $user->id);
        }

        $this->sessions = $query
            ->limit(50)
            ->get()
            ->map(fn (ChatSession $session) => [
                'id' => $session->id,
                'label' => $session->name ?: ($session->mobile ?: $session->email ?: 'Chat #' . $session->id),
                'status' => $session->status,
                'updated_at' => optional($session->updated_at)->toDateTimeString(),
            ])
            ->all();
    }

    public function selectSession(int $sessionId): void
    {
        $this->activeSessionId = $sessionId;
        $this->loadMessages();
    }

    public function loadMessages(): void
    {
        if (! $this->activeSessionId) {
            $this->messages = [];
            return;
        }

        $this->messages = ChatMessage::query()
            ->where('chat_session_id', $this->activeSessionId)
            ->orderBy('sent_at')
            ->limit(200)
            ->get()
            ->map(fn (ChatMessage $message) => [
                'id' => $message->id,
                'sender' => $message->sender,
                'sender_name' => $message->sender_name,
                'text' => $message->text,
                'timestamp' => optional($message->sent_at ?? $message->created_at)->toDateTimeString(),
            ])
            ->all();
    }

    public function sendMessage(PusherClient $pusher, WhatsappClient $whatsapp): void
    {
        $text = trim($this->newMessage);

        if (! $this->activeSessionId || $text === '') {
            return;
        }

        $session = ChatSession::find($this->activeSessionId);

        if (! $session || $session->status === 'blocked') {
            return;
        }

        $session->restoreFromEnded();

        if ($session->instance && $session->group_jid) {
            $whatsapp->sendText($session->instance, [
                'number' => $session->group_jid,
                'text' => $text,
            ]);
        }

        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'user_id' => auth()->id(),
            'sender' => 'agent',
            'sender_name' => auth()->user()?->name,
            'text' => $text,
            'source' => 'filament',
            'sent_at' => now(),
        ]);

        if (! $session->first_response_at) {
            $session->first_response_at = now();
        }
        $session->last_response_at = now();

        $channelKey = $session->group_id ?: $session->group_jid;
        $channel = $channelKey ? "group-{$channelKey}" : $session->pusher_channel;

        if ($channel) {
            $pusher->trigger($channel, 'message', [
                'message' => [
                    'id' => (string) $message->id,
                    'sender' => 'agent',
                    'sender_name' => auth()->user()?->name,
                    'text' => $text,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        }

        if ($channel && $session->pusher_channel !== $channel) {
            $session->pusher_channel = $channel;
        }

        $session->save();

        $this->newMessage = '';
        $this->loadMessages();
    }
}
