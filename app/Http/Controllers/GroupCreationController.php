<?php

namespace App\Http\Controllers;

use App\Services\ParticipantSelector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GroupCreationController extends Controller
{
    public function store(Request $request, ParticipantSelector $selector)
    {
        $roundRobin = $request->boolean('round_robin', (bool) config('whatsapp.round_robin'));
        $participants = $selector->selectParticipants($roundRobin);

        $payload = [
            'subject' => $request->string('subject')->toString(),
            'participants' => $participants,
        ];

        $endpoint = (string) config('whatsapp.group_create_endpoint');
        $token = (string) config('whatsapp.api_token');

        $response = Http::withToken($token)->post($endpoint, $payload);

        return response()->json($response->json(), $response->status());
    }
}
