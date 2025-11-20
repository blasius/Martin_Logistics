<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\API\FineCheckController;
use App\Http\Controllers\API\FinesController;
use App\Http\Controllers\Api\FirebaseVerificationController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\WhatsAppVerificationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/contacts', [ContactController::class, 'index']);
    Route::post('/contacts', [ContactController::class, 'store']);
    Route::post('/contacts/{contact}/send-code', [VerificationController::class, 'sendCode']);
    Route::post('/contacts/{contact}/verify', [VerificationController::class, 'verify']);
    Route::post('/contacts/{contact}/send-whatsapp', [WhatsAppVerificationController::class, 'send']);
    Route::post('/contacts/{contact}/verify-whatsapp', [WhatsAppVerificationController::class, 'verify']);
    Route::post('/contacts/{contact}/verify-firebase', [FirebaseVerificationController::class, 'verify']);
});

// routes/api.php
Route::get('/vehicles', [FinesController::class, 'listVehicles']);
Route::get('/fines/check/{plate}', [FinesController::class, 'check']);
Route::get('/fines/check/{plate}', [FineCheckController::class, 'check']);
Route::get('/fines/recent/{plate}', [FineCheckController::class, 'recent']);
