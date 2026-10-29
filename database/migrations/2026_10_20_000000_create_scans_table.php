<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $t) {
            $t->id();
            $t->string('client_id')->unique();          // uuid made on the phone -> safe re-sync
            $t->string('code')->index();
            $t->string('scanned_at');                   // kept as the phone's local time string
            $t->enum('method', ['scanned', 'typed'])->default('typed');
            $t->string('note')->default('');
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('scans'); }
};
