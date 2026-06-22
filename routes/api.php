<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileTripController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ComplianceSummaryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ControlTowerController;
use App\Http\Controllers\Api\PortalControlTowerController;
use App\Http\Controllers\Api\DispatchController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\FineCheckController;
use App\Http\Controllers\Api\FinesAnalyticsController;
use App\Http\Controllers\Api\FinesController;
use App\Http\Controllers\Api\FirebaseVerificationController;
use App\Http\Controllers\Api\InspectionController;
use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\WhatsAppVerificationController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\RoutesController;
use App\Http\Controllers\Api\PlacesController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\MockDispatchController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TrackerController;
use App\Http\Controllers\Api\FleetReportController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\RoleManagementController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\MobileSupportTicketController;
use App\Http\Controllers\Api\Support\SupportCategoryController;
use App\Http\Controllers\Api\Support\SupportTicketMessageController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\Workshop\PartController as WsPartController;
use App\Http\Controllers\Api\Workshop\WarehouseController as WsWarehouseController;
use App\Http\Controllers\Api\Workshop\StockLevelController as WsStockLevelController;
use App\Http\Controllers\Api\Workshop\StockMovementController as WsStockMovementController;
use App\Http\Controllers\Api\Workshop\RepairRequestController as WsRepairRequestController;
use App\Http\Controllers\Api\Workshop\VendorController as WsVendorController;
use App\Http\Controllers\Api\Workshop\PurchaseOrderController as WsPurchaseOrderController;
use App\Http\Controllers\Api\Workshop\WorkshopDashboardController as WsDashboardController;
use App\Http\Controllers\Api\Workshop\MechanicController as WsMechanicController;
use App\Http\Controllers\Api\Workshop\PartRequestController as WsPartRequestController;
use App\Http\Controllers\Api\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;

// Public Routes
Route::post('/login', [AuthController::class, 'login']); // Token-based
Route::post('/portal/login', [AuthController::class, 'portalLogin']); // Session-based
Route::post('/portal/2fa/verify', [AuthController::class, 'verifyTwoFactor']);
Route::post('/portal/2fa/recovery', [AuthController::class, 'verifyRecoveryCode']);
Route::post('/portal/2fa/setup-init', [AuthController::class, 'initSetup']);
Route::post('/portal/2fa/setup-confirm', [AuthController::class, 'confirmSetup']);
Route::post('/portal/email/resend', [AuthController::class, 'resendVerificationEmail']);
Route::get('/portal/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('portal.email.verify')
    ->middleware('signed');
Route::get('/dispatch/secure-print', [DispatchController::class, 'printStatus'])
    ->name('dispatch.print.secure')
    ->middleware('signed'); // Laravel 12 handles this alias automatically
Route::get('/search/global', [SearchController::class, 'search']);

// Mobile Auth Routes
Route::prefix('mobile/auth')->group(function () {
    Route::post('/request-whatsapp-otp', [MobileAuthController::class, 'requestWhatsAppOtp']);
    Route::post('/verify-whatsapp-otp', [MobileAuthController::class, 'verifyWhatsAppOtp']);
    Route::post('/verify-firebase-phone', [MobileAuthController::class, 'verifyFirebasePhone']);
    Route::post('/logout', [MobileAuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Customer Portal — Public Auth Routes
Route::prefix('customer/auth')->group(function () {
    Route::post('signup', [CustomerAuthController::class, 'signup']);
    Route::post('login', [CustomerAuthController::class, 'login']);
});

// Mobile App Routes (Protected)
Route::prefix('mobile')->middleware('auth:sanctum')->group(function () {
    Route::prefix('trips')->group(function () {
        Route::get('/current', [MobileTripController::class, 'current']);
        Route::post('/{trip}/status', [MobileTripController::class, 'updateStatus']);
    });

    // Mobile Support Tickets
    Route::prefix('support')->group(function () {
        Route::get('categories', [SupportCategoryController::class, 'index']);
        Route::get('tickets', [MobileSupportTicketController::class, 'index']);
        Route::post('tickets', [MobileSupportTicketController::class, 'store']);
        Route::get('tickets/{ticket}', [MobileSupportTicketController::class, 'show']);
        Route::post('tickets/{ticket}/messages', [MobileSupportTicketController::class, 'addMessage']);
    });
});

// Password reset (no auth — accessed via emailed link)
Route::post('/portal/password/reset', [ProfileController::class, 'reset']);

// Protected Routes
Route::middleware('auth')->group(function () {

    // Initial Session Check (Critical for UI protection on refresh)
    Route::get('/user', function (Request $request) {
        $user = $request->user()->toArray();
        $user['roles_list'] = $request->user()->getRoleNames();
        return $user;
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Portal Routes (Session-based via Vue) ---
    Route::prefix('portal')->group(function () {

        // Fines & Analytics
        Route::get('/fines', [FinesController::class, 'index']);
        Route::get('/fines/export', [FinesController::class, 'export']);
        Route::get('/fines/stats', [FinesController::class, 'stats']);
        Route::get('/fines/recent/{plate}', [FinesController::class, 'recent']);
        Route::post('/fines/check', [FinesController::class, 'forceCheck']);
        Route::get('/fines/analytics', [FinesAnalyticsController::class, 'index']);
        Route::get('/fines/by-day', [FinesAnalyticsController::class, 'byDay']);
        Route::get('/fines/export-day', [FinesAnalyticsController::class, 'exportDay']);
        Route::get('/fines/by-violation', [FinesAnalyticsController::class, 'byViolation']);

        //Mock data
        Route::prefix('/mock')->group(function () {
            Route::get('/clients', [MockDispatchController::class, 'searchClients']);
            Route::get('/clients/{id}/order', [MockDispatchController::class, 'getClientOrder']);
            Route::get('/routes', [MockDispatchController::class, 'getRoutes']);
            Route::get('/assignments', [MockDispatchController::class, 'searchAssignments']);
        });

        // Operations
        Route::get('/drivers', [DriverController::class, 'index']);
        Route::get('/drivers/search-users', [DriverController::class, 'searchUsers']);
        Route::post('/drivers', [DriverController::class, 'store']);
        Route::post('/trips', [TripController::class, 'store']);

        Route::get('/vehicles', [VehicleController::class, 'index']);

        Route::get('/dispatch', [DispatchController::class, 'index']);
        Route::post('/dispatch/pair', [DispatchController::class, 'pair']);
        Route::get('/dispatch/export', [DispatchController::class, 'export']);
        Route::get('/dispatch/history/{id}', [DispatchController::class, 'history']);
        Route::post('/dispatch/maintenance', [DispatchController::class, 'toggleMaintenance']);
        Route::post('/dispatch/activate', [DispatchController::class, 'activateVehicle']);
        Route::get('/dispatch/print-url', [DispatchController::class, 'getPrintUrl']);
        Route::post('/dispatch/toggle-status', [DispatchController::class, 'toggleStatus']);

        Route::get('/insurances', [InsuranceController::class, 'index']);
        Route::post('/insurances', [InsuranceController::class, 'store']);
        Route::get('/inspections', [InspectionController::class, 'index']);
        Route::post('/inspections', [InspectionController::class, 'store']);

        // Routes
        Route::get('/routes', [RoutesController::class, 'index']);
        Route::get('/routes/{route}', [RoutesController::class, 'show']);
        Route::post('/routes/store', [RoutesController::class, 'store']);
        Route::put('/routes/{route}', [RoutesController::class, 'update']);
        Route::delete('/routes/{route}', [RoutesController::class, 'destroy']);

        // Places
        Route::get('/places', [PlacesController::class, 'index']);
        Route::get('/places/{place}', [PlacesController::class, 'show']);
        Route::post('/places/store', [PlacesController::class, 'store']);
        Route::put('/places/{place}', [PlacesController::class, 'update']);
        Route::delete('/places/{place}', [PlacesController::class, 'destroy']);

        Route::get('/compliance-summary', [ComplianceSummaryController::class, 'complianceSummary']);
        Route::get('/control-tower', [ControlTowerController::class, 'index']);
        Route::get('/report/{type}', [ControlTowerController::class, 'report']);

        // Tracker
        Route::get('/tracker', [TrackerController::class, 'index']);
        Route::get('/tracker/search', [TrackerController::class, 'search']);
        Route::get('/tracker/export-stationary', [TrackerController::class, 'exportStationary']);
        Route::get('/tracker/export-offline', [TrackerController::class, 'exportOffline']);
        Route::get('/tracker/{vehicle}', [TrackerController::class, 'show']);

        // 2FA Management
        Route::get('/2fa/qr', [AuthController::class, 'getTwoFactorQrCode']);
        Route::post('/2fa/enable', [AuthController::class, 'enableTwoFactor']);
        Route::post('/2fa/confirm', [AuthController::class, 'confirmTwoFactor']);
        Route::post('/2fa/disable', [AuthController::class, 'disableTwoFactor']);
        Route::get('/2fa/recovery-codes', [AuthController::class, 'getRecoveryCodes']);
        Route::post('/2fa/recovery-codes/regenerate', [AuthController::class, 'regenerateRecoveryCodes']);

        // Reports
        Route::get('/reports', [FleetReportController::class, 'index']);

        // Dashboard
        Route::get('/dashboard/overview', [DashboardController::class, 'getOverview']);
        Route::get('/dashboard/analytics', [DashboardController::class, 'getAnalytics']);
        Route::get('/dashboard/operational-status', [DashboardController::class, 'getOperationalStatus']);

        Route::get('/drivers/{driver}', [SearchController::class, 'showDriver']);
        Route::get('/vehicles/{vehicle}', [SearchController::class, 'showVehicle']);

        // ** MOVED THIS ROUTE HIGHER **
        // Order Search (For the left sidebar)
        Route::get('/orders/search', [SearchController::class, 'searchOrders']);

        // Orders CRUD
        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::put('orders/{order}', [OrderController::class, 'update']);
        Route::delete('orders/{order}', [OrderController::class, 'destroy']);

        // Trip Lifecycle
        Route::post('/trips', [TripController::class, 'store']);
        Route::get('/trips/search-assignments', [TripController::class, 'searchAssignments']);


        // User Management
        Route::get('roles', [UserManagementController::class, 'rolesList'])
            ->middleware('role:super_admin|Admin');
        Route::apiResource('users', UserManagementController::class)
            ->middleware('role:super_admin|Admin');

        // Role Management
        Route::get('roles/manage', [RoleManagementController::class, 'index'])
            ->middleware('role:super_admin|Admin');
        Route::post('roles/manage', [RoleManagementController::class, 'store'])
            ->middleware('role:super_admin|Admin');
        Route::put('roles/manage/{role}', [RoleManagementController::class, 'update'])
            ->middleware('role:super_admin|Admin');
        Route::delete('roles/manage/{role}', [RoleManagementController::class, 'destroy'])
            ->middleware('role:super_admin|Admin');
        Route::get('roles/manage/permissions-list', [RoleManagementController::class, 'permissionsList'])
            ->middleware('role:super_admin|Admin');

        // Clients
        Route::get('clients/search', [ClientController::class, 'search']);
        Route::get('clients', [ClientController::class, 'index']);
        Route::post('clients', [ClientController::class, 'store']);
        Route::get('clients/{client}', [ClientController::class, 'show']);
        Route::put('clients/{client}', [ClientController::class, 'update']);
        Route::delete('clients/{client}', [ClientController::class, 'destroy']);

        // Currencies
        Route::get('currencies', [CurrencyController::class, 'index']);
        Route::post('currencies', [CurrencyController::class, 'store']);
        Route::put('currencies/{currency}', [CurrencyController::class, 'update']);
        Route::delete('currencies/{currency}', [CurrencyController::class, 'destroy']);

        // Exchange Rates
        Route::get('exchange-rates', [\App\Http\Controllers\Api\ExchangeRateController::class, 'index']);
        Route::post('exchange-rates', [\App\Http\Controllers\Api\ExchangeRateController::class, 'store']);
        Route::put('exchange-rates/{exchange_rate}', [\App\Http\Controllers\Api\ExchangeRateController::class, 'update']);
        Route::delete('exchange-rates/{exchange_rate}', [\App\Http\Controllers\Api\ExchangeRateController::class, 'destroy']);

        // Workshop & Maintenance
        Route::prefix('workshop')->group(function () {
            Route::get('dashboard', [WsDashboardController::class, 'index']);

            Route::get('mechanics', [WsMechanicController::class, 'index']);
            Route::post('mechanics', [WsMechanicController::class, 'store']);
            Route::get('mechanics/search-users', [WsMechanicController::class, 'searchUsers']);

            Route::get('parts', [WsPartController::class, 'index']);
            Route::post('parts', [WsPartController::class, 'store']);
            Route::get('parts/{part}', [WsPartController::class, 'show']);
            Route::put('parts/{part}', [WsPartController::class, 'update']);
            Route::delete('parts/{part}', [WsPartController::class, 'destroy']);

            Route::get('warehouses', [WsWarehouseController::class, 'index']);
            Route::post('warehouses', [WsWarehouseController::class, 'store']);
            Route::get('warehouses/{warehouse}', [WsWarehouseController::class, 'show']);
            Route::put('warehouses/{warehouse}', [WsWarehouseController::class, 'update']);
            Route::delete('warehouses/{warehouse}', [WsWarehouseController::class, 'destroy']);
            Route::get('warehouses-list', [WsWarehouseController::class, 'list']);

            Route::get('stock-levels', [WsStockLevelController::class, 'index']);
            Route::post('stock-levels', [WsStockLevelController::class, 'store']);
            Route::put('stock-levels/{stockLevel}', [WsStockLevelController::class, 'update']);
            Route::post('stock-levels/adjust', [WsStockLevelController::class, 'adjust']);

            Route::get('stock-movements', [WsStockMovementController::class, 'index']);

            Route::get('vendors', [WsVendorController::class, 'index']);
            Route::post('vendors', [WsVendorController::class, 'store']);
            Route::get('vendors/{vendor}', [WsVendorController::class, 'show']);
            Route::put('vendors/{vendor}', [WsVendorController::class, 'update']);
            Route::delete('vendors/{vendor}', [WsVendorController::class, 'destroy']);
            Route::get('vendors-list', [WsVendorController::class, 'list']);

            Route::get('repair-requests', [WsRepairRequestController::class, 'index']);
            Route::post('repair-requests', [WsRepairRequestController::class, 'store']);
            Route::get('repair-requests/mechanics', [WsRepairRequestController::class, 'mechanics']);
            Route::get('repair-requests/vehicles', [WsRepairRequestController::class, 'vehicles']);
            Route::get('repair-requests/search-vehicles', [WsRepairRequestController::class, 'searchVehicles']);
            Route::get('repair-requests/{repairRequest}', [WsRepairRequestController::class, 'show']);
            Route::post('repair-requests/{repairRequest}/submit', [WsRepairRequestController::class, 'submit']);
            Route::post('repair-requests/{repairRequest}/request-approval', [WsRepairRequestController::class, 'requestApproval']);
            Route::post('repair-requests/{repairRequest}/approve', [WsRepairRequestController::class, 'approve']);
            Route::post('repair-requests/{repairRequest}/reject', [WsRepairRequestController::class, 'reject']);
            Route::post('repair-requests/{repairRequest}/assign-mechanic', [WsRepairRequestController::class, 'assignMechanic']);
            Route::post('repair-requests/{repairRequest}/reassign-mechanic', [WsRepairRequestController::class, 'reassignMechanic']);
            Route::post('repair-requests/start-work/{assignmentId}', [WsRepairRequestController::class, 'startWork']);
            Route::post('repair-requests/complete-work/{assignmentId}', [WsRepairRequestController::class, 'completeWork']);
            Route::post('repair-requests/{repairRequest}/use-parts', [WsRepairRequestController::class, 'useParts']);
            Route::post('repair-requests/{repairRequest}/release', [WsRepairRequestController::class, 'release']);
            Route::post('repair-requests/{repairRequest}/cancel', [WsRepairRequestController::class, 'cancel']);

            Route::get('purchase-orders', [WsPurchaseOrderController::class, 'index']);
            Route::post('purchase-orders', [WsPurchaseOrderController::class, 'store']);
            Route::get('purchase-orders/{purchaseOrder}', [WsPurchaseOrderController::class, 'show']);
            Route::post('purchase-orders/{purchaseOrder}/send', [WsPurchaseOrderController::class, 'send']);
            Route::post('purchase-orders/{purchaseOrder}/confirm', [WsPurchaseOrderController::class, 'confirm']);
            Route::post('purchase-orders/{purchaseOrder}/receive', [WsPurchaseOrderController::class, 'receive']);
            Route::post('purchase-orders/{purchaseOrder}/cancel', [WsPurchaseOrderController::class, 'cancel']);

            Route::get('part-requests', [WsPartRequestController::class, 'index']);
            Route::post('part-requests', [WsPartRequestController::class, 'store']);
            Route::get('part-requests/{partRequest}', [WsPartRequestController::class, 'show']);
            Route::post('part-requests/{partRequest}/approve', [WsPartRequestController::class, 'approve']);
            Route::post('part-requests/{partRequest}/reject', [WsPartRequestController::class, 'reject']);
        });

        // Profile
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::post('password/send-reset-link', [ProfileController::class, 'sendResetLink']);

        // Audit Logs
        Route::get('audit-logs', [AuditLogController::class, 'index']);
        Route::get('audit-logs/timeline/{type}/{id}', [AuditLogController::class, 'show']);

        // Support System Nested Group
        Route::prefix('support')->group(function () {
            // Categories
            Route::get('categories', [SupportCategoryController::class, 'index']);
            Route::post('categories', [SupportCategoryController::class, 'store']);
            Route::put('categories/{supportCategory}', [SupportCategoryController::class, 'update']);
            Route::delete('categories/{supportCategory}', [SupportCategoryController::class, 'destroy']);
            Route::get('categories/stats', [SupportTicketController::class, 'categoryStats']);
            // Searches for ticket creation
            Route::get('users/search', [SupportTicketController::class, 'searchUsers']);
            Route::get('vehicles/search', [SupportTicketController::class, 'searchVehicles']);
            // Tickets
            Route::get('tickets', [SupportTicketController::class, 'index']);
            Route::post('tickets', [SupportTicketController::class, 'store']);
            Route::get('tickets/{ticket}', [SupportTicketController::class, 'show']);
            Route::patch('tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus']);
            Route::patch('tickets/{ticket}/assign', [SupportTicketController::class, 'assign']);
            Route::post('tickets/{ticket}/messages', [SupportTicketMessageController::class, 'store']);
        });
    });

    // Customer Portal API (authenticated routes — outside /portal prefix)
    Route::prefix('customer')->group(function () {
        Route::post('auth/verify-email', [CustomerAuthController::class, 'verifyEmail']);
        Route::post('auth/resend-verification', [CustomerAuthController::class, 'resendVerification']);
        Route::post('auth/logout', [CustomerAuthController::class, 'logout']);
        Route::get('auth/me', [CustomerAuthController::class, 'me']);
        Route::get('orders', [CustomerOrderController::class, 'index']);
        Route::post('orders', [CustomerOrderController::class, 'store']);
        Route::get('orders/{order}', [CustomerOrderController::class, 'show']);
    });
});
