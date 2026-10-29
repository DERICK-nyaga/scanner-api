<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\MasterList;
use App\Models\PublishedAuditLog;
use App\Models\SmsNotification;
use App\Models\ReturnedPackage;
use App\Models\ReturnPickup;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Models\DeliveryNote;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller {


    // Render Delivery Notes Management View
    public function indexDeliveryNotes()
    {
        $stations = Station::all();
        $deliveryNotes = DeliveryNote::with('station')->latest()->get();
        
        return view('admin.delivery_notes', compact('deliveryNotes', 'stations'));
    }
    // Store Uploaded Delivery Note / Waybill
    public function storeDeliveryNote(Request $request)
    {
        $request->validate([
            'station_id'    => 'required|exists:stations,id',
            'delivery_note' => 'required|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
        ]);

        $file = $request->file('delivery_note');
        
        // Store the file in storage/app/public/delivery_notes
        $path = $file->store('delivery_notes', 'public');

        DeliveryNote::create([
            'station_id'        => $request->station_id,
            'original_filename' => $file->getClientOriginalName(),
            'file_path'         => $path,
        ]);

        return back()->with('success', 'Delivery note/waybill uploaded successfully.');
    }

    /**
     * Generate secure OTP for agent package return pickups.
     */
    public function createReturnPickup(Request $request, $returnedPackageId) {
        $validated = $request->validate([
            'agent_name' => 'required|string',
            'agent_phone' => 'required|string',
            'agent_id_details' => 'required|string',
        ]);

        // Generate 6-digit secure OTP
        $otpCode = rand(100000, 999999);

        $pickup = ReturnPickup::create([
            'returned_package_id' => $returnedPackageId,
            'agent_name' => $validated['agent_name'],
            'agent_phone' => $validated['agent_phone'],
            'agent_id_details' => $validated['agent_id_details'],
            'otp_code' => $otpCode,
            'otp_verified' => false,
        ]);

        // Send OTP to agent phone & admin/station user notification log
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

    /**
     * Verify agent OTP to complete return handover.
     */
    public function verifyReturnOtp(Request $request, $pickupId) {
        $request->validate(['otp_code' => 'required|digits:6']);

        $pickup = ReturnPickup::findOrFail($pickupId);

        if ($pickup->otp_code === $request->otp_code) {
            $pickup->update(['otp_verified' => true]);
            
            // Update package status to returned/dispatched
            $pickup->returnedPackage()->update(['status' => 'Returned to Jumia Network']);

            return response()->json([
                'message' => 'OTP verified successfully. Package return acknowledged and locked.'
            ]);
        }

        return response()->json(['message' => 'Invalid OTP code provided.'], 400);
    }

    /**
     * Process and dispatch automated SMS reminders for packages nearing expiration (Day 8/9 check).
     */
    public function processCustomerSmsReminders(Request $request, $stationId) {
        $station = Station::with('masterLists')->findOrFail($stationId);
        
        $sentCount = 0;
        foreach ($station->masterLists as $item) {
            if (!empty($item->customer_phone)) {
                // Check days remaining until expiration (Day 8/9 check)
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

    // Fetch all stations for the frontend dropdown switcher
    // public function indexStations() {
    //     return response()->json(Station::withCount(['masterLists', 'scanLogs'])->get());
    // }

    public function indexStations()
    {
        try {
            $stations = \App\Models\Station::all();
            return response()->json($stations);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    // Fetch station details including its uploaded master list items
    public function getStationData($stationId) {
        $station = Station::with('masterLists')->findOrFail($stationId);

        // Map master list items to match the expected front-end structure
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

    // Upload master list tied specifically to the target station
    public function uploadMasterList(Request $request, $stationId) {
        $request->validate(['file' => 'required|mimes:csv,txt']);
        
        $station = Station::findOrFail($stationId);
        $path = $request->file('file')->getRealPath();
        $file = fopen($path, 'r');
        $header = fgetcsv($file); // Expected headers: Tracking #, Hub, Aging

        $count = 0;
        while (($row = fgetcsv($file)) !== FALSE) {
            $trackingNumber = trim($row[0] ?? '');
            $hub = trim($row[1] ?? '');
            $agingDays = intval($row[2] ?? 0); // Exact aging from CSV (e.g., 4)

            if (empty($trackingNumber)) continue;

            // Backdate arrival_date so aging continues counting forward dynamically
            $arrivalDate = now()->subDays($agingDays);

            MasterList::updateOrCreate(
                ['station_id' => $station->id, 'tracking_number' => $trackingNumber],
                [
                    'hub'             => $hub,
                    'aging'           => $agingDays,
                    'arrival_date'    => $arrivalDate,
                    'expiration_date' => $arrivalDate->copy()->addDays(10), // 10-day total limit
                ]
            );
            $count++;
        }
        fclose($file);

        return back()->with('success', "Successfully imported {$count} records. Aging values initialized from CSV.");
    }

    // Create a new station account (e.g. KE-3PL-Flexcom Cheptiret-Station)
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

    // Update an existing station
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

    // Render Stations CRUD Management View
    public function indexStationsView() {
        $stations = Station::withCount(['masterLists', 'scanLogs'])->get();
        return view('admin.stations', compact('stations'));
    }

    // Store a new station
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

    // Update an existing station
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

    // Delete a station
    public function destroyStationForm($id) {
        $station = Station::findOrFail($id);
        $station->delete();

        return back()->with('success', 'Station deleted successfully.');
    }

    // Delete a station
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

    // Web form version for uploading master list from /admin/dashboard
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
        $header = fgetcsv($file); // expected headers: Tracking #, Hub, Aging (or similar)

        $count = 0;
        while (($row = fgetcsv($file)) !== FALSE) {
            $trackingNumber = trim($row[0] ?? '');
            if (empty($trackingNumber)) continue;

            $hub = trim($row[1] ?? 'KE-3PL-Flexcom');
            $agingDays = intval($row[2] ?? 0);
            
            // Backdate arrival_date so aging continues counting forward dynamically
            $arrivalDate = now()->subDays($agingDays);

            MasterList::updateOrCreate(
                ['station_id' => $stationId, 'tracking_number' => $trackingNumber],
                [
                    'hub'             => $hub,
                    'aging'           => $agingDays,
                    'arrival_date'    => $arrivalDate,
                    'expiration_date' => $arrivalDate->copy()->addDays(10),
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

        $logs = \App\Models\ScanLog::where('station_id', $station->id)
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

    // API version for publishing audit log
    public function publishAuditLog(Request $request, $stationId) {
        $date = $request->input('audit_date', today());
        $logs = \App\Models\ScanLog::where('station_id', $stationId)
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