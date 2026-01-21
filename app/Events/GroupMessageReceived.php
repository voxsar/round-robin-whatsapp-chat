<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupMessageReceived implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $groupId;
    public array $message;

    public function __construct(string $groupId, array $message)
    {
        $this->groupId = $groupId;
        $this->message = $message;
    }

    public function broadcastOn(): Channel
    {
        return new Channel("chat-session.{$this->groupId}");
    }

    public function broadcastAs(): string
    {
        return 'group-message-received';
    }

    public function broadcastWith(): array
    {
        return [
            'groupId' => $this->groupId,
            'message' => $this->message,
        ];
    }
}
