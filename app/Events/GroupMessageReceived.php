<?php

namespace App\Events;


use App\Models\ChatSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupMessageReceived implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly ChatSession $session,
        public readonly array $payload,
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel($this->session->pusher_channel);
    }

    public function broadcastAs(): string
    {
        return 'message';
    }

    public function broadcastWith(): array
    {
        // Extract message details from the payload
        $messageText = $this->payload['message']['text'] 
            ?? $this->payload['message'] 
            ?? '';
        
        $sender = $this->payload['sender'] ?? 'agent';
        $timestamp = $this->payload['timestamp'] ?? now()->toIso8601String();
        
        return [
            'message' => [
                'id' => uniqid('msg_', true),
                'sender' => 'agent',
                'text' => $messageText,
                'timestamp' => $timestamp,
            ],
        ];
    }
}
