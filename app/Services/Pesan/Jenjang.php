<?php

namespace App\Services\Pesan;

use App\Services\Siakad\SiakadClient;

/**
 * Jenjang dan penulisan angkatan di SIAKAD: S1/D3/S3 memakai tahun 4 digit (2025), sedangkan S2 memakai
 * 2 digit (25). Kedua penulisan itu angkatan yang sama (tahun masuk), jadi memilih jenjang membuat isian
 * angkatan disesuaikan otomatis.
 */
class Jenjang
{
    public const DAFTAR = ['D3', 'S1', 'S2', 'S3'];

    /** Angkatan sebagaimana tertulis di SIAKAD untuk jenjang itu (2025 -> 25 untuk S2, 25 -> 2025 untuk lainnya). */
    public static function normalisasiAngkatan(?int $angkatan, ?string $jenjang): ?int
    {
        if ($angkatan === null) {
            return null;
        }
        if ($jenjang === 'S2' && $angkatan >= 2000) {
            return $angkatan - 2000;
        }
        if (in_array($jenjang, ['D3', 'S1', 'S3'], true) && $angkatan < 100) {
            return 2000 + $angkatan;
        }

        return $angkatan;
    }

    /** Tahun masuk lengkap: 25 -> 2025, 2025 -> 2025 (S2 menulis 2 digit di SIAKAD). */
    public static function tahunPenuh(int $angkatan): int
    {
        return $angkatan < 100 ? 2000 + $angkatan : $angkatan;
    }

    /**
     * Angkatan paling lama yang ditampilkan secara bawaan: tahun mulai tahun akademik aktif dikurangi
     * config('pesan.tahun_ke_belakang'). Tanpa tahun akademik dipakai tahun berjalan.
     *
     * @param  array<string,mixed>|null  $tahunAkademik
     */
    public static function tahunMinimal(?array $tahunAkademik, ?int $keBelakang = null): int
    {
        $mulai = isset($tahunAkademik['tahun']) && preg_match('/^(\d{4})/', (string) $tahunAkademik['tahun'], $m)
            ? (int) $m[1]
            : (int) now()->year;

        return $mulai - ($keBelakang ?? (int) config('pesan.tahun_ke_belakang'));
    }

    public static function dalamRentang(int|string $angkatan, int $tahunMinimal): bool
    {
        return self::tahunPenuh((int) $angkatan) >= $tahunMinimal;
    }

    /** "S2 · Angkatan 25", "Angkatan 2025", atau "S1". */
    public static function label(?string $jenjang, ?int $angkatan): string
    {
        return collect([$jenjang, $angkatan !== null ? 'Angkatan '.$angkatan : null])->filter()->implode(' · ');
    }

    /**
     * Peta id prodi -> jenjang (dari referensi SIAKAD yang di-cache).
     *
     * @return array<string,string>
     */
    public static function peta(SiakadClient $siakad): array
    {
        return collect($siakad->prodi())->filter(fn ($p) => ! empty($p['jenjang']))->pluck('jenjang', 'id')->all();
    }
}
