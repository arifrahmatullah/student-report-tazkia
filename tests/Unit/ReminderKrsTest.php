<?php

namespace Tests\Unit;

use App\Services\Pesan\ReminderKrs;
use Tests\TestCase;

class ReminderKrsTest extends TestCase
{
    public function test_parameter_dari_tahun_akademik_aktif(): void
    {
        $p = ReminderKrs::parameter(['semester' => 'GASAL', 'tahun' => '2026/2027', 'tanggalSelesaiKrs' => '2026-10-03']);

        $this->assertSame(['semester' => 'Ganjil', 'academic_year' => '2026/2027', 'date' => '03 Oktober 2026'], $p);
        $this->assertSame('Genap', ReminderKrs::parameter(['semester' => 'GENAP', 'tahun' => 'x'])['semester']);
        $this->assertSame('segera', ReminderKrs::parameter(['semester' => 'GENAP', 'tahun' => 'x'])['date'], 'tanpa batas KRS dipakai kata "segera" seperti aplikasi lama');
    }

    public function test_kontak_pertama_yang_bernomor_valid_dipilih_ayah_dulu(): void
    {
        $semua = ['ayah' => ['nama' => 'Budi', 'noHp' => '0812-3456-7890'], 'ibu' => ['nama' => 'Siti', 'noHp' => '085711112222']];
        $this->assertSame(['peran' => 'ayah', 'nama' => 'Budi', 'nomor' => '6281234567890'], ReminderKrs::pilihKontak($semua));

        $ayahKosong = ['ayah' => ['nama' => 'Budi'], 'ibu' => ['nama' => 'Siti', 'noHp' => '085711112222']];
        $this->assertSame('ibu', ReminderKrs::pilihKontak($ayahKosong)['peran'], 'ayah tanpa nomor -> pakai ibu');

        $ayahRusak = ['ayah' => ['noHp' => 'abc'], 'wali' => ['nama' => 'Paman', 'noHp' => '+62 812 0000 1111']];
        $this->assertSame('wali', ReminderKrs::pilihKontak($ayahRusak)['peran']);

        $this->assertNull(ReminderKrs::pilihKontak(['ayah' => ['nama' => 'Budi'], 'ibu' => ['nama' => 'Siti']]));
        $this->assertNull(ReminderKrs::pilihKontak([]));
    }

    public function test_nomor_disamarkan_tetapi_masih_dapat_dikenali(): void
    {
        $this->assertSame('0812-••••-890', ReminderKrs::samarkan('6281234567890'));
    }
}
