<?php

namespace Tests\Unit;

use App\Services\Siakad\ProfilMahasiswa;
use App\Services\Siakad\SiakadClient;
use Tests\TestCase;

class PerhatianTest extends TestCase
{
    private function perhatian(array $data): array
    {
        return (new ProfilMahasiswa(new SiakadClient))->perhatian($data);
    }

    public function test_tidak_ada_peringatan_bila_semua_baik(): void
    {
        $this->assertSame([], $this->perhatian([
            'krs' => ['sudahIsiKrs' => true, 'statusKrs' => 'DISETUJUI'],
            'masaKrs' => true,
            'tagihan' => ['totalBelumDibayar' => 0, 'jumlahLewatJatuhTempo' => 0],
            'presensi' => [['mkNama' => 'A', 'totalPertemuan' => 10, 'persenHadir' => 90]],
        ]));
    }

    public function test_tagihan_lewat_jatuh_tempo_paling_atas(): void
    {
        $hasil = $this->perhatian([
            'krs' => ['sudahIsiKrs' => false, 'statusKrs' => 'DRAFT'],
            'masaKrs' => true,
            'tagihan' => ['jumlahLewatJatuhTempo' => 2, 'totalBelumDibayar' => 5000000],
        ]);

        $this->assertSame('bahaya', $hasil[0]['tingkat']);
        $this->assertSame('KRS belum dikirim', $hasil[1]['judul']);
    }

    public function test_krs_belum_isi_hanya_diingatkan_saat_masa_krs(): void
    {
        $data = ['krs' => ['sudahIsiKrs' => false], 'tagihan' => []];

        $this->assertSame([], $this->perhatian($data + ['masaKrs' => false]));
        $this->assertSame('Belum mengisi KRS', $this->perhatian($data + ['masaKrs' => true])[0]['judul']);
    }

    public function test_kehadiran_rendah_menyebut_matakuliah_dan_mengabaikan_yang_belum_ada_pertemuan(): void
    {
        $hasil = $this->perhatian([
            'presensi' => [
                ['mkNama' => 'Fiqih', 'totalPertemuan' => 8, 'persenHadir' => 62.5],
                ['mkNama' => 'Baru', 'totalPertemuan' => 0, 'persenHadir' => 0],
                ['mkNama' => 'Ushul', 'totalPertemuan' => 8, 'persenHadir' => 74],
                ['mkNama' => 'Bagus', 'totalPertemuan' => 8, 'persenHadir' => 75],
            ],
        ]);

        $this->assertCount(1, $hasil);
        $this->assertSame('Fiqih dan Ushul.', $hasil[0]['isi']);
    }

    public function test_bagian_yang_gagal_dimuat_tidak_memicu_peringatan(): void
    {
        $this->assertSame([], $this->perhatian(['krs' => null, 'tagihan' => null, 'presensi' => null, 'masaKrs' => true]));
    }
}
