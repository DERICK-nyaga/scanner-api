<?php

use App\Http\Controllers\ScanController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ScanLogController;
use Illuminate\Support\Facades\Route;

// Protected Scanner & Admin API Routes
// Route::middleware(ApiToken::class)->group(function () {
    
    // Original Scans API
    Route::prefix('scans')->group(function () {
        Route::get('/', [ScanController::class, 'index']);
        Route::post('/bulk', [ScanLogController::class, 'storeBulk']); 
        Route::get('/export', [ScanController::class, 'export']);
        Route::delete('/{clientId}', [ScanController::class, 'destroy']);
    });

    // Admin & Station Management API
    Route::prefix('admin')->group(function () {
        Route::get('/accounts', [AdminController::class, 'manageAccounts']);
        Route::post('/accounts', [AdminController::class, 'storeAccount']);
        Route::post('/stations/{stationId}/master-list', [AdminController::class, 'uploadMasterList']);
        Route::post('/stations/{stationId}/publish-audit', [AdminController::class, 'publishAuditLog']);
        
        // Automated SMS Reminders & Reverse Logistics / OTP Processing APIs
        Route::post('/stations/{stationId}/sms-reminders', [AdminController::class, 'processCustomerSmsReminders']);
        Route::post('/returns/{returnedPackageId}/pickup', [AdminController::class, 'createReturnPickup']);
        Route::post('/returns/pickups/{pickupId}/verify-otp', [AdminController::class, 'verifyReturnOtp']);
    });

    // Station CRUD and Data Details API
    Route::prefix('admin')->group(function () {
        Route::get('/stations', [AdminController::class, 'indexStations']);
        Route::get('/stations/{stationId}/details', [AdminController::class, 'getStationData']);
        Route::post('/stations/{stationId}/master-list', [AdminController::class, 'uploadMasterList']);
        Route::post('/stations', [AdminController::class, 'storeStation']);
        Route::put('/stations/{id}', [AdminController::class, 'updateStation']);
        Route::delete('/stations/{id}', [AdminController::class, 'destroyStation']);
    });

// });