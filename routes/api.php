<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\API\FineCheckController;
use App\Http\Controllers\API\FinesAnalyticsController;
use App\Http\Controllers\API\FinesController;
use App\Http\Controllers\Api\FirebaseVerificationController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\WhatsAppVerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\Portal\SupportTicketController as PortalSupportTicketController;

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

Route::get('/portal/fines', [FinesController::class, 'index']);           // GET /api/portal/fines
Route::get('/portal/fines/recent/{plate}', [FinesController::class, 'recent']);
Route::post('/portal/fines/check', [FinesController::class, 'forceCheck']);
Route::get('/portal/fines/analytics', [FinesAnalyticsController::class, 'index']);
Route::get('/portal/fines/by-day', [FinesAnalyticsController::class, 'byDay']);
Route::get('/portal/fines/export-day', [FinesAnalyticsController::class, 'exportDay']); // CSV export drill-down
// Optionally keep the export range path using ?export=csv handled in index()

//Support system
Route::middleware('auth:sanctum')->group(function () {

    // Driver / Client
    Route::get('/support/tickets', [SupportTicketController::class, 'index']);
    Route::post('/support/tickets', [SupportTicketController::class, 'store']);
    Route::get('/support/tickets/{ticket}', [SupportTicketController::class, 'show']);

    // Portal
    Route::prefix('portal/support')->group(function () {
        Route::get('/tickets', [PortalSupportTicketController::class, 'index']);
        Route::patch('/tickets/{ticket}', [PortalSupportTicketController::class, 'update']);
    });
});
