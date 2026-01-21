<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GroupController;

Route::get('/', function () {
    return view('app');
});

Route::post('/group/create/{instance}', [GroupController::class, 'create']);
