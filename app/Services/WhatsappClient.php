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

    public function addParticipants(string $instance, string $groupId, array $participants): array
    {
        return $this->updateParticipants($instance, $groupId, 'add', $participants);
    }

    public function removeParticipants(string $instance, string $groupId, array $participants): array
    {
        return $this->updateParticipants($instance, $groupId, 'remove', $participants);
    }

    public function updateParticipants(string $instance, string $groupJid, string $action, array $participants): array
    {
        $endpoint = $this->endpoint($this->groupParticipantEndpoint('whatsapp.group_update_participant_endpoint', $instance));
        $endpoint .= '?' . http_build_query(['groupJid' => $groupJid]);

        return $this->request()
            ->post($endpoint, [
                'action' => $action,
                'participants' => $participants,
            ])
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

    private function groupParticipantEndpoint(string $configKey, string $instance): string
    {
        $template = config($configKey, "/group/participants/{$instance}");

        return str_replace('{instance}', $instance, $template);
    }
}
