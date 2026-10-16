<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesan', function (Blueprint $table) {
            // Mahasiswa sasaran saat pesan dibuat ([{nim, nama}]); dipakai pengirim WhatsApp dan laporan.
            $table->json('daftar_nim')->nullable()->after('target');
            $table->unsignedInteger('jumlah_mahasiswa')->default(0)->after('daftar_nim');
        });
    }

    public function down(): void
    {
        Schema::table('pesan', function (Blueprint $table) {
            $table->dropColumn(['daftar_nim', 'jumlah_mahasiswa']);
        });
    }
};
