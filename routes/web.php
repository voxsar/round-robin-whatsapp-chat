<?php

use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use App\Http\Controllers\GroupCreationController;
use App\Http\Controllers\ChatSessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GroupController;

Route::get('/', function () {
    return view('app');
});

Route::post('/webhooks/whatsapp', WhatsAppWebhookController::class);
Route::post('/groups', [GroupCreationController::class, 'store']);
Route::post('/chat/session', [ChatSessionController::class, 'store']);
Route::post('/group/create/{instance}', [GroupController::class, 'create']);
