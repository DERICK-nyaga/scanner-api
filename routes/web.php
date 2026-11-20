<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\LinehaulDispatchController;
use App\Http\Controllers\Auth\PasswordlessAuthController;
use App\Http\Controllers\IncomingPackageController;
use Illuminate\Support\Facades\Route;
use App\Models\Station;
use App\Models\ReturnedPackage;
use App\Models\PublishedAuditLog;
use App\Models\DeliveryNote;
use App\Models\SmsNotification;
use App\Models\LinehaulDispatch;
use App\Models\ScanLog;
use App\Models\IncomingPackage;
use App\Models\ReturnPickup;
use App\Http\Controllers\SmsConsoleController;

// 0. AUTHENTICATION & ROOT REDIRECTION
Route::get('/login', [PasswordlessAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login/request-access', [PasswordlessAuthController::class, 'authenticateByEmail'])->name('login.submit');
Route::post('/logout', [PasswordlessAuthController::class, 'logout'])->name('logout');

Route::get('/', function () {
    return redirect()->route('dashboard.index');
});

// 1. DASHBOARD & SPA SCANNER BLADE VIEWS
Route::get('/dashboard', function () {
    $masterlistCount = ScanLog::where('is_archived', false)->count();
    $incomingCount   = IncomingPackage::whereDate('created_at', today())->count();
    $returnsCount    = ReturnedPackage::where('status', '!=', 'Returned to Jumia Network')->count();
    $otpCount        = ReturnPickup::where('otp_verified', true)->count();

    return view('dashboard.index', compact('masterlistCount', 'incomingCount', 'returnsCount', 'otpCount'));
})->name('dashboard.index');

// 2. CONSOLIDATED ADMIN WEB INTERFACE ROUTES (/admin)
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    Route::get('/station-emails', [AdminController::class, 'indexStationEmails'])->name('admin.station-emails.index');
    Route::post('/station-emails', [AdminController::class, 'storeStationEmail'])->name('admin.station-emails.store');
    Route::delete('/station-emails/{id}', [AdminController::class, 'destroyStationEmail'])->name('admin.station-emails.destroy');

    Route::get('/returns', function () {
        $returns = ReturnedPackage::with('pickup')->get();
        return view('admin.returns', compact('returns'));
    })->name('admin.returns');

    Route::get('/returns/create', function () {
        $returns = ReturnedPackage::with('pickup')->latest()->get();
        $stations = Station::all();
        return view('admin.returns.create', compact('returns', 'stations'));
    })->name('admin.returns.create');

    Route::get('/returns/scanner', function () {
        return view('admin.returns.scanner');
    })->name('admin.returns.scanner');

    Route::post('/returns', [AdminController::class, 'storeReturnPackage'])->name('admin.returns.store');
    Route::post('/returns/batch-store', [LinehaulDispatchController::class, 'storeBatchReturnPackages'])->name('admin.returns.batch-store');
    Route::post('/returns/{id}/pickup', [AdminController::class, 'createReturnPickup'])->name('admin.returns.pickup');
    Route::post('/returns/otp/{pickupId}', [AdminController::class, 'verifyReturnOtp'])->name('admin.returns.verifyOtp');

    Route::get('/linehaul-dispatches', function () {
        $dispatches = LinehaulDispatch::with('packages')->latest()->get();
        return view('admin.linehaul.index', compact('dispatches'));
    })->name('admin.linehaul.index');

    Route::get('/linehauls', [LinehaulDispatchController::class, 'indexLinehauls'])->name('admin.linehauls.index');
    Route::get('/linehauls/create', [LinehaulDispatchController::class, 'createLinehaulView'])->name('admin.linehauls.create');
    Route::post('/linehauls', [LinehaulDispatchController::class, 'storeLinehaul'])->name('admin.linehauls.store');

    Route::get('/audit-logs', function () {
        $publishedLogs = PublishedAuditLog::latest()->get();
        return view('admin.audit_logs', compact('publishedLogs'));
    })->name('admin.audit.logs');

    Route::get('/delivery-notes', [AdminController::class, 'indexDeliveryNotes'])->name('admin.delivery-notes.index');
    Route::post('/delivery-notes', [AdminController::class, 'storeDeliveryNote'])->name('admin.delivery-notes.store');

    Route::get('/sms-logs', function () {
        $smsLogs = SmsNotification::with('station')->latest()->get();
        return view('admin.sms_logs', compact('smsLogs'));
    })->name('admin.sms.logs');

    Route::get('/stations', [AdminController::class, 'indexStationsView'])->name('admin.stations.index');
    Route::post('/stations', [AdminController::class, 'storeStationForm'])->name('admin.stations.store');
    Route::put('/stations/{id}', [AdminController::class, 'updateStationForm'])->name('admin.stations.update');
    Route::delete('/stations/{id}', [AdminController::class, 'destroyStationForm'])->name('admin.stations.destroy');

    Route::post('/master-upload', [AdminController::class, 'uploadMasterListForm'])->name('admin.master.upload');
    Route::post('/publish-audit', [AdminController::class, 'publishAuditLogForm'])->name('admin.audit.publish');

    Route::get('/historical-master-lists', [AdminController::class, 'indexHistoricalMasterLists'])->name('admin.historical.master');
    Route::get('/scan-logs', [AdminController::class, 'indexScanLogs'])->name('admin.scan.logs');
    Route::post('/save-batch', [AdminController::class, 'saveAndArchiveBatchForm'])->name('admin.batch.save');

    Route::get('/incoming', [IncomingPackageController::class, 'index'])->name('admin.incoming.index');
    Route::get('/incoming/create', [IncomingPackageController::class, 'create'])->name('admin.incoming.create');
    Route::post('/incoming', [IncomingPackageController::class, 'store'])->name('admin.incoming.store');
});

// 3. SPA PWA CATCH-ALL ROUTE (Must be at the very bottom)
Route::get('/app/{any?}', function () {
    return view('dashboard.index'); 
})->where('any', '.*')->name('app.main');

Route::get('/sms-console', [SmsConsoleController::class, 'index'])->name('sms.console');
