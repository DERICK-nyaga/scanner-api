<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('master_lists', function (Blueprint $table) {
            if (!Schema::hasColumn('master_lists', 'hub')) {
                $table->string('hub')->nullable()->after('tracking_number');
            }
            if (!Schema::hasColumn('master_lists', 'aging')) {
                $table->integer('aging')->default(0)->after('hub');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_lists', function (Blueprint $table) {
            $table->dropColumn(['hub', 'aging']);
        });
    }
};
