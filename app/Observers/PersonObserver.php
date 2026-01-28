<?php

namespace App\Observers;

use App\Models\Person;
use App\Services\StageMembershipService;

class PersonObserver
{
    public function __construct(private StageMembershipService $membershipService)
    {
    }

    public function created(Person $person): void
    {
        $this->membershipService->syncStage($person);
    }

    public function updated(Person $person): void
    {
        if ($person->wasChanged('stage')) {
            $this->membershipService->syncStage($person, $person->getOriginal('stage'));
        }
    }
}
