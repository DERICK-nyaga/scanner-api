<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanLog extends Model {
    use HasFactory;

    protected $table = 'scan_logs';

    protected $fillable = [
        'station_id',
        'tracking_number',
        'scanned_at',
        'method',
        'note',
        'match_status',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function station() {
        return $this->belongsTo(Station::class);
    }
}