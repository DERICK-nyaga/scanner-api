<?php
namespace App\Http\Controllers;

use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScanController extends Controller
{
    public function index()
    {
        return Scan::orderByDesc('id')->paginate(500);
    }

    // Upsert everything from the phone; re-syncing never creates copies.
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'scans'              => 'required|array|max:5000',
            'scans.*.client_id'  => 'required|string|max:64',
            'scans.*.code'       => 'required|string|max:255',
            'scans.*.scanned_at' => 'required|string|max:32',
            'scans.*.method'     => 'required|in:scanned,typed',
            'scans.*.note'       => 'nullable|string|max:255',
        ]);
        $now = now();
        $rows = array_map(fn ($s) => $s + ['note' => $s['note'] ?? '', 'created_at' => $now, 'updated_at' => $now], $data['scans']);
        DB::transaction(fn () => Scan::upsert($rows, ['client_id'], ['code', 'scanned_at', 'method', 'note', 'updated_at']));
        return ['synced' => count($rows)];
    }

    public function destroy(string $clientId)
    {
        Scan::where('client_id', $clientId)->delete();
        return response()->noContent();
    }

    // GET /api/scans/export  -> CSV with a Duplicate column, opens in Excel/WPS
    public function export()
    {
        $counts = Scan::select('code', DB::raw('count(*) c'))->groupBy('code')->pluck('c', 'code');
        return response()->streamDownload(function () use ($counts) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tracking Number', 'Timestamp', 'Method', 'Duplicate', 'Notes']);
            Scan::orderByDesc('id')->cursor()->each(fn ($s) => fputcsv($out, [
                $s->code, $s->scanned_at, $s->method,
                ($counts[$s->code] ?? 1) > 1 ? 'DUPLICATE x' . $counts[$s->code] : 'Unique', $s->note,
            ]));
            fclose($out);
        }, 'scans.csv', ['Content-Type' => 'text/csv']);
    }
}
