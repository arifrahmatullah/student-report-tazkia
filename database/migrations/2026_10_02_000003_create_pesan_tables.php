<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengirim_id')->constrained('users');
            $table->string('judul');
            $table->text('isi');
            $table->json('target');                 // filter: prodi, angkatan, beasiswa, daftar nim
            $table->boolean('ke_mahasiswa')->default(false);
            $table->boolean('ke_orangtua')->default(true);
            $table->boolean('kirim_wa')->default(false);
            $table->string('status', 20)->default('antre'); // antre | diproses | selesai | gagal
            $table->timestamp('dijadwalkan_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('pesan_penerima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesan_id')->constrained('pesan')->cascadeOnDelete();
            $table->string('nim', 20);
            $table->string('nama_mahasiswa')->nullable();
            $table->string('tipe', 12);              // mahasiswa | ayah | ibu | wali
            $table->string('nama_penerima')->nullable();
            $table->string('no_hp', 20)->nullable(); // sudah dinormalkan 628xxx
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // akun orang tua bila sudah klaim
            $table->timestamp('dibaca_pada')->nullable();
            $table->string('wa_status', 20)->nullable(); // null = tidak dikirim | antre | terkirim | gagal | tanpa_nomor
            $table->text('wa_error')->nullable();
            $table->timestamp('wa_terkirim_pada')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'dibaca_pada']);
            $table->index(['pesan_id', 'wa_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesan_penerima');
        Schema::dropIfExists('pesan');
    }
};
