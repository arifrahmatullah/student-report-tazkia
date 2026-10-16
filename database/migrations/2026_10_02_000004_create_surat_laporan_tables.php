<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penandatangan surat; gambar tanda tangan dikelola lokal (API hanya memberi nama koordinator).
        Schema::create('penandatangan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('jabatan');
            $table->string('nidn')->nullable();
            $table->string('nik')->nullable();
            $table->string('ttd_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('surat_laporan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->foreignId('penandatangan_id')->nullable()->constrained('penandatangan')->nullOnDelete();
            $table->string('beasiswa_id');
            $table->string('beasiswa_nama');
            $table->string('sampai_tahun_akademik_id');
            $table->string('sampai_tahun_akademik_nama');
            $table->string('filter_prodi_id')->nullable();
            $table->unsignedSmallInteger('filter_angkatan')->nullable();
            $table->string('nomor_surat');
            $table->date('tanggal_surat');
            $table->string('tempat_surat')->default('Bogor');
            $table->string('perihal');
            $table->text('tujuan');                  // alamat tujuan, boleh multi baris
            $table->text('catatan')->nullable();
            // Salinan angka saat dicetak: dokumen resmi tidak boleh berubah diam-diam bila SIAKAD berubah.
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->index('beasiswa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_laporan');
        Schema::dropIfExists('penandatangan');
    }
};
