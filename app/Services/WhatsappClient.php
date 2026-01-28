<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class WhatsappClient
{
    public function createGroup(string $instance, array $payload): array
    {
        return $this->request()
            ->post($this->endpoint("/group/create/{$instance}"), $payload)
            ->json() ?? [];
    }

    public function sendText(string $instance, array $payload): array
    {
        return $this->request()
            ->post($this->endpoint("/message/sendText/{$instance}"), $payload)
            ->json() ?? [];
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'apiKey' => config('services.whatsapp.api_key'),
        ]);
    }

    private function endpoint(string $path): string
    {
        return rtrim(config('services.whatsapp.base_url', ''), '/') . $path;
    }
}
