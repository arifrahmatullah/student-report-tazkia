<?php

namespace Tests\Unit;

use App\Services\Pesan\Jenjang;
use PHPUnit\Framework\TestCase;

class JenjangTest extends TestCase
{
    public function test_s2_memakai_dua_digit_dan_jenjang_lain_empat_digit(): void
    {
        $this->assertSame(25, Jenjang::normalisasiAngkatan(2025, 'S2'));
        $this->assertSame(25, Jenjang::normalisasiAngkatan(25, 'S2'), 'sudah 2 digit: tidak diubah');
        $this->assertSame(7, Jenjang::normalisasiAngkatan(2007, 'S2'));

        foreach (['S1', 'D3', 'S3'] as $j) {
            $this->assertSame(2025, Jenjang::normalisasiAngkatan(25, $j), $j);
            $this->assertSame(2025, Jenjang::normalisasiAngkatan(2025, $j), $j);
        }
    }

    public function test_tanpa_jenjang_angkatan_dipakai_apa_adanya(): void
    {
        $this->assertSame(25, Jenjang::normalisasiAngkatan(25, null));
        $this->assertSame(2025, Jenjang::normalisasiAngkatan(2025, null));
        $this->assertNull(Jenjang::normalisasiAngkatan(null, 'S2'));
    }

    public function test_label(): void
    {
        $this->assertSame('S2 · Angkatan 25', Jenjang::label('S2', 25));
        $this->assertSame('Angkatan 2025', Jenjang::label(null, 2025));
        $this->assertSame('S1', Jenjang::label('S1', null));
        $this->assertSame('', Jenjang::label(null, null));
    }

    public function test_tahun_penuh_dan_rentang_empat_tahun_ke_belakang(): void
    {
        $this->assertSame(2025, Jenjang::tahunPenuh(25));
        $this->assertSame(2025, Jenjang::tahunPenuh(2025));
        $this->assertSame(2007, Jenjang::tahunPenuh(7));

        $min = Jenjang::tahunMinimal(['tahun' => '2026/2027'], 4);
        $this->assertSame(2022, $min);
        $this->assertTrue(Jenjang::dalamRentang(2022, $min));
        $this->assertTrue(Jenjang::dalamRentang(2026, $min));
        $this->assertFalse(Jenjang::dalamRentang(2021, $min));
        $this->assertTrue(Jenjang::dalamRentang(22, $min), 'S2 angkatan 22 = 2022');
        $this->assertFalse(Jenjang::dalamRentang(21, $min), 'S2 angkatan 21 = 2021');
        $this->assertFalse(Jenjang::dalamRentang(7, $min));
    }

    public function test_tahun_minimal_mengikuti_tahun_akademik_dan_bisa_dibatasi_ulang(): void
    {
        $this->assertSame(2021, Jenjang::tahunMinimal(['tahun' => '2025/2026'], 4));
        $this->assertSame(2024, Jenjang::tahunMinimal(['tahun' => '2026/2027'], 2));
        $this->assertSame((int) date('Y') - 4, Jenjang::tahunMinimal(null, 4), 'tanpa tahun akademik dipakai tahun berjalan');
    }
}
