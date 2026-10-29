<?php

namespace App\Http\Controllers;

use App\Models\ScanLog;
use App\Models\MasterList;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScanLogController extends Controller {
    public function storeBulk(Request $request) {
        $validated = $request->validate([
            'scans' => 'required|array',
            'scans.*.client_id' => 'required',
            'scans.*.code' => 'required',
            'scans.*.scanned_at' => 'required',
            'scans.*.method' => 'required',
        ]);

        $syncedCount = 0;
        foreach ($validated['scans'] as $scan) {
            $code = trim($scan['code']);
            $existsInMaster = MasterList::where('tracking_number', $code)->exists();
            $matchStatus = $existsInMaster ? 'matched' : 'unexpected';

            ScanLog::updateOrCreate(
                ['tracking_number' => $code],
                [
                    'station_id' => 1, // Adjust based on authenticated station context
                    'scanned_at' => $scan['scanned_at'],
                    'method' => $scan['method'],
                    'match_status' => $matchStatus,
                ]
            );
            $syncedCount++;
        }

        return response()->json(['synced' => $syncedCount]);
    }
}