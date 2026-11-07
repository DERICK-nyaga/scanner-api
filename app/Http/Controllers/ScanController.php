<?php

namespace App\Http\Controllers;

use App\Models\ScanLog;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function index(Request $request)
    {
        $stationId = $request->query('station_id');
        
        if (!$stationId) {
            return response()->json([], 200);
        }

        // Only return active (unarchived) scans to the PWA scanner feed
        return ScanLog::where('station_id', $stationId)
            ->where('is_archived', false)
            ->orderByDesc('id')
            ->take(500)
            ->get()
            ->map(fn($log) => [
                'id'           => $log->id,
                'client_id'    => 'sys-' . $log->tracking_number,
                'code'         => $log->tracking_number,
                'scanned_at'   => $log->scanned_at ? \Carbon\Carbon::parse($log->scanned_at)->format('Y-m-d H:i:s') : now(),
                'method'       => $log->method,
                'match_status' => $log->match_status,
                'note'         => $log->note,
            ]);
    }

    public function export(Request $request)
    {
        $stationId = $request->query('station_id', 1);
        
        return response()->streamDownload(function () use ($stationId) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tracking Number', 'Timestamp', 'Method', 'Match Status', 'Notes']);
            
            ScanLog::where('station_id', $stationId)
                ->where('is_archived', false)
                ->orderByDesc('id')
                ->cursor()
                ->each(fn ($s) => fputcsv($out, [
                    $s->tracking_number, 
                    $s->scanned_at?->format('Y-m-d H:i:s') ?? $s->created_at, 
                    $s->method, 
                    $s->match_status, 
                    $s->note,
                ]));
                
            fclose($out);
        }, "station-{$stationId}-scans.csv", ['Content-Type' => 'text/csv']);
    }
}