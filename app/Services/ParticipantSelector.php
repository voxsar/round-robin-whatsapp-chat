<?php

namespace App\Services;

class ParticipantSelector
{
    public function selectParticipants(): array
    {
        $participants = config('services.whatsapp.participants', []);
        $botNumber = config('services.whatsapp.bot_number');
        $roundRobin = config('services.whatsapp.round_robin', false);

        $selected = [];

        if ($roundRobin && count($participants) > 0) {
            $selected[] = $participants[array_rand($participants)];
        } else {
            $selected = $participants;
        }

        if ($botNumber) {
            $selected[] = $botNumber;
        }

        return array_values(array_unique(array_filter($selected)));
    }
}
