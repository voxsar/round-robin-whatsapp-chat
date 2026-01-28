<?php

use App\Http\Controllers\ChatMessageController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use App\Http\Controllers\GroupCreationController;
use App\Http\Controllers\ChatSessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LiveLocationController;

Route::get('/', function () {
    return view('app');
});

Route::get('/login', function () {
    return redirect()->to('/admin/login');
})->name('login');

Route::post('/chat/message', [ChatMessageController::class, 'store'])
    ->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/webhooks/whatsapp', WhatsAppWebhookController::class);
Route::post('/groups', [GroupCreationController::class, 'store']);
Route::post('/chat/session', [ChatSessionController::class, 'store']);
Route::post('/group/create/{instance}', [GroupController::class, 'create']);
Route::get('/live-location', [LiveLocationController::class, 'show'])->name('live-location.show');
Route::get('/live-location/thumbnail', [LiveLocationController::class, 'thumbnail'])->name('live-location.thumbnail');
