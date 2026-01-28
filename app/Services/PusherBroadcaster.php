<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PusherBroadcaster
{
    public function broadcastMessage(string $channel, array $payload): void
    {
        $config = config('services.pusher');

        if (! is_array($config)) {
            return;
        }

        $appId = $config['app_id'] ?? null;
        $key = $config['key'] ?? null;
        $secret = $config['secret'] ?? null;
        $cluster = $config['cluster'] ?? null;

        if (empty($appId) || empty($key) || empty($secret) || empty($cluster)) {
            return;
        }

        $body = [
            'name' => 'chat.message',
            'channels' => [$channel],
            'data' => json_encode($payload, JSON_THROW_ON_ERROR),
        ];

        $bodyJson = json_encode($body, JSON_THROW_ON_ERROR);
        $bodyMd5 = md5($bodyJson);

        $timestamp = (string) time();
        $queryParams = [
            'auth_key' => $key,
            'auth_timestamp' => $timestamp,
            'auth_version' => '1.0',
            'body_md5' => $bodyMd5,
        ];

        ksort($queryParams);
        $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $path = "/apps/{$appId}/events";
        $signatureBase = "POST\n{$path}\n{$queryString}";
        $signature = hash_hmac('sha256', $signatureBase, $secret);

        $url = sprintf('https://api-%s.pusher.com%s?%s&auth_signature=%s', $cluster, $path, $queryString, $signature);

        $response = Http::asJson()->post($url, $body);

        if (! $response->successful()) {
            throw new RuntimeException('Pusher broadcast failed: '.$response->body());
        }
    }
}
