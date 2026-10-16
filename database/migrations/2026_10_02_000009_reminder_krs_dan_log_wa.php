<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesan', function (Blueprint $table) {
            // umum = pengumuman biasa; reminder_krs = pengingat KRS (template WhatsApp berparameter bernama)
            $table->string('jenis', 30)->default('umum')->after('pengirim_id');
            $table->index('jenis');
        });

        // Log WhatsApp menjadi satu-satunya tempat melihat "berhasil terkirim atau belum", termasuk yang dilewati
        // (tanpa nomor, nomor tidak valid) sehingga no_hp boleh kosong.
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->string('no_hp', 20)->nullable()->change();
            $table->string('template')->nullable()->change();
            $table->foreignId('pesan_id')->nullable()->after('pesan_penerima_id')->constrained('pesan')->nullOnDelete();
            $table->string('nama_mahasiswa')->nullable()->after('nim');
            $table->string('tipe', 12)->nullable()->after('nama_mahasiswa');       // ayah | ibu | wali | mahasiswa | tes
            $table->string('nama_penerima')->nullable()->after('tipe');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('pesan_id');
            $table->dropColumn(['nama_mahasiswa', 'tipe', 'nama_penerima']);
        });
        Schema::table('pesan', function (Blueprint $table) {
            $table->dropIndex(['jenis']);
            $table->dropColumn('jenis');
        });
    }
};
