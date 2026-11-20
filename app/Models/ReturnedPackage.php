<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnedPackage extends Model {
    use HasFactory;

    protected $table = 'returned_packages';

    protected $fillable = [
        'station_id',
        'tracking_number',
        'linehaul_id',
        'courier_phone',
        'reason',
        'note',
        'status',
        'return_date',
    ];

    protected $casts = [
        'return_date' => 'datetime',
    ];

    public function station() {
        return $this->belongsTo(Station::class);
    }

    public function pickup() {
        return $this->hasOne(ReturnPickup::class);
    }
}