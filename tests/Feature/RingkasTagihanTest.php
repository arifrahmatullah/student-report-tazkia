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
