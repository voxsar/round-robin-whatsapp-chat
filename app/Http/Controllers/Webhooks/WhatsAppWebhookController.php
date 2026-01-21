<?php

namespace App\Http\Controllers\Webhooks;

use App\Events\GroupMessageReceived;
use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->validateSignature($request);

        $payload = $request->validate([
            'group_id' => ['nullable', 'string', 'required_without:instance'],
            'instance' => ['nullable', 'string', 'required_without:group_id'],
            'message' => ['required'],
            'sender' => ['nullable'],
            'timestamp' => ['nullable'],
        ]);

        $groupId = $payload['group_id'] ?? null;
        $instance = $payload['instance'] ?? null;

        $session = null;

        if ($groupId) {
            $session = ChatSession::query()->where('group_id', $groupId)->first();
        }

        if (! $session && $instance) {
            $session = ChatSession::query()->where('provider_instance', $instance)->first();
        }

        if (! $session) {
            return response()->json(['message' => 'Chat session not found.'], 404);
        }

        event(new GroupMessageReceived($session, $payload));

        return response()->json(['status' => 'ok']);
    }

    private function validateSignature(Request $request): void
    {
        $secret = config('services.whatsapp.webhook_secret');

        if (! $secret) {
            return;
        }

        $header = config('services.whatsapp.signature_header', 'X-Webhook-Signature');
        $signature = $request->headers->get($header);

        if (! $signature) {
            abort(401, 'Missing webhook signature.');
        }

        if (Str::startsWith($signature, 'sha256=')) {
            $signature = Str::after($signature, 'sha256=');
        }

        $computed = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($computed, $signature)) {
            Log::warning('WhatsApp webhook signature mismatch.', [
                'header' => $header,
            ]);

            abort(401, 'Invalid webhook signature.');
        }
    }
}
