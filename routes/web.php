<?php

use App\Http\Controllers\ChatSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::post('/chat/session', [ChatSessionController::class, 'store']);
