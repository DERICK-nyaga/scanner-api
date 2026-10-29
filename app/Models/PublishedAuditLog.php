<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublishedAuditLog extends Model {
    use HasFactory;

    protected $table = 'published_audit_logs';

    protected $fillable = [
        'station_id',
        'audit_date',
        'snapshot_data',
        'published_by',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'snapshot_data' => 'array', // Automatically decodes JSON snapshot data to an array/object
    ];

    public function station() {
        return $this->belongsTo(Station::class);
    }

    public function publisher() {
        return $this->belongsTo(Station::class, 'published_by');
    }
}