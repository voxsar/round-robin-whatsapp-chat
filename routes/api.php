<?php

use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/chat/session', [ChatSessionController::class, 'store']);
Route::post('/chat/session/lookup', [ChatSessionController::class, 'lookup']);
Route::post('/chat/session/end', [ChatSessionController::class, 'end']);
Route::post('/chat/message', [ChatSessionController::class, 'sendMessage']);
Route::post('/webhooks/whatsapp', [WebhookController::class, 'handleWhatsApp']);
