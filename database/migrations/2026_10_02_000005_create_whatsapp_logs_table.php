<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 30);             // broadcast | reminder_krs | reminder_cuti
            $table->string('nim', 20)->nullable();
            $table->string('no_hp', 20);
            $table->string('template');
            $table->json('parameter')->nullable();
            $table->string('status', 20);            // terkirim | gagal
            $table->text('respons')->nullable();
            $table->foreignId('pesan_penerima_id')->nullable()->constrained('pesan_penerima')->nullOnDelete();
            $table->timestamps();

            $table->index(['jenis', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
