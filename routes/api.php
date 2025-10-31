<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactVerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // contacts
    Route::get('/contacts', [ContactVerificationController::class, 'index']);
    Route::post('/contacts', [ContactVerificationController::class, 'store']);
    Route::post('/contacts/{contact}/resend', [ContactVerificationController::class, 'resend']);
    Route::post('/contacts/{contact}/verify', [ContactVerificationController::class, 'verify']);
});
