<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppProvider
{
    public function sendGroupMessage(string $groupId, string $message): void
    {
        $baseUrl = config('services.whatsapp.base_url');
        $token = config('services.whatsapp.token');
		$instance = config('services.whatsapp.instance');
        $endpoint = config('services.whatsapp.send_message_endpoint', '/message/sendText/'.$instance);

        if (empty($baseUrl) || empty($token)) {
            throw new RuntimeException('WhatsApp provider credentials are not configured.');
        }
		Log::info(rtrim($baseUrl, '/').'/'.ltrim($endpoint, '/'));
        $response = $this->client($token)
            ->post(rtrim($baseUrl, '/').'/'.ltrim($endpoint, '/'), [
                'number' => $groupId,
                'text' => $message,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('WhatsApp provider request failed: '.$response->body());
        }
    }

    private function client(string $token): PendingRequest
    {
        //api header called apiKey 
		return Http::withHeaders([
			'apiKey' => $token,	
		])
            ->acceptJson()
            ->asJson()
            ->timeout(config('services.whatsapp.timeout', 10));
    }
}
