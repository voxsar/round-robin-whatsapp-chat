<?php

use App\Http\Controllers\ChatMessageController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use App\Http\Controllers\GroupCreationController;
use App\Http\Controllers\ChatSessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GroupController;

Route::get('/', function () {
    return view('app');
});

Route::post('/chat/message', [ChatMessageController::class, 'store'])
    ->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/webhooks/whatsapp', WhatsAppWebhookController::class);
Route::post('/groups', [GroupCreationController::class, 'store']);
Route::post('/chat/session', [ChatSessionController::class, 'store']);
Route::post('/group/create/{instance}', [GroupController::class, 'create']);
