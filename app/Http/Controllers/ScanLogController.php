<?php

namespace App\Http\Controllers;

use App\Models\ScanLog;
use App\Models\MasterList;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScanLogController extends Controller {
    public function storeBulk(Request $request) {
        $validated = $request->validate([
            'station_id'          => 'required|exists:stations,id',
            'scans'               => 'required|array',
            'scans.*.client_id'   => 'required|string',
            'scans.*.code'        => 'required|string',
            'scans.*.scanned_at'  => 'required|string',
            'scans.*.method'      => 'required|string',
            'scans.*.note'        => 'nullable|string',
        ]);

        $stationId = $validated['station_id'];
        $syncedCount = 0;

        DB::transaction(function () use ($validated, $stationId, &$syncedCount) {
            foreach ($validated['scans'] as $scan) {
                $code = trim($scan['code']);
                
                // Check against master list for this specific station
                $masterItem = MasterList::where('station_id', $stationId)
                    ->where('tracking_number', $code)
                    ->first();

                $matchStatus = $masterItem ? 'matched' : 'unexpected';

                // 1. Log or update the scan record for this station
                ScanLog::updateOrCreate(
                    ['tracking_number' => $code, 'station_id' => $stationId],
                    [
                        'scanned_at'   => $scan['scanned_at'],
                        'method'       => $scan['method'],
                        'note'         => $scan['note'] ?? '',
                        'match_status' => $matchStatus,
                    ]
                );

                // 2. Keep general scan tracking updated
                Scan::updateOrCreate(
                    ['client_id' => $scan['client_id']],
                    [
                        'code'       => $code,
                        'scanned_at' => $scan['scanned_at'],
                        'method'     => $scan['method'],
                        'note'       => $scan['note'] ?? '',
                    ]
                );

                // 3. Mark master list item as scanned to prevent double entry
                if ($masterItem) {
                    $masterItem->update(['status' => 'scanned']);
                }

                $syncedCount++;
            }
        });

        return response()->json(['synced' => $syncedCount]);
    }
}