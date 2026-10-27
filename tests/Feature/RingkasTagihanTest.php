<?php

namespace Tests\Feature;

use App\Services\Siakad\ProfilMahasiswa;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RingkasTagihanTest extends TestCase
{
    private function tagihan(string $ta, string $status, int $tagih, int $bayar, string $jt, bool $lewat = false): array
    {
        return [
            'tahunAkademikId' => $ta, 'tahunAkademik' => 'TA '.$ta, 'status' => $status,
            'nominalTagih' => $tagih, 'akumulasiPembayaran' => $bayar, 'sisa' => $tagih - $bayar,
            'lunas' => $tagih === $bayar, 'jatuhTempo' => $jt, 'lewatJatuhTempo' => $lewat,
        ];
    }

    public function test_tagihan_digabung_tidak_dihitung_dan_semester_berjalan_dipisah(): void
    {
        Cache::flush();
        config(['siakad.api_key' => 'kunci-uji', 'siakad.base_url' => 'https://siakad.test']);
        Http::fake(['*/mahasiswa/100001/tagihan*' => Http::response([
            $this->tagihan('now', 'AKTIF', 4000000, 666666, '2027-04-18'),
            $this->tagihan('lama', 'AKTIF', 4000000, 4000000, '2026-10-18'),
            $this->tagihan('hilang', 'DIGABUNG', 4000000, 2666664, '2024-05-25', true),
        ])]);

        $r = app(ProfilMahasiswa::class)->ringkasTagihan('100001', 'now');

        $this->assertSame(3333334, $r['totalBelumDibayar']);
        $this->assertSame(0, $r['jumlahLewatJatuhTempo']);
        $this->assertSame('2027-04-18', $r['jatuhTempoTerdekat']);
        $this->assertSame(2, $r['jumlahTagihan']);
        $this->assertSame(3333334, $r['berjalan']['sisa']);
        $this->assertSame(0, $r['tunggakan']);
    }

    public function test_harus_dibayar_sekarang_memakai_termin_yang_sedang_ditagihkan(): void
    {
        Cache::flush();
        config(['siakad.api_key' => 'kunci-uji', 'siakad.base_url' => 'https://siakad.test']);
        $t = $this->tagihan('now', 'AKTIF', 4000000, 666666, '2027-04-18');
        $t['cicilan'] = [
            ['tahap' => 1, 'jatuhTempo' => '2026-09-15', 'nominal' => 666666, 'status' => 'LUNAS'],
            ['tahap' => 2, 'jatuhTempo' => '2026-10-15', 'nominal' => 666666, 'status' => 'LEWAT_JATUH_TEMPO'],
            ['tahap' => 3, 'jatuhTempo' => '2026-11-15', 'nominal' => 1333332, 'status' => 'SEDANG_DITAGIHKAN'],
        ];
        Http::fake(['*/mahasiswa/100003/tagihan*' => Http::response([$t])]);

        $r = app(ProfilMahasiswa::class)->ringkasTagihan('100003', 'now');

        $this->assertSame(3, $r['berjalan']['sekarang']['tahap']);
        $this->assertSame(1333332, $r['berjalan']['sekarang']['nominal']);
        $this->assertCount(3, $r['berjalan']['cicilan']);
    }

    public function test_tanpa_cicilan_atau_sudah_lunas_tidak_ada_angka_sekarang(): void
    {
        Cache::flush();
        config(['siakad.api_key' => 'kunci-uji', 'siakad.base_url' => 'https://siakad.test']);
        $lunas = $this->tagihan('now', 'AKTIF', 4000000, 4000000, '2027-04-18');
        $lunas['cicilan'] = [['tahap' => 1, 'jatuhTempo' => '2026-09-15', 'nominal' => 4000000, 'status' => 'SEDANG_DITAGIHKAN']];
        Http::fake(['*/mahasiswa/100004/tagihan*' => Http::response([$lunas])]);
        $this->assertNull(app(ProfilMahasiswa::class)->ringkasTagihan('100004', 'now')['berjalan']['sekarang']);

        Http::fake(['*/mahasiswa/100005/tagihan*' => Http::response([$this->tagihan('now', 'AKTIF', 4000000, 666666, '2027-04-18')])]);
        $this->assertNull(app(ProfilMahasiswa::class)->ringkasTagihan('100005', 'now')['berjalan']['sekarang']);
    }

    public function test_tunggakan_semester_lama_yang_aktif_dilaporkan_terpisah(): void
    {
        Cache::flush();
        config(['siakad.api_key' => 'kunci-uji', 'siakad.base_url' => 'https://siakad.test']);
        Http::fake(['*/mahasiswa/100002/tagihan*' => Http::response([
            $this->tagihan('now', 'AKTIF', 4000000, 4000000, '2027-04-18'),
            $this->tagihan('lama', 'AKTIF', 4000000, 3000000, '2026-01-01', true),
        ])]);

        $r = app(ProfilMahasiswa::class)->ringkasTagihan('100002', 'now');

        $this->assertSame(1000000, $r['tunggakan']);
        $this->assertSame(1, $r['jumlahLewatJatuhTempo']);
        $this->assertSame(0, $r['berjalan']['sisa']);
    }
}
