<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('returned_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('stations')->onDelete('cascade');
            $table->string('tracking_number');
            $table->date('return_date');
            $table->text('reason');
            $table->string('status')->default('processing'); // processing, handed_over
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('returned_packages');
    }
};