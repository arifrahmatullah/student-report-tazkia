<?php

namespace Tests\Unit;

use App\Support\Hijri;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class HijriTest extends TestCase
{
    public function test_konversi_titik_acuan(): void
    {
        // 1 Januari 2000 = 24 Ramadhan 1420 H (hitungan sipil/tabular).
        $this->assertSame('24 Ramadhan 1420 H', Hijri::format(Carbon::create(2000, 1, 1)));
        // Tanggal pertama kalender sipil: 1 Muharram 1 H = 16 Juli 622 (Julian) = 19 Juli 622 (Gregorian proleptik).
        $this->assertSame('1 Muharram 1 H', Hijri::format(Carbon::create(622, 7, 19)));
    }

    public function test_hari_berurutan_tidak_melompat(): void
    {
        $tgl = Carbon::create(2026, 10, 2);
        $sebelumnya = Hijri::dari($tgl->copy()->subDay());
        $sekarang = Hijri::dari($tgl);

        $this->assertTrue(
            $sekarang['hari'] === $sebelumnya['hari'] + 1
            || ($sekarang['hari'] === 1 && in_array($sebelumnya['hari'], [29, 30], true))
        );
        $this->assertContains($sekarang['bulan'], range(1, 12));
    }
}
