<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('unique_identifier')->unique(); // e.g. KE-3PL-Flexcom Cheptiret-Station
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user'); // superadmin, admin, user
            $table->json('permissions')->nullable();
            $table->boolean('disable_manual_typing')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('stations');
    }
};