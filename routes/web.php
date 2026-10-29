<?php

use Illuminate\Support\Facades\Route;
use App\Models\Station;
use App\Models\ReturnedPackage;
use App\Models\PublishedAuditLog;
use App\Models\DeliveryNote;
use App\Models\SmsNotification;
use App\Http\Controllers\AdminController;

// Redirect root to the Vue Scanner PWA
Route::get('/', function () {
    return redirect('/app/');
});

// Consolidated Admin Web Interface Routes
Route::prefix('admin')->group(function () {
    
    // Dashboard view for account management & master list uploads
    Route::get('/dashboard', function () {
        $stations = Station::all();
        return view('admin.dashboard', compact('stations'));
    })->name('admin.dashboard');

    // Returns & Jumia reverse logistics view
    Route::get('/returns', function () {
        $returns = ReturnedPackage::with('pickup')->get();
        return view('admin.returns', compact('returns'));
    })->name('admin.returns');

    // Published & Locked Audit Logs View
    Route::get('/audit-logs', function () {
        $publishedLogs = PublishedAuditLog::latest()->get();
        return view('admin.audit_logs', compact('publishedLogs'));
    })->name('admin.audit.logs');

    // Delivery Notes & Waybills View
    Route::get('/delivery-notes', function () {
        $stations = Station::all();
        $deliveryNotes = DeliveryNote::with('station')->latest()->get();
        return view('admin.delivery_notes', compact('deliveryNotes', 'stations'));
    })->name('admin.delivery-notes.index');
    Route::get('/delivery-notes', [AdminController::class, 'indexDeliveryNotes'])->name('admin.delivery-notes.index');
    Route::post('/delivery-notes', [AdminController::class, 'storeDeliveryNote'])->name('admin.delivery-notes.store');

    // Customer SMS Notification Logs View
    Route::get('/sms-logs', function () {
        $smsLogs = SmsNotification::with('station')->latest()->get();
        return view('admin.sms_logs', compact('smsLogs'));
    })->name('admin.sms.logs');

    // Station CRUD Web Interface
    Route::get('/stations', [AdminController::class, 'indexStationsView'])->name('admin.stations.index');
    Route::post('/stations', [AdminController::class, 'storeStationForm'])->name('admin.stations.store');
    Route::put('/stations/{id}', [AdminController::class, 'updateStationForm'])->name('admin.stations.update');
    Route::delete('/stations/{id}', [AdminController::class, 'destroyStationForm'])->name('admin.stations.destroy');

    // Form Action Endpoints
    Route::post('/master-upload', [AdminController::class, 'uploadMasterListForm'])->name('admin.master.upload');
    Route::post('/publish-audit', [AdminController::class, 'publishAuditLogForm'])->name('admin.audit.publish');
});