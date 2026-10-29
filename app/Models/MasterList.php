<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterList extends Model {
    use HasFactory;

    protected $table = 'master_lists';

    protected $fillable = [
        'station_id',
        'tracking_number',
        'customer_name',
        'customer_phone',
        'arrival_date',
        'expiration_date',
        'status',
    ];

    protected $casts = [
        'arrival_date' => 'date',
        'expiration_date' => 'date',
    ];

    public function station() {
        return $this->belongsTo(Station::class);
    }
}