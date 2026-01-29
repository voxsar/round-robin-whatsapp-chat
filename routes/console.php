<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\ChatInactivityService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('chat:process-inactivity', function (ChatInactivityService $service) {
    $result = $service->handle();
    $this->info(sprintf('Away sent: %d | Ended: %d', $result['away'], $result['ended']));
})->purpose('Send away messages and end inactive chats.');
