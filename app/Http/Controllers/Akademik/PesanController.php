<?php

namespace App\Http\Controllers\Akademik;

use App\Http\Controllers\Controller;
use App\Models\ParentMahasiswa;
use App\Models\Pesan;
use App\Services\Pesan\Jenjang;
use App\Services\Pesan\PenerimaResolver;
use App\Services\Pesan\PesanService;
use App\Services\Siakad\SiakadClient;
use App\Services\Siakad\SiakadException;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PesanController extends Controller
{
    public function index(): View
    {
        $daftar = Pesan::with('pengirim:id,name')
            ->withCount([
                'penerima as akun_count' => fn ($q) => $q->whereNotNull('user_id'),
                'penerima as dibaca_count' => fn ($q) => $q->whereNotNull('user_id')->whereNotNull('dibaca_pada'),
                'penerima as wa_terkirim_count' => fn ($q) => $q->where('wa_status', 'terkirim'),
            ])
            ->latest()->paginate(15);

        return view('akademik.pesan.index', ['daftar' => $daftar]);
    }

    public function create(SiakadClient $siakad, WhatsAppClient $wa): View
    {
        $gagal = false;
        $muat = function (callable $ambil) use (&$gagal) {
            try {
                return collect($ambil());
            } catch (SiakadException) {
                $gagal = true;

                return collect();
            }
        };

        return view('akademik.pesan.create', [
            'beasiswa' => $muat($siakad->beasiswa(...))->where('status', 'AKTIF')->where('jumlahPenerima', '>', 0)->sortBy('nama')->values(),
            'prodi' => $muat($siakad->prodi(...))->sortBy('nama')->values(),
            'gagal' => $gagal,
            'waAktif' => $wa->aktif(),
        ]);
    }

    /** Perkiraan jumlah penerima untuk target yang sedang dipilih (dipanggil dari form). */
    public function estimasi(Request $request, PenerimaResolver $resolver, SiakadClient $siakad): JsonResponse
    {
        $target = $this->validasiTarget($request);

        try {
            $hasil = $resolver->resolve($target);
        } catch (SiakadException) {
            return response()->json(['pesan' => 'SIAKAD tidak dapat dihubungi saat ini.'], 503);
        }

        $nim = $hasil['daftar']->pluck('nim');
        $terhubung = ParentMahasiswa::whereIn('nim', $nim)->distinct()->pluck('nim');

        return response()->json([
            'mahasiswa' => $nim->count(),
            'terhubung' => $terhubung->count(),
            'belum_terhubung' => $nim->count() - $terhubung->count(),
            'akun' => ParentMahasiswa::whereIn('nim', $nim)->distinct()->count('user_id'),
            'tidak_ditemukan' => $hasil['tidak_ditemukan'],
            'tidak_aktif' => count($hasil['tidak_aktif']),
            'di_luar_rentang' => $hasil['di_luar_rentang'],
            'rentang' => $this->rentang($siakad),
        ]);
    }

    public function store(Request $request, PenerimaResolver $resolver, PesanService $layanan, WhatsAppClient $wa, SiakadClient $siakad): RedirectResponse
    {
        $target = $this->validasiTarget($request);
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'isi' => ['required', 'string', 'max:5000'],
            'ke_orangtua' => ['nullable', 'boolean'],
            'ke_mahasiswa' => ['nullable', 'boolean'],
            'kirim_wa' => ['nullable', 'boolean'],
        ]);

        $keOrangtua = $request->boolean('ke_orangtua');
        $keMahasiswa = $request->boolean('ke_mahasiswa');
        $kirimWa = $request->boolean('kirim_wa');

        if (! $keOrangtua && ! $keMahasiswa) {
            throw ValidationException::withMessages(['ke_orangtua' => 'Pilih minimal satu jenis penerima: orang tua atau mahasiswa.']);
        }
        if ($keMahasiswa && ! $kirimWa) {
            throw ValidationException::withMessages(['kirim_wa' => 'Mahasiswa tidak memiliki inbox di portal, jadi pesan untuk mahasiswa hanya dapat dikirim lewat WhatsApp.']);
        }
        if ($kirimWa && ! $wa->aktif()) {
            throw ValidationException::withMessages(['kirim_wa' => 'WhatsApp belum dikonfigurasi, jadi pesan hanya dapat dikirim ke inbox portal.']);
        }

        try {
            $hasil = $resolver->resolve($target);
        } catch (SiakadException) {
            throw ValidationException::withMessages(['mode' => 'SIAKAD tidak dapat dihubungi, penerima belum dapat ditentukan. Coba lagi beberapa saat.']);
        }

        if ($hasil['daftar']->isEmpty()) {
            throw ValidationException::withMessages(['mode' => 'Tidak ada mahasiswa yang cocok dengan pilihan penerima ini, sehingga pesan tidak dikirim.']);
        }

        $pesan = $layanan->buat($request->user(), [
            'judul' => $data['judul'],
            'isi' => $data['isi'],
            'ke_orangtua' => $keOrangtua,
            'ke_mahasiswa' => $keMahasiswa,
            'kirim_wa' => $kirimWa,
            'target' => $target + ['label' => $this->label($target, $siakad), 'tidak_ditemukan' => $hasil['tidak_ditemukan'], 'tidak_aktif' => $hasil['tidak_aktif'], 'di_luar_rentang' => $hasil['di_luar_rentang'], 'rentang' => $this->rentang($siakad)],
        ], $hasil['daftar']);

        return redirect()->route('akademik.pesan.show', $pesan)->with('status', $kirimWa
            ? 'Pesan masuk ke inbox portal dan notifikasi WhatsApp sedang dikirim di latar belakang.'
            : 'Pesan terkirim ke inbox portal.');
    }

    public function show(Pesan $pesan): View
    {
        $baris = $pesan->penerima()->get();
        $inbox = $baris->where('tipe', 'akun');
        $wa = $baris->where('tipe', '!=', 'akun');

        // Rekap per mahasiswa: status inbox dan status tiap kontak WhatsApp.
        $perMahasiswa = collect($pesan->daftar_nim ?? [])->map(function ($m) use ($inbox, $wa) {
            $akun = $inbox->where('nim', $m['nim'])->whereNotNull('user_id');

            return $m + [
                'akun' => $akun->count(),
                'dibaca' => $akun->whereNotNull('dibaca_pada')->count(),
                'wa' => $wa->where('nim', $m['nim'])->values(),
            ];
        });

        return view('akademik.pesan.show', [
            'pesan' => $pesan->load('pengirim:id,name'),
            'perMahasiswa' => $perMahasiswa,
            'ringkas' => [
                'mahasiswa' => $perMahasiswa->count(),
                'terhubung' => $perMahasiswa->where('akun', '>', 0)->count(),
                'akun' => $inbox->whereNotNull('user_id')->count(),
                'dibaca' => $inbox->whereNotNull('user_id')->whereNotNull('dibaca_pada')->count(),
                'wa' => $wa->countBy('wa_status')->all(),
            ],
        ]);
    }

    /** Rentang angkatan bawaan untuk ditampilkan di formulir (mis. "2022–2026"). */
    private function rentang(SiakadClient $siakad): ?string
    {
        try {
            $min = Jenjang::tahunMinimal($siakad->tahunAkademikAktif());

            return $min.'–'.($min + (int) config('pesan.tahun_ke_belakang'));
        } catch (SiakadException) {
            return null;
        }
    }

    /** Validasi bagian "siapa penerimanya" (dipakai form dan endpoint estimasi). */
    private function validasiTarget(Request $request): array
    {
        $d = $request->validate([
            'mode' => ['required', Rule::in(PenerimaResolver::MODE)],
            'beasiswa_id' => ['nullable', 'required_if:mode,beasiswa', 'string'],
            'jenjang' => ['nullable', Rule::in(Jenjang::DAFTAR)],
            'prodi_id' => ['nullable', 'string'],
            'angkatan' => ['nullable', 'integer', 'between:1,2100'],
            'nim' => ['nullable', 'required_if:mode,nim', 'string', 'max:5000'],
        ], [
            'beasiswa_id.required_if' => 'Pilih program beasiswa.',
            'nim.required_if' => 'Isi minimal satu NIM.',
        ]);

        if ($d['mode'] === 'angkatan' && empty($d['prodi_id']) && empty($d['angkatan']) && empty($d['jenjang'])) {
            throw ValidationException::withMessages(['angkatan' => 'Pilih jenjang, program studi, atau isi angkatan, agar pesan tidak terkirim ke seluruh mahasiswa tanpa sengaja.']);
        }

        return ['mode' => $d['mode']]
            + ($d['mode'] === 'beasiswa' ? ['beasiswa_id' => $d['beasiswa_id']] : [])
            + ($d['mode'] !== 'nim' ? ['jenjang' => $d['jenjang'] ?? null, 'prodi_id' => $d['prodi_id'] ?? null, 'angkatan' => $d['angkatan'] ?? null] : [])
            + ($d['mode'] === 'nim' ? ['nim' => $d['nim']] : []);
    }

    /** Tulisan ringkas target untuk daftar dan detail pesan. */
    private function label(array $t, SiakadClient $siakad): string
    {
        try {
            $prodi = isset($t['prodi_id']) ? collect($siakad->prodi())->firstWhere('id', $t['prodi_id'])['nama'] ?? null : null;
            $beasiswa = ($t['mode'] === 'beasiswa') ? collect($siakad->beasiswa())->firstWhere('id', $t['beasiswa_id'])['nama'] ?? null : null;
        } catch (SiakadException) {
            $prodi = $beasiswa = null;
        }

        $bagian = array_filter([
            $t['mode'] === 'beasiswa' ? 'Penerima '.($beasiswa ?? 'beasiswa') : null,
            $t['mode'] === 'angkatan' ? 'Mahasiswa aktif' : null,
            $t['mode'] === 'nim' ? 'Daftar NIM ('.count(PenerimaResolver::parseNim($t['nim'])).')' : null,
            $prodi,
            ($t['mode'] !== 'nim') ? (Jenjang::label($t['jenjang'] ?? null, Jenjang::normalisasiAngkatan(isset($t['angkatan']) ? (int) $t['angkatan'] : null, $t['jenjang'] ?? null)) ?: null) : null,
        ]);

        return implode(' · ', $bagian);
    }
}
