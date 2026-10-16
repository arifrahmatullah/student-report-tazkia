<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_laporan', function (Blueprint $table) {
            // Kosong = dihitung otomatis saat dicetak.
            $table->string('tanggal_hijriah')->nullable()->after('tanggal_surat');
        });
    }

    public function down(): void
    {
        Schema::table('surat_laporan', function (Blueprint $table) {
            $table->dropColumn('tanggal_hijriah');
        });
    }
};
