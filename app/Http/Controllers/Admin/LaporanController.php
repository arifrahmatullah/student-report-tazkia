<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penandatangan;
use App\Models\SuratLaporan;
use App\Services\Siakad\SiakadClient;
use App\Services\Siakad\SiakadException;
use App\Support\Hijri;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LaporanController extends Controller
{
    /** Penulisan semester pada surat. */
    private const SEMESTER = ['GENAP' => 'Genap', 'GASAL' => 'Ganjil', 'GANJIL' => 'Ganjil', 'PENDEK' => 'Pendek'];

    public function index(): View
    {
        return view('admin.laporan.index', [
            'daftar' => SuratLaporan::with('pembuat:id,name')->latest()->paginate(15),
        ]);
    }

    public function create(SiakadClient $siakad): View
    {
        // Tiap daftar dimuat sendiri: satu yang gagal tidak mengosongkan yang lain.
        $gagal = false;
        $muat = function (string $nama, callable $ambil) use (&$gagal) {
            try {
                return collect($ambil());
            } catch (SiakadException $e) {
                $gagal = true;
                Log::warning("SIAKAD gagal memuat {$nama} untuk form laporan", ['status' => $e->status, 'kode' => $e->errorCode]);

                return collect();
            }
        };

        $beasiswa = $muat('beasiswa', $siakad->beasiswa(...))->where('jumlahPenerima', '>', 0)->sortBy('nama')->values();
        $tahun = $muat('tahun akademik', $siakad->tahunAkademik(...))->sortByDesc('kode')->values();
        $prodi = $muat('prodi', $siakad->prodi(...))->sortBy('nama')->values();

        return view('admin.laporan.create', [
            'beasiswa' => $beasiswa,
            'tahun' => $tahun,
            'prodi' => $prodi,
            'gagal' => $gagal,
            'penandatangan' => Penandatangan::where('is_active', true)->orderBy('nama')->get(),
            // Bawaan: semester terakhir yang sudah selesai (bukan yang sedang berjalan).
            'tahunBawaan' => $tahun->firstWhere('aktif', false)['id'] ?? null,
        ]);
    }

    public function store(Request $request, SiakadClient $siakad): RedirectResponse
    {
        $data = $request->validate([
            'beasiswa_id' => ['required', 'string'],
            'tahun_akademik_id' => ['required', 'string'],
            'prodi_id' => ['nullable', 'string'],
            'angkatan' => ['nullable', 'integer', 'between:1990,2100'],
            'penandatangan_id' => ['required', 'exists:penandatangan,id'],
            'nomor_surat' => ['required', 'string', 'max:150'],
            'lampiran' => ['required', 'string', 'max:100'],
            'tempat_surat' => ['required', 'string', 'max:100'],
            'tanggal_surat' => ['required', 'date'],
            'tanggal_hijriah' => ['nullable', 'string', 'max:60'],
            'perihal' => ['required', 'string', 'max:255'],
            'nama_beasiswa_surat' => ['required', 'string', 'max:255'],
            'instansi_tujuan' => ['required', 'string', 'max:255'],
            'penerima_nama' => ['required', 'string', 'max:255'],
            'penerima_jabatan' => ['required', 'string', 'max:255'],
            'penerima_alamat' => ['required', 'string', 'max:1000'],
            'teks_angkatan' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ], [
            'penandatangan_id.required' => 'Pilih penandatangan surat.',
            'penandatangan_id.exists' => 'Penandatangan tidak ditemukan.',
        ]);

        try {
            $laporan = $siakad->laporanAkademik($data['beasiswa_id'], $data['tahun_akademik_id'], [
                'prodiId' => $data['prodi_id'] ?? null,
                'angkatan' => $data['angkatan'] ?? null,
            ]);
        } catch (SiakadException $e) {
            throw ValidationException::withMessages([
                'beasiswa_id' => $e->isNotFound()
                    ? 'Beasiswa atau tahun akademik tidak ditemukan di SIAKAD.'
                    : 'SIAKAD tidak dapat dihubungi. Coba lagi beberapa saat.',
            ]);
        }

        // Urutan mengikuti surat contoh: mahasiswa dan mata kuliah berurut abjad nama, semester berurut nomor.
        $laporan['penerima'] = collect($laporan['penerima'] ?? [])
            ->sortBy(fn ($p) => mb_strtolower($p['nama'] ?? ''))
            ->map(function ($p) {
                $p['semester'] = collect($p['semester'] ?? [])->sortBy('semesterKe')->map(function ($smt) {
                    $smt['matakuliah'] = collect($smt['matakuliah'] ?? [])
                        ->sortBy(fn ($mk) => mb_strtolower($mk['nama'] ?? ''))->values()->all();

                    return $smt;
                })->values()->all();

                return $p;
            })->values()->all();

        if (empty($laporan['penerima'])) {
            throw ValidationException::withMessages([
                'beasiswa_id' => 'Tidak ada penerima yang cocok dengan pilihan ini, sehingga laporan tidak dibuat.',
            ]);
        }

        $surat = SuratLaporan::create([
            'dibuat_oleh' => $request->user()->id,
            'penandatangan_id' => $data['penandatangan_id'],
            'beasiswa_id' => $data['beasiswa_id'],
            'beasiswa_nama' => $laporan['beasiswa']['nama'] ?? '-',
            'sampai_tahun_akademik_id' => $data['tahun_akademik_id'],
            'sampai_tahun_akademik_nama' => $laporan['sampaiTahunAkademik']['nama'] ?? '-',
            'filter_prodi_id' => $data['prodi_id'] ?? null,
            'filter_angkatan' => $data['angkatan'] ?? null,
            'nomor_surat' => $data['nomor_surat'],
            'lampiran' => $data['lampiran'],
            'tanggal_surat' => $data['tanggal_surat'],
            'tanggal_hijriah' => $data['tanggal_hijriah'] ?? null,
            'tempat_surat' => $data['tempat_surat'],
            'perihal' => $data['perihal'],
            'nama_beasiswa_surat' => $data['nama_beasiswa_surat'],
            'instansi_tujuan' => $data['instansi_tujuan'],
            'penerima_nama' => $data['penerima_nama'],
            'penerima_jabatan' => $data['penerima_jabatan'],
            'penerima_alamat' => $data['penerima_alamat'],
            'teks_angkatan' => $data['teks_angkatan'] ?? null,
            'catatan' => $data['catatan'] ?? null,
            // Salinan angka saat dibuat: dokumen bertanda tangan tidak boleh berubah diam-diam.
            'snapshot' => [
                'diambil_pada' => now()->toIso8601String(),
                'beasiswa' => $laporan['beasiswa'] ?? null,
                'sampaiTahunAkademik' => $laporan['sampaiTahunAkademik'] ?? null,
                'penerima' => $laporan['penerima'],
            ],
        ]);

        return redirect()->route('admin.laporan.show', $surat)->with('status', 'Laporan dibuat. Periksa angkanya, lalu unduh PDF.');
    }

    public function show(SuratLaporan $laporan): View
    {
        return view('admin.laporan.show', [
            'surat' => $laporan->load('penandatangan', 'pembuat:id,name'),
            'hijriah' => $this->hijriah($laporan),
        ]);
    }

    public function pdf(SuratLaporan $laporan)
    {
        $laporan->load('penandatangan');
        $ps = $laporan->penandatangan;
        $sampai = $laporan->snapshot['sampaiTahunAkademik'] ?? [];

        $pdf = Pdf::loadView('surat.laporan', [
            'surat' => $laporan,
            'penerima' => collect($laporan->snapshot['penerima'] ?? []),
            'hijriah' => $this->hijriah($laporan),
            'semesterLabel' => 'Semester '.(self::SEMESTER[strtoupper($sampai['semester'] ?? '')] ?? '').' Tahun Ajaran '.($sampai['tahun'] ?? $laporan->sampai_tahun_akademik_nama),
            'ttd' => $this->ttdDataUri($ps?->ttd_path),
            'aset' => [
                'latar' => $this->aset('latar.png'),
                'basmalah' => $this->aset('basmalah.png'),
                'cap' => $this->aset('cap.png'),
                'logo' => $this->aset('logo-transkrip.png'),
                'salamPembuka' => $this->aset('salam-pembuka.png'),
                'salamPenutup' => $this->aset('salam-penutup.png'),
            ],
        ])->setPaper('a4');

        return $pdf->stream('Laporan-Akademik-'.Str::slug($laporan->nama_beasiswa_surat ?: $laporan->beasiswa_nama).'-'.$laporan->tanggal_surat->format('Ymd').'.pdf');
    }

    public function destroy(SuratLaporan $laporan): RedirectResponse
    {
        $laporan->delete();

        return redirect()->route('admin.laporan.index')->with('status', 'Laporan dihapus.');
    }

    private function hijriah(SuratLaporan $surat): string
    {
        return $surat->tanggal_hijriah
            ?: Hijri::format($surat->tanggal_surat->copy()->addDays((int) config('surat.koreksi_hijriah')));
    }

    private function aset(string $nama): string
    {
        return 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('surat/'.$nama)));
    }

    private function ttdDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return 'data:'.Storage::disk('local')->mimeType($path).';base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
