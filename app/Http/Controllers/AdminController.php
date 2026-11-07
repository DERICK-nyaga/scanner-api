<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\MasterList;
use App\Models\PublishedAuditLog;
use App\Models\SmsNotification;
use App\Models\ReturnedPackage;
use App\Models\ReturnPickup;
use Illuminate\Http\Request;
use App\Models\ScanLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Models\DeliveryNote;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller {

    public function index(Request $request)
    {
        $stationId = $request->input('station_id');
        
        $scans = ScanLog::where('station_id', $stationId)
            ->where('is_archived', false)
            ->latest()
            ->get();

        return response()->json($scans);
    }

    public function indexDeliveryNotes()
    {
        $stations = Station::all();
        $deliveryNotes = DeliveryNote::with('station')->latest()->get();
        
        return view('admin.delivery_notes', compact('deliveryNotes', 'stations'));
    }

    public function storeDeliveryNote(Request $request)
    {
        $request->validate([
            'station_id'    => 'required|exists:stations,id',
            'delivery_note' => 'required|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $file = $request->file('delivery_note');
        $path = $file->store('delivery_notes', 'public');

        DeliveryNote::create([
            'station_id'        => $request->station_id,
            'original_filename' => $file->getClientOriginalName(),
            'file_path'         => $path,
        ]);

        return back()->with('success', 'Delivery note/waybill uploaded successfully.');
    }

    public function createReturnPickup(Request $request, $returnedPackageId) {
        $validated = $request->validate([
            'agent_name' => 'required|string',
            'agent_phone' => 'required|string',
            'agent_id_details' => 'required|string',
        ]);

        $otpCode = rand(100000, 999999);

        $pickup = ReturnPickup::create([
            'returned_package_id' => $returnedPackageId,
            'agent_name' => $validated['agent_name'],
            'agent_phone' => $validated['agent_phone'],
            'agent_id_details' => $validated['agent_id_details'],
            'otp_code' => $otpCode,
            'otp_verified' => false,
        ]);

        SmsNotification::create([
            'station_id' => 1,
            'recipient_phone' => $validated['agent_phone'],
            'message_content' => "Your Jumia Return Pickup OTP code is: {$otpCode}. Provide this to station staff upon package handover.",
            'status' => 'sent'
        ]);

        return response()->json([
            'message' => 'OTP generated and sent to pickup agent successfully.', 
            'pickup_id' => $pickup->id
        ]);
    }

    public function verifyReturnOtp(Request $request, $pickupId) {
        $request->validate(['otp_code' => 'required|digits:6']);

        $pickup = ReturnPickup::findOrFail($pickupId);

        if ($pickup->otp_code === $request->otp_code) {
            $pickup->update(['otp_verified' => true]);
            $pickup->returnedPackage()->update(['status' => 'Returned to Jumia Network']);

            return response()->json([
                'message' => 'OTP verified successfully. Package return acknowledged and locked.'
            ]);
        }

        return response()->json(['message' => 'Invalid OTP code provided.'], 400);
    }

    public function processCustomerSmsReminders(Request $request, $stationId) {
        $station = Station::with('masterLists')->findOrFail($stationId);
        
        $sentCount = 0;
        foreach ($station->masterLists as $item) {
            if (!empty($item->customer_phone)) {
                $daysRemaining = now()->diffInDays($item->expiration_date, false);
                
                if ($daysRemaining <= 2 && $daysRemaining >= 0) {
                    $message = "Dear {$item->customer_name}, your package {$item->tracking_number} is expiring soon at our station. Please collect it before {$item->expiration_date->format('Y-m-d')}.";
                    
                    SmsNotification::create([
                        'station_id' => $station->id,
                        'recipient_phone' => $item->customer_phone,
                        'message_content' => $message,
                        'status' => 'sent'
                    ]);
                    $sentCount++;
                }
            }
        }

        return back()->with('success', "Successfully dispatched {$sentCount} automated SMS reminders to customers.");
    }

    public function indexStations() {
        return response()->json(Station::withCount(['masterLists', 'scanLogs'])->get());
    }

    public function getStationDetails($stationId) {
        $station = Station::with(['masterLists' => function($query) {
            $query->where('is_active', true);
        }])->findOrFail($stationId);

        $masterList = $station->masterLists->map(function ($item) {
            return [
                'tracking_number' => $item->tracking_number,
                'hub'             => $item->hub ?? 'KE-3PL-Flexcom',
                'aging'           => $item->aging ?? 0,
                'arrival_date'    => $item->arrival_date ?? $item->created_at,
            ];
        });

        return response()->json([
            'station'     => $station,
            'master_list' => $masterList,
        ]);
    }

    public function updateStationSettings(Request $request)
    {
        $request->validate([
            'station_ids' => 'required',
            'disable_manual' => 'required|boolean'
        ]);

        $stationIds = $request->input('station_ids');
        $disableManual = $request->input('disable_manual');

        if ($stationIds === 'all' || (is_array($stationIds) && in_array('all', $stationIds))) {
            Station::query()->update(['disable_manual' => $disableManual]);
            $count = Station::count();
        } else {
            $ids = is_array($stationIds) ? $stationIds : [$stationIds];
            Station::whereIn('id', $ids)->update(['disable_manual' => $disableManual]);
            $count = count($ids);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Security settings successfully updated for {$count} station(s)."
        ]);
    }

    public function uploadMasterList(Request $request, $stationId) {
        $request->validate(['file' => 'required|mimes:csv,txt']);
        
        return $this->processMasterListUpload($request, $stationId, false);
    }

    public function getMasterListItems(Request $request, $stationId)
    {
        $masterList = MasterList::where('station_id', $stationId)
            ->where('is_active', true)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'master_list' => $masterList
        ]);
    }

    public function clearMasterList(Request $request, $stationId)
    {
        // 1. Deactivate active master list items
        MasterList::where('station_id', $stationId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // 2. Archive scan logs for this station as well
        ScanLog::where('station_id', $stationId)
            ->where('is_archived', false)
            ->update(['is_archived' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Master list table and active scans reset to blank. Records archived safely.'
        ]);
    }

    public function indexHistoricalMasterLists(Request $request)
    {
        $stations = Station::all();
        $stationId = $request->input('station_id');

        $query = MasterList::with('station')->latest();

        if ($stationId) {
            $query->where('station_id', $stationId);
        }

        $historicalMasterLists = $query->paginate(50);

        return view('admin.historical_master_lists', compact('historicalMasterLists', 'stations', 'stationId'));
    }

    public function indexScanLogs(Request $request)
    {
        $stations = Station::all();
        $stationId = $request->input('station_id');
        $date = $request->input('date');

        $query = ScanLog::with('station')->latest();

        if ($stationId) {
            $query->where('station_id', $stationId);
        }
        if ($date) {
            $query->whereDate('scanned_at', $date);
        }

        $scanLogs = $query->paginate(50);

        return view('admin.scan_logs', compact('scanLogs', 'stations', 'stationId', 'date'));
    }

    public function saveAndArchiveBatch(Request $request, $stationId)
    {
        $station = Station::findOrFail($stationId);
        
        $activeLogs = ScanLog::where('station_id', $stationId)
            ->where('is_archived', false)
            ->get();

        if ($activeLogs->isNotEmpty()) {
            PublishedAuditLog::create([
                'station_id'    => $station->id,
                'audit_date'    => today(),
                'snapshot_data' => $activeLogs->toArray(),
                'published_by'  => Auth::id() ?? $station->id,
            ]);

            ScanLog::where('station_id', $stationId)
                ->where('is_archived', false)
                ->update(['is_archived' => true]);
        }

        MasterList::where('station_id', $stationId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Batch successfully saved, locked for future reference, and cleared from the client PWA.'
        ]);
    }

    public function saveAndArchiveBatchForm(Request $request)
    {
        $request->validate([
            'station_id' => 'required|exists:stations,id'
        ]);

        $stationId = $request->station_id;
        $station = Station::findOrFail($stationId);
        
        $activeLogs = ScanLog::where('station_id', $stationId)
            ->where('is_archived', false)
            ->get();

        if ($activeLogs->isNotEmpty()) {
            PublishedAuditLog::create([
                'station_id'    => $station->id,
                'audit_date'    => today(),
                'snapshot_data' => $activeLogs->toArray(),
                'published_by'  => Auth::id() ?? $station->id,
            ]);

            ScanLog::where('station_id', $stationId)
                ->where('is_archived', false)
                ->update(['is_archived' => true]);
        }

        MasterList::where('station_id', $stationId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return back()->with('success', 'Batch successfully saved, locked for future reference, and cleared from the client PWA.');
    }

    public function storeStation(Request $request) {
        $validated = $request->validate([
            'unique_identifier' => 'required|unique:stations',
            'name' => 'required',
            'email' => 'required|email|unique:stations',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $station = Station::create($validated);

        return response()->json(['message' => 'Station created successfully', 'station' => $station]);
    }

    public function updateStation(Request $request, $id) {
        $station = Station::findOrFail($id);
        
        $validated = $request->validate([
            'unique_identifier' => 'required|unique:stations,unique_identifier,' . $id,
            'name' => 'required',
            'email' => 'required|email|unique:stations,email,' . $id,
            'role' => 'required'
        ]);

        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->password);
        }

        $station->update($validated);

        return response()->json(['message' => 'Station updated successfully', 'station' => $station]);
    }

    public function indexStationsView() {
        $stations = Station::withCount(['masterLists', 'scanLogs'])->get();
        return view('admin.stations', compact('stations'));
    }

    public function storeStationForm(Request $request) {
        $validated = $request->validate([
            'unique_identifier' => 'required|string|unique:stations,unique_identifier',
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|unique:stations,email',
            'password'          => 'required|min:6',
            'role'              => 'required|in:superadmin,admin,user'
        ]);

        $validated['password'] = bcrypt($validated['password']);
        Station::create($validated);

        return back()->with('success', 'Station created successfully.');
    }

    public function updateStationForm(Request $request, $id) {
        $station = Station::findOrFail($id);

        $validated = $request->validate([
            'unique_identifier' => 'required|string|unique:stations,unique_identifier,' . $id,
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|unique:stations,email,' . $id,
            'role'              => 'required|in:superadmin,admin,user'
        ]);

        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->password);
        }

        $station->update($validated);

        return back()->with('success', 'Station updated successfully.');
    }

    public function destroyStationForm($id) {
        $station = Station::findOrFail($id);
        $station->delete();

        return back()->with('success', 'Station deleted successfully.');
    }

    public function destroyStation($id) {
        $station = Station::findOrFail($id);
        $station->delete();

        return response()->json(['message' => 'Station deleted successfully']);
    }

    public function manageAccounts() {
        return response()->json(Station::with('masterLists')->get());
    }

    public function storeAccount(Request $request) {
        $validated = $request->validate([
            'unique_identifier' => 'required|unique:stations',
            'name' => 'required',
            'email' => 'required|email|unique:stations',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $station = Station::create($validated);

        return response()->json(['message' => 'Account created successfully', 'station' => $station]);
    }

    public function uploadMasterListForm(Request $request) {
        $request->validate([
            'station_id' => 'required|exists:stations,id',
            'file' => 'required|mimes:csv,txt'
        ]);

        return $this->processMasterListUpload($request, $request->station_id, false);
    }

    protected function processMasterListUpload(Request $request, $stationId, $isApi) {
        $path = $request->file('file')->getRealPath();
        $file = fopen($path, 'r');
        $header = fgetcsv($file);

        // Deactivate old master list items & archive active scans on fresh import
        MasterList::where('station_id', $stationId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        ScanLog::where('station_id', $stationId)
            ->where('is_archived', false)
            ->update(['is_archived' => true]);

        $count = 0;
        while (($row = fgetcsv($file)) !== FALSE) {
            $trackingNumber = trim($row[0] ?? '');
            if (empty($trackingNumber)) continue;

            $hub = trim($row[1] ?? 'KE-3PL-Flexcom');
            $agingDays = intval($row[2] ?? 0);
            
            $arrivalDate = now()->subDays($agingDays);

            MasterList::updateOrCreate(
                ['station_id' => $stationId, 'tracking_number' => $trackingNumber],
                [
                    'hub'             => $hub,
                    'aging'           => $agingDays,
                    'arrival_date'    => $arrivalDate,
                    'expiration_date' => $arrivalDate->copy()->addDays(10),
                    'is_active'       => true
                ]
            );
            $count++;
        }
        fclose($file);

        if ($isApi) {
            return response()->json(['message' => "Successfully imported {$count} records."]);
        }

        return back()->with('success', "Successfully imported {$count} records into the station master list.");
    }

    public function publishAuditLogForm(Request $request) {
        $request->validate([
            'audit_date' => 'required|date'
        ]);

        $station = Station::first();
        if (!$station) {
            return back()->with('error', 'Please create a station account first before publishing audit logs.');
        }

        $date = $request->input('audit_date');

        $logs = ScanLog::where('station_id', $station->id)
            ->whereDate('scanned_at', $date)
            ->get();

        PublishedAuditLog::create([
            'station_id' => $station->id,
            'audit_date' => $date,
            'snapshot_data' => $logs->toArray(),
            'published_by' => $station->id 
        ]);

        return back()->with('success', 'Audit log locked & published successfully for ' . $date);
    }

    public function publishAuditLog(Request $request, $stationId) {
        $date = $request->input('audit_date', today());
        $logs = ScanLog::where('station_id', $stationId)
            ->whereDate('scanned_at', $date)
            ->get();

        $published = PublishedAuditLog::create([
            'station_id' => $stationId,
            'audit_date' => $date,
            'snapshot_data' => $logs->toArray(),
            'published_by' => 1
        ]);

        return response()->json(['message' => 'Audit log locked & published successfully.', 'published' => $published]);
    }
}