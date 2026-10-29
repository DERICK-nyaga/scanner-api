<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('master_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('stations')->onDelete('cascade');
            $table->string('tracking_number');
            $table->string('hub')->nullable(); // Captures the Hub column (e.g., KE-3PL-Flex...)
            $table->integer('aging')->default(0); // Captures the Aging days column from your CSV
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->date('arrival_date'); // Calculated Day 0 based on aging
            $table->date('expiration_date'); // Day 9/10 Return threshold
            $table->string('status')->default('pending'); // pending, scanned, returned, expired
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('master_lists');
    }
};