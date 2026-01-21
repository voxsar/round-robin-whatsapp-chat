<?php

namespace App\Services;

use Pusher\Pusher;

class PusherClient
{
    public function trigger(string $channel, string $event, array $payload): void
    {
        $config = config('services.pusher');

        if (! $config['app_id'] || ! $config['key'] || ! $config['secret']) {
            return;
        }

        $pusher = new Pusher(
            $config['key'],
            $config['secret'],
            $config['app_id'],
            [
                'cluster' => $config['cluster'] ?? 'mt1',
                'useTLS' => true,
            ]
        );

        $pusher->trigger($channel, $event, $payload);
    }
}
