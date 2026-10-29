<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sms_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('stations')->onDelete('cascade');
            $table->string('recipient_phone');
            $table->text('message_content');
            $table->string('status')->default('sent'); // sent, failed
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('sms_notifications');
    }
};