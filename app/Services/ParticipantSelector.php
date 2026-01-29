<?php

namespace App\Services;

class ParticipantSelector
{
    /**
     * @return array<int, string>
     */
    public function selectParticipants(?bool $roundRobin = null): array
    {
        $roundRobin = $roundRobin ?? (bool) config('whatsapp.round_robin');
        $botNumber = (string) (\App\Models\ChatSetting::current()->bot_number ?: config('whatsapp.bot_number'));
        $participants = [];

        if ($roundRobin) {
            $pool = array_values(array_filter(config('whatsapp.participant_pool', [])));
            if ($pool !== []) {
                $participants[] = $pool[array_rand($pool)];
            }
        } else {
            $participants = array_values(array_filter(config('whatsapp.fixed_participants', [])));
        }

        if ($botNumber !== '') {
            array_unshift($participants, $botNumber);
        }

        return array_values(array_unique($participants));
    }
}
