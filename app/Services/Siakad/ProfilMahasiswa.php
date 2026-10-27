<?php

namespace App\Services\Siakad;

use Illuminate\Support\Facades\Log;

/**
 * Menyusun halaman ringkasan satu mahasiswa dari beberapa endpoint SIAKAD.
 * Tiap bagian diambil terpisah: bila satu gagal, bagian lain tetap tampil
 * dan bagian yang gagal ditandai (null) agar tampilan menulis "belum tersedia".
 */
class ProfilMahasiswa
{
    public function __construct(private readonly SiakadClient $siakad) {}

    /** @return array<string,mixed> */
    public function kumpulkan(string $nim): array
    {
        $aktif = $this->aman('tahun akademik', fn () => $this->siakad->tahunAkademikAktif());
        $taId = $aktif['id'] ?? null;

        $data = [
            'mahasiswa' => $this->aman('mahasiswa', fn () => $this->siakad->mahasiswa($nim)),
            'tahunAkademik' => $aktif,
            'krs' => $this->aman('krs', fn () => $this->siakad->krs($nim, $taId)),
            'riwayatIpk' => $this->aman('riwayat ipk', fn () => $this->siakad->riwayatIpk($nim)),
            'presensi' => $this->aman('presensi', fn () => $this->siakad->presensi($nim, $taId)),
            'tagihan' => $this->aman('tagihan', fn () => $this->ringkasTagihan($nim, $taId)),
            'masaKrs' => $this->masaKrs($aktif),
        ];
        $data['perhatian'] = $this->perhatian($data);

        return $data;
    }

    /** Ringkasan satu mahasiswa untuk kartu di beranda; null bila SIAKAD tidak dapat dihubungi. */
    public function ringkas(string $nim): ?array
    {
        return $this->aman('mahasiswa', fn () => $this->siakad->mahasiswa($nim));
    }

    /**
     * Ringkasan tagihan dihitung dari daftar tagihan, bukan dari /tagihan/ringkasan: endpoint itu ikut menjumlahkan
     * tagihan berstatus DIGABUNG (sudah dilebur ke tagihan lain), sehingga sisa terlihat lebih besar dari yang sebenarnya.
     * Hanya tagihan AKTIF yang dihitung; semester berjalan dipisah dari tunggakan semester sebelumnya.
     *
     * @return array<string,mixed>
     */
    public function ringkasTagihan(string $nim, ?string $taId): array
    {
        $aktif = collect($this->siakad->tagihan($nim))->where('status', 'AKTIF')->values();
        $belum = $aktif->filter(fn ($t) => ($t['sisa'] ?? 0) > 0);
        $berjalan = $taId ? $aktif->firstWhere('tahunAkademikId', $taId) : null;
        $tunggakan = $belum->filter(fn ($t) => ($t['tahunAkademikId'] ?? null) !== $taId);

        return [
            'jumlahTagihan' => $aktif->count(),
            'jumlahLunas' => $aktif->where('lunas', true)->count(),
            'totalTagih' => $aktif->sum('nominalTagih'),
            'totalDibayar' => $aktif->sum('akumulasiPembayaran'),
            'totalBelumDibayar' => $belum->sum('sisa'),
            'jatuhTempoTerdekat' => $belum->pluck('jatuhTempo')->filter()->min(),
            'jumlahLewatJatuhTempo' => $belum->where('lewatJatuhTempo', true)->count(),
            'berjalan' => $berjalan ? [
                'tahunAkademik' => $berjalan['tahunAkademik'] ?? null,
                'totalTagih' => $berjalan['nominalTagih'] ?? 0,
                'totalDibayar' => $berjalan['akumulasiPembayaran'] ?? 0,
                'sisa' => $berjalan['sisa'] ?? 0,
                'jatuhTempo' => $berjalan['jatuhTempo'] ?? null,
            ] : null,
            'tunggakan' => $tunggakan->sum('sisa'),
        ];
    }

    /** Batas kehadiran (persen) di bawah mana orang tua perlu diberi tahu. */
    public const BATAS_HADIR = 75;

    /**
     * Hal-hal yang perlu diketahui orang tua, urut dari yang paling mendesak.
     *
     * @return list<array{tingkat:string,judul:string,isi:string}>
     */
    public function perhatian(array $data): array
    {
        $daftar = [];
        $krs = $data['krs'] ?? null;
        $ta = $data['tahunAkademik'] ?? null;
        $tagihan = $data['tagihan'] ?? null;

        if (($tagihan['jumlahLewatJatuhTempo'] ?? 0) > 0) {
            $daftar[] = ['tingkat' => 'bahaya', 'judul' => 'Tagihan lewat jatuh tempo',
                'isi' => $tagihan['jumlahLewatJatuhTempo'].' tagihan sudah melewati jatuh tempo. Segera selesaikan pembayaran.'];
        } elseif (($tagihan['totalBelumDibayar'] ?? 0) > 0 && ! empty($tagihan['jatuhTempoTerdekat'])) {
            $daftar[] = ['tingkat' => 'info', 'judul' => 'Ada tagihan yang belum dibayar',
                'isi' => 'Jatuh tempo terdekat: '.\Illuminate\Support\Carbon::parse($tagihan['jatuhTempoTerdekat'])->translatedFormat('d F Y').'.'];
        }

        if ($krs && ! ($krs['sudahIsiKrs'] ?? false) && ($data['masaKrs'] ?? false)) {
            $batas = ! empty($ta['tanggalSelesaiKrs'])
                ? ' Batas pengisian '.\Illuminate\Support\Carbon::parse($ta['tanggalSelesaiKrs'])->translatedFormat('d F Y').'.'
                : '';
            $daftar[] = ($krs['statusKrs'] ?? null) === 'DRAFT'
                ? ['tingkat' => 'peringatan', 'judul' => 'KRS belum dikirim', 'isi' => 'KRS masih berupa draf dan belum disetujui.'.$batas]
                : ['tingkat' => 'peringatan', 'judul' => 'Belum mengisi KRS', 'isi' => 'Mahasiswa belum mengisi KRS semester ini.'.$batas];
        }

        $rendah = collect($data['presensi'] ?? [])
            ->filter(fn ($p) => ($p['totalPertemuan'] ?? 0) > 0 && ($p['persenHadir'] ?? 100) < self::BATAS_HADIR)
            ->pluck('mkNama')->filter()->values();
        if ($rendah->isNotEmpty()) {
            $daftar[] = ['tingkat' => 'peringatan', 'judul' => 'Kehadiran di bawah '.self::BATAS_HADIR.'%',
                'isi' => $rendah->join(', ', ' dan ').'.'];
        }

        return $daftar;
    }

    /** True bila hari ini berada dalam rentang KRS tahun akademik aktif. */
    private function masaKrs(?array $ta): bool
    {
        if (! $ta || empty($ta['tanggalMulaiKrs']) || empty($ta['tanggalSelesaiKrs'])) {
            return false;
        }

        $hariIni = now()->toDateString();

        return $hariIni >= $ta['tanggalMulaiKrs'] && $hariIni <= $ta['tanggalSelesaiKrs'];
    }

    private function aman(string $bagian, callable $ambil): mixed
    {
        try {
            return $ambil();
        } catch (SiakadException $e) {
            Log::warning("SIAKAD gagal untuk bagian {$bagian}", ['status' => $e->status, 'kode' => $e->errorCode]);

            return null;
        }
    }
}
