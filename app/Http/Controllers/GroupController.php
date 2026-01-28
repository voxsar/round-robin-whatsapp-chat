<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GroupController extends Controller
{
    public function create(Request $request, string $instance)
    {
        $apiKey = $request->header('apiKey');
        $expectedKey = config('services.whatsapp.group_api_key');

        if (!$expectedKey || $apiKey !== $expectedKey) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->validate([
            'subject' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'participants' => ['required', 'array'],
            'participants.*' => ['required', 'string', 'regex:/^\+?\d+$/'],
        ]);

        $baseUrl = rtrim(config('services.whatsapp.base_url'), '/');
        $serviceKey = config('services.whatsapp.api_key');

        $response = Http::withHeaders([
            'apiKey' => $serviceKey,
        ])->post("{$baseUrl}/group/create/{$instance}", $payload);

        return response()->json($response->json(), $response->status());
    }
}
