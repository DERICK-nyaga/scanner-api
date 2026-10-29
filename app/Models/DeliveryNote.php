<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model {
    use HasFactory;

    protected $table = 'delivery_notes';

    protected $fillable = [
        'station_id',
        'file_path',
        'original_filename',
    ];

    public function station() {
        return $this->belongsTo(Station::class);
    }
}