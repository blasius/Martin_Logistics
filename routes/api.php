<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ComplianceSummaryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ControlTowerController;
use App\Http\Controllers\Api\DispatchController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\FineCheckController;
use App\Http\Controllers\Api\FinesAnalyticsController;
use App\Http\Controllers\Api\FinesController;
use App\Http\Controllers\Api\FirebaseVerificationController;
use App\Http\Controllers\Api\FleetDashboardController;
use App\Http\Controllers\Api\InspectionController;
use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\WhatsAppVerificationController;
use App\Http\Controllers\Api\SearchController;

// Public Routes
Route::post('/login', [AuthController::class, 'login']); // Token-based
Route::post('/portal/login', [AuthController::class, 'portalLogin']); // Session-based
Route::get('/dispatch/secure-print', [DispatchController::class, 'printStatus'])
    ->name('dispatch.print.secure')
    ->middleware('signed'); // Laravel 12 handles this alias automatically
Route::get('/search/global', [SearchController::class, 'search']);

// Protected Routes
Route::middleware('auth')->group(function () {

    // Initial Session Check (Critical for UI protection on refresh)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Portal Routes (Session-based via Vue) ---
    Route::prefix('portal')->group(function () {

        // Fines & Analytics
        Route::get('/fines', [FinesController::class, 'index']);
        Route::get('/fines/recent/{plate}', [FinesController::class, 'recent']);
        Route::post('/fines/check', [FinesController::class, 'forceCheck']);
        Route::get('/fines/analytics', [FinesAnalyticsController::class, 'index']);
        Route::get('/fines/by-day', [FinesAnalyticsController::class, 'byDay']);
        Route::get('/fines/export-day', [FinesAnalyticsController::class, 'exportDay']);
        Route::get('/fines/by-violation', [FinesAnalyticsController::class, 'byViolation']);

        // Operations
        Route::get('/drivers', [DriverController::class, 'index']);
        Route::get('/drivers/search-users', [DriverController::class, 'searchUsers']);
        Route::post('/drivers', [DriverController::class, 'store']);

        Route::get('/vehicles', [VehicleController::class, 'index']);

        Route::get('/dispatch', [DispatchController::class, 'index']);
        Route::post('/dispatch/pair', [DispatchController::class, 'pair']);
        Route::get('/dispatch/export', [DispatchController::class, 'export']);
        Route::get('/dispatch/history/{id}', [DispatchController::class, 'history']);
        Route::post('/dispatch/maintenance', [DispatchController::class, 'toggleMaintenance']);
        Route::post('/dispatch/activate', [DispatchController::class, 'activateVehicle']);
        Route::get('/dispatch/print-url', [DispatchController::class, 'getPrintUrl']);

        Route::get('/insurances', [InsuranceController::class, 'index']);
        Route::post('/insurances', [InsuranceController::class, 'store']);
        Route::get('/inspections', [InsuranceController::class, 'index']);
        Route::post('/inspections', [InsuranceController::class, 'store']);

        Route::get('/compliance-summary', [ComplianceSummaryController::class, 'complianceSummary']);
        Route::get('/control-tower', [FleetDashboardController::class, 'getSnapshot']);
        Route::get('/report/{type}', [FleetDashboardController::class, 'getDetailedReport']);

        // Support System Nested Group
        Route::prefix('support')->group(function () {
            Route::get('categories', [SupportCategoryController::class, 'index']);
            Route::get('tickets', [SupportTicketController::class, 'index']);
            Route::post('tickets', [SupportTicketController::class, 'store']);
            Route::get('tickets/{ticket}', [SupportTicketController::class, 'show']);
            Route::patch('tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus']);
            Route::patch('tickets/{ticket}/assign', [SupportTicketController::class, 'assign']);
            Route::post('tickets/{ticket}/messages', [SupportTicketMessageController::class, 'store']);
        });
    });
});
