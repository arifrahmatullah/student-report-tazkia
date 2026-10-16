<?php

namespace App\Services\Pesan;

use App\Services\Siakad\SiakadClient;
use App\Services\Siakad\SiakadException;
use Illuminate\Support\Collection;

/**
 * Menentukan mahasiswa sasaran sebuah pesan dari target yang dipilih staf. HANYA mahasiswa berstatus AKTIF
 * yang pernah menjadi sasaran; yang tidak aktif (lulus, cuti, keluar) dilewati dan dilaporkan.
 *
 * Mode:
 *  - beasiswa : penerima suatu program beasiswa (opsional saring jenjang/prodi/angkatan)
 *  - angkatan : mahasiswa AKTIF menurut jenjang, prodi, dan/atau angkatan
 *  - nim      : daftar NIM yang diketik langsung
 */
class PenerimaResolver
{
    public const MODE = ['beasiswa', 'angkatan', 'nim'];

    /** Batas daftar NIM manual; jumlah besar sebaiknya memakai mode lain. */
    public const MAKS_NIM_MANUAL = 200;

    public function __construct(private readonly SiakadClient $siakad) {}

    /**
     * @param  array{mode:string, beasiswa_id?:?string, jenjang?:?string, prodi_id?:?string, angkatan?:int|string|null, nim?:?string}  $target
     * @return array{daftar: Collection<int,array{nim:string,nama:?string}>, tidak_ditemukan: list<string>, tidak_aktif: list<string>, di_luar_rentang: int}
     *
     * @throws SiakadException bila SIAKAD tidak dapat dihubungi
     */
    public function resolve(array $target): array
    {
        $hasil = match ($target['mode'] ?? null) {
            'beasiswa' => $this->dariBeasiswa($target),
            'angkatan' => $this->dariAngkatan($target),
            'nim' => $this->dariDaftarNim((string) ($target['nim'] ?? '')),
            default => ['daftar' => collect(), 'tidak_ditemukan' => [], 'tidak_aktif' => []],
        };

        return $hasil + ['di_luar_rentang' => 0];
    }

    /** Pecah isian bebas (koma, spasi, baris baru) menjadi NIM unik yang hanya berisi angka. */
    public static function parseNim(string $teks): array
    {
        return collect(preg_split('/[\s,;]+/', $teks, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($n) => trim($n))
            ->filter(fn ($n) => preg_match('/^[0-9]{4,20}$/', $n))
            ->unique()->values()->all();
    }

    private function dariBeasiswa(array $t): array
    {
        $jenjang = $t['jenjang'] ?? null;
        $penerima = $this->siakad->penerima((string) $t['beasiswa_id'], [
            'prodiId' => $t['prodi_id'] ?? null,
            'angkatan' => Jenjang::normalisasiAngkatan(isset($t['angkatan']) ? (int) $t['angkatan'] : null, $jenjang),
        ]);

        return $this->pisahkanAktif(collect($penerima), $jenjang, 'statusMahasiswa');
    }

    private function dariAngkatan(array $t): array
    {
        $jenjang = $t['jenjang'] ?? null;
        $mahasiswa = $this->siakad->mahasiswaList([
            'status' => 'AKTIF',
            'prodiId' => $t['prodi_id'] ?? null,
            'angkatan' => Jenjang::normalisasiAngkatan(isset($t['angkatan']) ? (int) $t['angkatan'] : null, $jenjang),
        ]);

        $hasil = $this->pisahkanAktif(collect($mahasiswa), $jenjang, 'status');

        // Tanpa angkatan yang disebut, hanya angkatan 4 tahun terakhir (config pesan.tahun_ke_belakang) yang dituju;
        // mahasiswa lama yang statusnya masih aktif tidak ikut. Menyebut angkatan berarti memilih langsung.
        if (empty($t['angkatan'])) {
            $min = Jenjang::tahunMinimal($this->siakad->tahunAkademikAktif());
            $mahasiswaAktif = collect($mahasiswa)->keyBy('nim');
            [$dalam, $luar] = $hasil['daftar']->partition(fn ($m) => Jenjang::dalamRentang($mahasiswaAktif[$m['nim']]['angkatan'] ?? 0, $min));
            $hasil['daftar'] = $dalam->values();
            $hasil['di_luar_rentang'] = $luar->count();
        }

        return $hasil;
    }

    private function dariDaftarNim(string $teks): array
    {
        $daftar = collect();
        $hilang = [];
        $tidakAktif = [];

        foreach (array_slice(self::parseNim($teks), 0, self::MAKS_NIM_MANUAL) as $nim) {
            try {
                $m = $this->siakad->mahasiswa($nim);
            } catch (SiakadException $e) {
                if (! $e->isNotFound()) {
                    throw $e;
                }
                $hilang[] = $nim;

                continue;
            }

            if (($m['status'] ?? null) !== 'AKTIF') {
                $tidakAktif[] = (string) ($m['nim'] ?? $nim);

                continue;
            }
            $daftar->push(['nim' => (string) ($m['nim'] ?? $nim), 'nama' => $m['nama'] ?? null]);
        }

        return ['daftar' => $daftar->unique('nim')->values(), 'tidak_ditemukan' => $hilang, 'tidak_aktif' => $tidakAktif];
    }

    /**
     * Saring menurut jenjang lalu pisahkan yang tidak aktif.
     *
     * @param  Collection<int,array<string,mixed>>  $baris
     */
    private function pisahkanAktif(Collection $baris, ?string $jenjang, string $kunciStatus): array
    {
        $baris = $baris->filter(fn ($b) => ! empty($b['nim']));

        if ($jenjang) {
            $peta = Jenjang::peta($this->siakad);
            $baris = $baris->filter(fn ($b) => ($peta[$b['prodiId'] ?? ''] ?? null) === $jenjang);
        }

        [$aktif, $lain] = $baris->partition(fn ($b) => ($b[$kunciStatus] ?? null) === 'AKTIF');

        return [
            'daftar' => $aktif->map(fn ($b) => ['nim' => (string) $b['nim'], 'nama' => $b['nama'] ?? null])
                ->unique('nim')->sortBy('nama')->values(),
            'tidak_ditemukan' => [],
            'tidak_aktif' => $lain->pluck('nim')->map(fn ($n) => (string) $n)->unique()->values()->all(),
        ];
    }
}
