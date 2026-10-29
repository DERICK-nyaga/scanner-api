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
        'return_date',
        'reason',
        'status',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function station() {
        return $this->belongsTo(Station::class);
    }

    public function pickup() {
        return $this->hasOne(ReturnPickup::class);
    }
}