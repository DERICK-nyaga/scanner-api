<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Station extends Authenticatable {
    use Notifiable;

    protected $table = 'stations';

    protected $fillable = [
        'unique_identifier', 
        'name', 
        'email', 
        'password', 
        'role', 
        'permissions', 
        'disable_manual_typing'
    ];

    protected $casts = [
        'permissions' => 'array',
        'disable_manual_typing' => 'boolean',
    ];

    public function masterLists() {
        return $this->hasMany(MasterList::class);
    }

    public function scanLogs() {
        return $this->hasMany(ScanLog::class);
    }

    public function returnedPackages() {
        return $this->hasMany(ReturnedPackage::class);
    }

    public function deliveryNotes() {
        return $this->hasMany(DeliveryNote::class);
    }

    public function smsNotifications() {
        return $this->hasMany(SmsNotification::class);
    }
}