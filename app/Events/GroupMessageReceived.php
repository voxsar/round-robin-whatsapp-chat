<?php

namespace App\Events;

use App\Models\ChatSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
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
        return 'GroupMessageReceived';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_session_id' => $this->session->id,
            'group_id' => $this->session->group_id,
            'provider_instance' => $this->session->provider_instance,
            'payload' => $this->payload,
        ];
    }
}
