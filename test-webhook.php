<?php

/**
 * Test script for WhatsApp webhook
 * Usage: php test-webhook.php
 */

// Sample webhook payload from WhatsApp
$webhookPayload = [
    [
        "headers" => [
            "host" => "automation.nanaska.com",
            "x-real-ip" => "172.22.0.4",
            "x-forwarded-for" => "172.22.0.4",
            "x-forwarded-proto" => "https",
            "connection" => "upgrade",
            "content-length" => "1805",
            "accept" => "application/json, text/plain, */*",
            "content-type" => "application/json",
            "user-agent" => "axios/1.13.2",
            "accept-encoding" => "gzip, compress, deflate, br"
        ],
        "params" => [],
        "query" => [],
        "body" => [
            "event" => "messages.upsert",
            "instance" => "WhatsApp",
            "data" => [
                "key" => [
                    "remoteJid" => "120363422186326906@g.us",
                    "fromMe" => false,
                    "id" => "AC65ABFB8C40B49FBF265432D19ED2C4",
                    "participant" => "139427556921492@lid",
                    "participantAlt" => "94774395913@s.whatsapp.net",
                    "addressingMode" => "lid"
                ],
                "pushName" => "voxsar",
                "status" => "DELIVERY_ACK",
                "message" => [
                    "messageContextInfo" => [
                        "threadId" => [],
                    ],
                    "conversation" => "Hi"
                ],
                "messageType" => "conversation",
                "messageTimestamp" => 1768969523,
                "instanceId" => "299e21bc-b053-43ef-bceb-2063fb58c85b",
                "source" => "android"
            ],
            "destination" => "https://automation.nanaska.com/webhook/d47af0fa-ad68-4926-8a5b-b9cb8c1d9360",
            "date_time" => "2026-01-21T01:25:23.670Z",
            "sender" => "94789210953@s.whatsapp.net",
            "server_url" => "http://localhost:8080",
            "apikey" => "8901A63EDDA5-4B29-A448-414532C4DB2B"
        ],
        "webhookUrl" => "https://automation.nanaska.com/webhook/d47af0fa-ad68-4926-8a5b-b9cb8c1d9360",
        "executionMode" => "production"
    ]
];

// Send to local webhook endpoint
$ch = curl_init('http://localhost:8000/api/webhooks/whatsapp');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookPayload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n";

// Decode and pretty print
if ($response) {
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "\nParsed Response:\n";
        print_r($decoded);
    }
}
