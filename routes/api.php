<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\API\FineCheckController;
use App\Http\Controllers\API\FinesAnalyticsController;
use App\Http\Controllers\API\FinesController;
use App\Http\Controllers\Api\FirebaseVerificationController;
use App\Http\Controllers\Api\Support\SupportCategoryController;
use App\Http\Controllers\Api\Support\SupportTicketController;
use App\Http\Controllers\Api\Support\SupportTicketMessageController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\ControlTowerController;
use App\Http\Controllers\Api\WhatsAppVerificationController;
use App\Http\Controllers\Api\DriverController;
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

Route::get('/portal/fines', [FinesController::class, 'index']);           // GET /api/portal/fines
Route::get('/portal/fines/recent/{plate}', [FinesController::class, 'recent']);
Route::post('/portal/fines/check', [FinesController::class, 'forceCheck']);
Route::get('/portal/fines/analytics', [FinesAnalyticsController::class, 'index']);
Route::get('/portal/fines/by-day', [FinesAnalyticsController::class, 'byDay']);
Route::get('/portal/fines/export-day', [FinesAnalyticsController::class, 'exportDay']); // CSV export drill-down
// Optionally keep the export range path using ?export=csv handled in index()
Route::get('/portal/fines/by-violation', [FinesAnalyticsController::class, 'byViolation']);
Route::get('/portal/fines/by-violation', [FinesAnalyticsController::class, 'byViolation']);
// Targeted CSV Exports
Route::get('/portal/drivers', [DriverController::class, 'index']);

//Support system
Route::middleware(['auth:sanctum'])->prefix('portal/support')->group(function () {

    Route::get('categories', [SupportCategoryController::class, 'index']);

    Route::get('tickets', [SupportTicketController::class, 'index']);
    Route::post('tickets', [SupportTicketController::class, 'store']);
    Route::get('tickets/{ticket}', [SupportTicketController::class, 'show']);

    Route::patch('tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus']);
    Route::patch('tickets/{ticket}/assign', [SupportTicketController::class, 'assign']);

    Route::post('tickets/{ticket}/messages', [SupportTicketMessageController::class, 'store']);
});

Route::get('/portal/control-tower/stats', [ControlTowerController::class, 'getStats']);
