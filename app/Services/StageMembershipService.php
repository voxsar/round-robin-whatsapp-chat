<?php

namespace App\Services;

use App\Models\ChatSetting;
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

        $person->chatSessions()->get()->each(function ($session) use ($addParticipants, $removeParticipants, $previousStage, $person): void {
            $groupId = $session->group_jid ?: $session->whatsapp_group_id;

            if (! $groupId || ! $session->instance) {
                return;
            }

            if ($previousStage && $previousStage !== $person->stage) {
                $message = sprintf('Stage updated: %s → %s', $previousStage, $person->stage);
                $this->whatsappClient->sendText($session->instance, [
                    'number' => $groupId,
                    'text' => $message,
                ]);
                Log::info('StageMembership: stage change message sent', [
                    'group_id' => $groupId,
                    'from' => $previousStage,
                    'to' => $person->stage,
                ]);
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
        $settings = ChatSetting::current();
        $settingsKey = "stage_{$stage}_participants";
        $configured = $this->parseParticipants($settings->{$settingsKey} ?? null);

        if ($configured !== []) {
            return $configured;
        }

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
