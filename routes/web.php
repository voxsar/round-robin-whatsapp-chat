<?php

use App\Http\Controllers\ChatSessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GroupController;

Route::get('/', function () {
    return view('app');
});

Route::post('/chat/session', [ChatSessionController::class, 'store']);
Route::post('/group/create/{instance}', [GroupController::class, 'create']);
