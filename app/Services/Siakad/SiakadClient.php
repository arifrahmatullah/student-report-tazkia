<?php

namespace App\Services\Siakad;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Klien baca-saja untuk API integrasi SIAKAD (dokumen 15).
 * Semua endpoint GET dengan header X-Api-Key. Field kosong dihilangkan oleh API,
 * jadi pemanggil harus memakai null-safe / data_get.
 */
class SiakadClient
{
    private const PREFIX = '/api/integrasi';

    /** @param array<string,mixed> $query */
    public function get(string $path, array $query = [], int $ttl = 0): mixed
    {
        $query = array_filter($query, fn ($v) => $v !== null && $v !== '');

        if ($ttl <= 0) {
            return $this->request($path, $query);
        }

        $key = 'siakad:'.md5($path.'?'.http_build_query($query));

        return Cache::remember($key, $ttl, fn () => $this->request($path, $query));
    }

    // ---- Referensi ------------------------------------------------------

    public function prodi(): array
    {
        return $this->get('/prodi', [], config('siakad.ttl.referensi'));
    }

    public function tahunAkademik(): array
    {
        return $this->get('/tahun-akademik', [], config('siakad.ttl.referensi'));
    }

    public function tahunAkademikAktif(): ?array
    {
        try {
            return $this->get('/tahun-akademik/aktif', [], config('siakad.ttl.referensi')) ?: null;
        } catch (SiakadException $e) {
            if ($e->isNotFound()) {
                return null;
            }
            throw $e;
        }
    }

    // ---- Mahasiswa ------------------------------------------------------

    public function mahasiswa(string $nim): array
    {
        return $this->get("/mahasiswa/{$nim}", [], config('siakad.ttl.mahasiswa'));
    }

    public function biodata(string $nim): array
    {
        return $this->get("/mahasiswa/{$nim}/biodata", [], config('siakad.ttl.mahasiswa'));
    }

    public function orangTua(string $nim): array
    {
        return $this->get("/mahasiswa/{$nim}/orang-tua", [], config('siakad.ttl.orang_tua'));
    }

    /**
     * Verifikasi klaim. Tidak pernah di-cache dan tanggal lahir tidak boleh masuk log.
     * "NIM tidak ada" dan "tanggal salah" sama-sama menghasilkan cocok=false.
     */
    public function verifikasi(string $nim, string $tanggalLahir): array
    {
        try {
            return $this->request("/mahasiswa/{$nim}/verifikasi", ['tanggalLahir' => $tanggalLahir]);
        } catch (SiakadException $e) {
            if ($e->isNotFound()) {
                return ['cocok' => false];
            }
            throw $e;
        }
    }

    public function krs(string $nim, ?string $tahunAkademikId = null): array
    {
        return $this->get("/mahasiswa/{$nim}/krs", ['tahunAkademikId' => $tahunAkademikId], config('siakad.ttl.mahasiswa'));
    }

    public function transkrip(string $nim, ?string $sampaiTahunAkademikId = null): array
    {
        return $this->get("/mahasiswa/{$nim}/transkrip", ['sampaiTahunAkademikId' => $sampaiTahunAkademikId], config('siakad.ttl.mahasiswa'));
    }

    /**
     * Semua mahasiswa yang cocok dengan filter (status, angkatan, prodiId), menelusuri seluruh halaman.
     * Dibatasi $maks agar permintaan yang terlalu luas tidak menahan halaman terlalu lama.
     *
     * @return list<array<string,mixed>>
     */
    public function mahasiswaList(array $filter, int $maks = 5000): array
    {
        $hasil = [];
        $halaman = 0;

        do {
            $res = $this->get('/mahasiswa', $filter + ['size' => 200, 'page' => $halaman]);
            foreach ($res['content'] ?? [] as $m) {
                $hasil[] = $m;
            }
            $halaman++;
            $totalHalaman = (int) ($res['totalPages'] ?? 1);
        } while ($halaman < $totalHalaman && count($hasil) < $maks);

        return array_slice($hasil, 0, $maks);
    }

    public function riwayatIpk(string $nim): array
    {
        return $this->get("/mahasiswa/{$nim}/riwayat-ipk", [], config('siakad.ttl.mahasiswa'));
    }

    public function presensi(string $nim, ?string $tahunAkademikId = null): array
    {
        return $this->get("/mahasiswa/{$nim}/presensi", ['tahunAkademikId' => $tahunAkademikId], config('siakad.ttl.mahasiswa'));
    }

    public function tagihan(string $nim, ?string $tahunAkademikId = null): array
    {
        return $this->get("/mahasiswa/{$nim}/tagihan", ['tahunAkademikId' => $tahunAkademikId], config('siakad.ttl.mahasiswa'));
    }

    public function ringkasanTagihan(string $nim, ?string $tahunAkademikId = null): array
    {
        return $this->get("/mahasiswa/{$nim}/tagihan/ringkasan", ['tahunAkademikId' => $tahunAkademikId], config('siakad.ttl.mahasiswa'));
    }

    // ---- Beasiswa -------------------------------------------------------

    public function beasiswa(): array
    {
        return $this->get('/beasiswa', [], config('siakad.ttl.beasiswa'));
    }

    /** Tanpa filter status hanya penerima yang masih berlaku. */
    public function penerima(string $beasiswaId, array $filter = []): array
    {
        return $this->get("/beasiswa/{$beasiswaId}/penerima", $filter, config('siakad.ttl.beasiswa'));
    }

    public function laporanAkademik(string $beasiswaId, string $sampaiTahunAkademikId, array $filter = []): array
    {
        return $this->get(
            "/beasiswa/{$beasiswaId}/laporan-akademik",
            ['sampaiTahunAkademikId' => $sampaiTahunAkademikId] + $filter,
            config('siakad.ttl.laporan'),
        );
    }

    // ---- Reminder (daftar utuh, tanpa paginasi) --------------------------

    public function belumIsiKrs(array $filter = [], int $ttl = 0): array
    {
        return $this->get('/krs/belum-isi', $filter, $ttl);
    }

    public function sksNol(array $filter = []): array
    {
        return $this->get('/krs/sks-nol', $filter);
    }

    // ---- Transport ------------------------------------------------------

    private function http(): PendingRequest
    {
        $key = config('siakad.api_key');
        if (! $key) {
            throw new SiakadException('SIAKAD_API_KEY belum diatur di .env');
        }

        return Http::baseUrl(rtrim(config('siakad.base_url'), '/').self::PREFIX)
            ->withHeaders(['X-Api-Key' => $key])
            ->acceptJson()
            ->timeout(config('siakad.timeout'))
            ->retry(2, 300, fn ($e) => $e instanceof ConnectionException, throw: false);
    }

    private function request(string $path, array $query): mixed
    {
        try {
            $response = $this->http()->get($path, $query);
        } catch (ConnectionException $e) {
            throw new SiakadException('SIAKAD tidak dapat dihubungi.', 0, 'KONEKSI');
        }

        if ($response->failed()) {
            throw new SiakadException(
                $response->json('message') ?? 'Permintaan ke SIAKAD gagal.',
                $response->status(),
                $response->json('code'),
            );
        }

        return $response->json() ?? [];
    }
}
