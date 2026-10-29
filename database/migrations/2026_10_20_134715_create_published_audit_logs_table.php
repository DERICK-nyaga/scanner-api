<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('published_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('stations')->onDelete('cascade');
            $table->date('audit_date');
            $table->json('snapshot_data'); // Immutable record of scanned items for the day
            $table->foreignId('published_by')->constrained('stations');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('published_audit_logs');
    }
};