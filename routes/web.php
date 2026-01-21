<?php

use App\Http\Controllers\GroupCreationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::post('/groups', [GroupCreationController::class, 'store']);
