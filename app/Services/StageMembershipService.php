<?php

namespace App\Services;

use App\Models\Person;
use Illuminate\Support\Facades\Log;

class StageMembershipService
{
    public function __construct(private WhatsappClient $whatsappClient)
    {
    }

    public function syncStage(Person $person, ?string $previousStage = null): void
    {
        $addParticipants = $this->stageParticipants($person->stage);
        $removeParticipants = $previousStage
            ? $this->stageRemovalParticipants($previousStage)
            : [];

        if (empty($addParticipants) && empty($removeParticipants)) {
            return;
        }

        $person->chatSessions()->get()->each(function ($session) use ($addParticipants, $removeParticipants): void {
            $groupId = $session->group_jid ?: $session->whatsapp_group_id;

            if (! $groupId || ! $session->instance) {
                return;
            }

            if (! empty($addParticipants)) {
                $this->whatsappClient->addParticipants($session->instance, $groupId, $addParticipants);
                Log::info('StageMembership: added participants', [
                    'group_id' => $groupId,
                    'participants' => $addParticipants,
                    'stage' => $session->person?->stage,
                ]);
            }

            if (! empty($removeParticipants)) {
                $this->whatsappClient->removeParticipants($session->instance, $groupId, $removeParticipants);
                Log::info('StageMembership: removed participants', [
                    'group_id' => $groupId,
                    'participants' => $removeParticipants,
                    'stage' => $session->person?->stage,
                ]);
            }
        });
    }

    private function stageParticipants(string $stage): array
    {
        return array_values(array_filter(config("whatsapp.stage_participants.{$stage}", [])));
    }

    private function stageRemovalParticipants(string $stage): array
    {
        $explicitRemoval = array_values(array_filter(config("whatsapp.stage_remove_participants.{$stage}", [])));

        if (! empty($explicitRemoval)) {
            return $explicitRemoval;
        }

        return $this->stageParticipants($stage);
    }
}
