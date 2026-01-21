<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppProvider
{
    public function sendGroupMessage(string $groupId, string $message): void
    {
        $baseUrl = config('services.whatsapp.base_url');
        $token = config('services.whatsapp.token');
        $endpoint = config('services.whatsapp.send_message_endpoint', '/messages');

        if (empty($baseUrl) || empty($token)) {
            throw new RuntimeException('WhatsApp provider credentials are not configured.');
        }

        $response = $this->client($token)
            ->post(rtrim($baseUrl, '/').'/'.ltrim($endpoint, '/'), [
                'group_id' => $groupId,
                'message' => $message,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('WhatsApp provider request failed: '.$response->body());
        }
    }

    private function client(string $token): PendingRequest
    {
        return Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(config('services.whatsapp.timeout', 10));
    }
}
