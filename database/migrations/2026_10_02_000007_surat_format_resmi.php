<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_laporan', function (Blueprint $table) {
            $table->string('lampiran')->default('Satu Berkas')->after('nomor_surat');
            $table->string('nama_beasiswa_surat')->nullable()->after('beasiswa_nama'); // "Beasiswa Pemerintah Kabupaten Balangan"
            $table->string('instansi_tujuan')->nullable()->after('perihal');            // "Pemerintah Kabupaten Balangan"
            $table->string('penerima_nama')->nullable()->after('instansi_tujuan');
            $table->string('penerima_jabatan')->nullable()->after('penerima_nama');
            $table->text('penerima_alamat')->nullable()->after('penerima_jabatan');
            $table->string('teks_angkatan')->nullable()->after('penerima_alamat');       // "angkatan 2025", opsional
        });

        // Data lama (satu kolom 'tujuan') dipindahkan ke alamat penerima agar tidak hilang.
        DB::table('surat_laporan')->update(['penerima_alamat' => DB::raw('tujuan')]);

        Schema::table('surat_laporan', function (Blueprint $table) {
            $table->dropColumn('tujuan');
        });

        Schema::table('penandatangan', function (Blueprint $table) {
            // Terisi = koordinator program studi tersebut; tanda tangannya dipakai di transkrip prodi itu.
            $table->string('prodi_id')->nullable()->after('nik');
            $table->boolean('pakai_cap')->default(true)->after('ttd_path');
        });
    }

    public function down(): void
    {
        Schema::table('penandatangan', function (Blueprint $table) {
            $table->dropColumn(['prodi_id', 'pakai_cap']);
        });

        Schema::table('surat_laporan', function (Blueprint $table) {
            $table->text('tujuan')->nullable();
        });
        DB::table('surat_laporan')->update(['tujuan' => DB::raw('penerima_alamat')]);

        Schema::table('surat_laporan', function (Blueprint $table) {
            $table->dropColumn(['lampiran', 'nama_beasiswa_surat', 'instansi_tujuan', 'penerima_nama', 'penerima_jabatan', 'penerima_alamat', 'teks_angkatan']);
        });
    }
};
