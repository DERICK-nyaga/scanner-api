<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('return_pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('returned_package_id')->constrained('returned_packages')->onDelete('cascade');
            $table->string('agent_name');
            $table->string('agent_phone');
            $table->string('agent_id_details');
            $table->string('otp_code');
            $table->boolean('otp_verified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('return_pickups');
    }
};