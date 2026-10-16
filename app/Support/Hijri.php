<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Konversi Masehi -> Hijriah memakai kalender sipil (tabular).
 *
 * Hasilnya bisa berbeda satu hari dari penetapan resmi Kemenag / Umm al-Qura
 * (yang bergantung pada rukyat/hisab). Untuk dokumen resmi, tanggal Hijriah
 * boleh diisi manual di formulir surat; fungsi ini hanya nilai bawaan.
 */
class Hijri
{
    public const BULAN = [
        1 => 'Muharram', 2 => 'Safar', 3 => 'Rabi’ul Awal', 4 => 'Rabi’ul Akhir',
        5 => 'Jumadil Awal', 6 => 'Jumadil Akhir', 7 => 'Rajab', 8 => 'Sya’ban',
        9 => 'Ramadhan', 10 => 'Syawal', 11 => 'Dzulqa’dah', 12 => 'Dzulhijjah',
    ];

    /** @return array{tahun:int,bulan:int,hari:int} */
    public static function dari(CarbonInterface $tanggal): array
    {
        $jd = self::julianDay($tanggal->year, $tanggal->month, $tanggal->day);

        $l = $jd - 1948440 + 10632;
        $n = intdiv($l - 1, 10631);
        $l = $l - 10631 * $n + 354;
        $j = intdiv(10985 - $l, 5316) * intdiv(50 * $l, 17719) + intdiv($l, 5670) * intdiv(43 * $l, 15238);
        $l = $l - intdiv(30 - $j, 15) * intdiv(17719 * $j, 50) - intdiv($j, 16) * intdiv(15238 * $j, 43) + 29;
        $bulan = intdiv(24 * $l, 709);
        $hari = $l - intdiv(709 * $bulan, 24);
        $tahun = 30 * $n + $j - 30;

        return ['tahun' => $tahun, 'bulan' => $bulan, 'hari' => $hari];
    }

    /** Contoh: "24 Ramadhan 1420 H". */
    public static function format(CarbonInterface $tanggal): string
    {
        $h = self::dari($tanggal);

        return $h['hari'].' '.self::BULAN[$h['bulan']].' '.$h['tahun'].' H';
    }

    private static function julianDay(int $y, int $m, int $d): int
    {
        $a = intdiv(14 - $m, 12);
        $y2 = $y + 4800 - $a;
        $m2 = $m + 12 * $a - 3;

        return $d + intdiv(153 * $m2 + 2, 5) + 365 * $y2 + intdiv($y2, 4) - intdiv($y2, 100) + intdiv($y2, 400) - 32045;
    }
}
