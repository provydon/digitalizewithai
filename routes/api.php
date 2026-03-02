<?php

use App\Http\Controllers\Api\DigitalizeController;
use Illuminate\Support\Facades\Route;

Route::post('digitalize', [DigitalizeController::class, 'store']);
Route::get('data/{data}', [DigitalizeController::class, 'show']);
