<?php

namespace App\Services;

use App\Models\ChatSetting;

class ParticipantSelector
{
    /**
     * @return array<int, string>
     */
    public function selectParticipants(?bool $roundRobin = null): array
    {
        $roundRobin = $roundRobin ?? (bool) config('whatsapp.round_robin');
        $settings = ChatSetting::current();
        $botNumber = (string) ($settings->bot_number ?: config('whatsapp.bot_number'));
        $pendingParticipants = $this->parseParticipants($settings->pending_participants ?? null);
        $participants = [];

        if ($roundRobin) {
            $pool = $pendingParticipants !== []
                ? $pendingParticipants
                : array_values(array_filter(config('whatsapp.participant_pool', [])));
            if ($pool !== []) {
                $participants[] = $pool[array_rand($pool)];
            }
        } else {
            $participants = $pendingParticipants !== []
                ? $pendingParticipants
                : array_values(array_filter(config('whatsapp.fixed_participants', [])));
        }

        if ($botNumber !== '') {
            array_unshift($participants, $botNumber);
        }

        return array_values(array_unique($participants));
    }

    /**
     * @return array<int, string>
     */
    private function parseParticipants(?string $participants): array
    {
        if (! $participants) {
            return [];
        }

        $values = array_map('trim', explode(',', $participants));

        return array_values(array_filter($values, fn ($value) => $value !== ''));
    }
}
