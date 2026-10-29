<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnPickup extends Model {
    use HasFactory;

    protected $table = 'return_pickups';

    protected $fillable = [
        'returned_package_id',
        'agent_name',
        'agent_phone',
        'agent_id_details',
        'otp_code',
        'otp_verified',
    ];

    protected $casts = [
        'otp_verified' => 'boolean',
    ];

    public function returnedPackage() {
        return $this->belongsTo(ReturnedPackage::class);
    }
}