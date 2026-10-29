<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsNotification extends Model {
    use HasFactory;

    protected $table = 'sms_notifications';

    protected $fillable = [
        'station_id',
        'recipient_phone',
        'message_content',
        'status',
    ];

    public function station() {
        return $this->belongsTo(Station::class);
    }
}