<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const TABEL = ['surat_laporan', 'penandatangan', 'pesan'];

    public function up(): void
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, function (Blueprint $t) {
                $t->uuid('uuid')->nullable()->unique();
            });

            DB::table($tabel)->whereNull('uuid')->orderBy('id')->each(
                fn ($baris) => DB::table($tabel)->where('id', $baris->id)->update(['uuid' => (string) Str::uuid()])
            );
        }
    }

    public function down(): void
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, function (Blueprint $t) use ($tabel) {
                $t->dropUnique($tabel.'_uuid_unique');
                $t->dropColumn('uuid');
            });
        }
    }
};
