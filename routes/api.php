<?php

use App\Http\Controllers\Api\DigitalizeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('digitalize', [DigitalizeController::class, 'store']);
    Route::get('data', [DigitalizeController::class, 'index']);
    Route::get('data/{data}', [DigitalizeController::class, 'show']);
});
