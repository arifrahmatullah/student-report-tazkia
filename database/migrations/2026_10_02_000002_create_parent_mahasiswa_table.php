<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hasil klaim: akun orang tua ini memantau NIM ini. Data akademiknya tetap di SIAKAD.
        Schema::create('parent_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nim', 20);
            $table->string('nama_mahasiswa')->nullable(); // salinan untuk tampilan cepat, bukan sumber kebenaran
            $table->string('siakad_mahasiswa_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'nim']);
            $table->index('nim');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_mahasiswa');
    }
};
