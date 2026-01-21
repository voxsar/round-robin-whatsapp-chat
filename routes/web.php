<?php

use App\Http\Controllers\ChatMessageController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::post('/chat/message', [ChatMessageController::class, 'store'])
    ->withoutMiddleware([VerifyCsrfToken::class]);
