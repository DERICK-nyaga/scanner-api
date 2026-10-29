<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('stations')->onDelete('cascade');
            $table->string('tracking_number');
            $table->timestamp('scanned_at');
            $table->string('method'); // scanned, typed
            $table->text('note')->nullable();
            $table->string('match_status'); // matched, missing, unexpected
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('scan_logs');
    }
};