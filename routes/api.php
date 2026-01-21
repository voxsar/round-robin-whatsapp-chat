<?php

use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/chat/session', [ChatSessionController::class, 'start']);
Route::post('/chat/message', [ChatSessionController::class, 'sendMessage']);
Route::post('/webhooks/whatsapp', [WebhookController::class, 'handleWhatsApp']);
