<?php

use App\Http\Controllers\GroupCreationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GroupController;

Route::get('/', function () {
    return view('app');
});

Route::post('/groups', [GroupCreationController::class, 'store']);
Route::post('/group/create/{instance}', [GroupController::class, 'create']);
