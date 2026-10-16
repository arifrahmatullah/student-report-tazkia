<?php

namespace App\Http\Controllers\Akademik;

use App\Http\Controllers\Controller;
use App\Services\Pesan\Jenjang;
use App\Services\Pesan\PesanService;
use App\Services\Pesan\ReminderKrs;
use App\Services\Siakad\SiakadClient;
use App\Services\Siakad\SiakadException;
use App\Services\WhatsApp\PengirimanDiblokir;
use App\Services\WhatsApp\WhatsAppClient;
use App\Support\NomorHp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pengingat KRS: tampilkan dulu mahasiswa aktif yang belum punya KRS disetujui (dari SIAKAD), lengkap dengan
 * nomor mahasiswa, tes kirim ke nomor sendiri, baru kirim. Bawaannya hanya angkatan 4 tahun terakhir
 * (config pesan.tahun_ke_belakang) dan hanya mahasiswa aktif. Hasil pengiriman tercatat di log WhatsApp.
 */
class ReminderKrsController extends Controller
{
    private const JENIS_LOG_TES = 'tes_reminder_krs';

    public function index(Request $request, SiakadClient $siakad, WhatsAppClient $wa): View
    {
        $filter = $request->validate([
            'jenjang' => ['nullable', Rule::in(Jenjang::DAFTAR)],
            'angkatan' => ['nullable', 'integer', 'between:1,2100'],
            'prodi_id' => ['nullable', 'string', 'max:64'],
            'lama' => ['nullable', 'boolean'],
        ]);
        $lama = $request->boolean('lama');
        $tOrtu = ReminderKrs::template('orangtua');
        $tMhs = ReminderKrs::template('mahasiswa');
        $angkatanEfektif = Jenjang::normalisasiAngkatan(isset($filter['angkatan']) ? (int) $filter['angkatan'] : null, $filter['jenjang'] ?? null);

        $data = [
            'tahunAktif' => null, 'parameter' => null, 'gagal' => false, 'tahunMin' => null, 'tahunMulai' => null,
            'filter' => $filter, 'lama' => $lama, 'daftar' => null, 'ringkasan' => null, 'tersembunyi' => 0,
            'angkatanEfektif' => $angkatanEfektif,
            'labelFilter' => Jenjang::label($filter['jenjang'] ?? null, $angkatanEfektif),
            'prodi' => collect(),
            'templateOrtu' => $tOrtu, 'templateMhs' => $tMhs, 'adaTemplateMhs' => ReminderKrs::adaTemplateMahasiswa(),
            'infoOrtu' => $wa->infoTemplate($tOrtu), 'infoMhs' => $wa->infoTemplate($tMhs),
            'waSiap' => $wa->aktif($tOrtu) || $wa->aktif($tMhs), 'modeUji' => $wa->modeUji(),
            'tesTerakhir' => DB::table('whatsapp_logs')->where('jenis', self::JENIS_LOG_TES)->where('status', 'terkirim')
                ->where('created_at', '>=', now()->subDay())->count(),
        ];

        try {
            $ta = $siakad->tahunAkademikAktif();
            $tahunMin = Jenjang::tahunMinimal($ta);
            $data['tahunAktif'] = $ta;
            $data['tahunMin'] = $tahunMin;
            $data['tahunMulai'] = $tahunMin + (int) config('pesan.tahun_ke_belakang');
            $data['parameter'] = $ta ? ReminderKrs::parameter($ta) : null;
            $data['prodi'] = collect($siakad->prodi())->sortBy('nama')->values();

            // Rentang angkatan berlaku bila staf tidak menyebut angkatan tertentu dan tidak meminta semua angkatan.
            $pakaiRentang = ! $lama && empty($filter['angkatan']);

            if (! empty($filter['angkatan']) || ! empty($filter['prodi_id']) || ! empty($filter['jenjang'])) {
                $semua = $this->ambilBelumKrs($siakad, $filter);
                $daftar = $pakaiRentang ? $this->dalamRentang($semua, $tahunMin) : $semua;
                $data['daftar'] = $daftar->sortBy([['prodiNama', 'asc'], ['nama', 'asc']])->values();
                $data['tersembunyi'] = $semua->count() - $daftar->count();
            } else {
                // Layar awal: ringkasan per jenjang lalu angkatan, supaya staf memilih sebelum melihat daftar.
                $semua = $this->ambilBelumKrs($siakad, [], 120);
                $tampil = $pakaiRentang ? $this->dalamRentang($semua, $tahunMin) : $semua;
                $data['tersembunyi'] = $semua->count() - $tampil->count();
                $data['ringkasan'] = $tampil
                    ->groupBy(fn ($m) => $m['jenjang'] ?? 'Lainnya')
                    ->map(fn ($perJenjang) => $perJenjang->groupBy('angkatan')->map(fn ($g) => [
                        'jumlah' => $g->count(),
                        'draf' => $g->where('statusKrs', 'DRAFT')->count(),
                        'diajukan' => $g->where('statusKrs', 'DIAJUKAN')->count(),
                    ])->sortKeysDesc())
                    ->sortKeys();
            }
        } catch (SiakadException) {
            $data['gagal'] = true;
        }

        return view('akademik.reminder-krs.index', $data);
    }

    /**
     * Nomor tujuan per NIM: nomor mahasiswa (utuh, karena pesan ditujukan kepadanya) dan satu nomor orang tua
     * (disamarkan). Dipanggil bertahap dari halaman agar daftar langsung tampil tanpa menunggu SIAKAD.
     */
    public function kontak(Request $request, SiakadClient $siakad): JsonResponse
    {
        $d = $request->validate(['nim' => ['required', 'array', 'max:25'], 'nim.*' => ['string', 'regex:/^[0-9]{4,20}$/']]);

        $hasil = [];
        foreach ($d['nim'] as $nim) {
            try {
                $k = $siakad->orangTua($nim);
                $ortu = ReminderKrs::pilihKontak($k);
                $hasil[$nim] = [
                    'mahasiswa' => ReminderKrs::nomorMahasiswa($k),
                    'orangtua' => $ortu
                        ? ['ada' => true, 'peran' => $ortu['peran'], 'nama' => $ortu['nama'], 'nomor' => ReminderKrs::samarkan($ortu['nomor'])]
                        : ['ada' => false],
                ];
            } catch (SiakadException) {
                $hasil[$nim] = ['galat' => true, 'mahasiswa' => ['ada' => false], 'orangtua' => ['ada' => false]];
            }
        }

        return response()->json($hasil);
    }

    /** Tes kirim ke nomor sendiri memakai template penerima yang dipilih; hasilnya langsung tampil dan tercatat di log. */
    public function tes(Request $request, SiakadClient $siakad, WhatsAppClient $wa): RedirectResponse
    {
        $d = $request->validate([
            'nomor' => ['required', 'string', 'max:200'],
            'nama' => ['nullable', 'string', 'max:100'],
            'nim' => ['nullable', 'string', 'max:30'],
            'untuk' => ['nullable', Rule::in(['mahasiswa', 'orangtua'])],
        ], ['nomor.required' => 'Isi nomor WhatsApp untuk tes.']);

        $template = ReminderKrs::template($d['untuk'] ?? 'mahasiswa');
        if (! $wa->aktif($template)) {
            throw ValidationException::withMessages(['nomor' => 'WhatsApp belum dikonfigurasi (WA_TOKEN dan WA_PHONE_NUMBER_ID di .env).']);
        }

        $kunci = 'tes-krs:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($kunci, 10)) {
            throw ValidationException::withMessages(['nomor' => 'Terlalu banyak tes kirim. Coba lagi dalam '.ceil(RateLimiter::availableIn($kunci) / 60).' menit.']);
        }

        $nomor = collect(preg_split('/[,;\n]+/', $d['nomor'], -1, PREG_SPLIT_NO_EMPTY))->map(fn ($n) => trim($n))->unique()->values();
        if ($nomor->count() > 3) {
            throw ValidationException::withMessages(['nomor' => 'Tes kirim maksimal ke 3 nomor sekaligus.']);
        }

        try {
            $ta = $siakad->tahunAkademikAktif();
        } catch (SiakadException) {
            throw ValidationException::withMessages(['nomor' => 'SIAKAD tidak dapat dihubungi, jadi tahun akademik untuk isi pesan belum diketahui.']);
        }
        if (! $ta) {
            throw ValidationException::withMessages(['nomor' => 'Tidak ada tahun akademik aktif di SIAKAD.']);
        }

        $variabel = ['nama' => ($d['nama'] ?? null) ?: 'Nama Mahasiswa', 'nim' => ($d['nim'] ?? null) ?: '12345678'] + ReminderKrs::parameter($ta);

        $hasil = [];
        foreach ($nomor as $mentah) {
            RateLimiter::hit($kunci, 3600);
            $normal = NomorHp::normalisasi($mentah);

            if ($normal === null) {
                $hasil[] = ['nomor' => $mentah, 'status' => 'nomor_tidak_valid', 'keterangan' => 'Bukan nomor HP Indonesia yang valid.'];
                $this->catatTes($request, $variabel, $mentah, null, 'nomor_tidak_valid', 'Bukan nomor HP Indonesia yang valid.', $template);

                continue;
            }

            try {
                $id = $wa->kirimTemplate($normal, $template, $variabel);
                $hasil[] = ['nomor' => $normal, 'status' => 'terkirim', 'keterangan' => 'Diterima WhatsApp.'];
                $this->catatTes($request, $variabel, $mentah, $normal, 'terkirim', $id, $template);
            } catch (PengirimanDiblokir $e) {
                $hasil[] = ['nomor' => $normal, 'status' => 'diblokir', 'keterangan' => $e->getMessage()];
                $this->catatTes($request, $variabel, $mentah, $normal, 'diblokir', $e->getMessage(), $template);
            } catch (\RuntimeException $e) {
                $hasil[] = ['nomor' => $normal, 'status' => 'gagal', 'keterangan' => $e->getMessage()];
                $this->catatTes($request, $variabel, $mentah, $normal, 'gagal', $e->getMessage(), $template);
            }
        }

        return back()->with('hasil_tes', $hasil);
    }

    public function kirim(Request $request, SiakadClient $siakad, WhatsAppClient $wa, PesanService $layanan): RedirectResponse
    {
        $d = $request->validate([
            'jenjang' => ['nullable', Rule::in(Jenjang::DAFTAR)],
            'angkatan' => ['nullable', 'integer', 'between:1,2100'],
            'prodi_id' => ['nullable', 'string', 'max:64'],
            'lama' => ['nullable', 'boolean'],
            'nim' => ['required', 'array', 'min:1', 'max:1000'],
            'nim.*' => ['string', 'regex:/^[0-9]{4,20}$/'],
            'ke_orangtua' => ['nullable', 'boolean'],
            'ke_mahasiswa' => ['nullable', 'boolean'],
            'kirim_wa' => ['nullable', 'boolean'],
        ], ['nim.required' => 'Pilih minimal satu mahasiswa.', 'nim.min' => 'Pilih minimal satu mahasiswa.']);

        if (empty($d['angkatan']) && empty($d['prodi_id']) && empty($d['jenjang'])) {
            throw ValidationException::withMessages(['nim' => 'Pilih jenjang, angkatan, atau program studi terlebih dahulu.']);
        }

        $keOrangtua = $request->boolean('ke_orangtua');
        $keMahasiswa = $request->boolean('ke_mahasiswa');
        $kirimWa = $request->boolean('kirim_wa');

        if (! $keOrangtua && ! $keMahasiswa) {
            throw ValidationException::withMessages(['ke_orangtua' => 'Pilih minimal satu jenis penerima: mahasiswa atau orang tua.']);
        }
        if ($keMahasiswa && ! $kirimWa) {
            throw ValidationException::withMessages(['kirim_wa' => 'Mahasiswa tidak memiliki inbox di portal, jadi pesan untuk mahasiswa hanya dapat dikirim lewat WhatsApp.']);
        }
        if ($kirimWa) {
            $this->pastikanWhatsAppSiap($wa, array_values(array_unique(array_filter([
                $keOrangtua ? ReminderKrs::template('orangtua') : null,
                $keMahasiswa ? ReminderKrs::template('mahasiswa') : null,
            ]))));
        }

        try {
            $ta = $siakad->tahunAkademikAktif();
            // Ambil ulang dari SIAKAD: hanya yang MASIH belum punya KRS disetujui, berstatus aktif, dan (bila rentang
            // berlaku) dalam 4 tahun terakhir yang dikirimi. NIM kiriman formulir hanya menyaring, tidak pernah menambah.
            $masihBelum = $this->ambilBelumKrs($siakad, $d);
            if (! $request->boolean('lama') && empty($d['angkatan'])) {
                $masihBelum = $this->dalamRentang($masihBelum, Jenjang::tahunMinimal($ta));
            }
            $masihBelum = $masihBelum->keyBy('nim');
        } catch (SiakadException) {
            throw ValidationException::withMessages(['nim' => 'SIAKAD tidak dapat dihubungi, daftar mahasiswa belum dapat diperiksa. Coba lagi beberapa saat.']);
        }
        if (! $ta) {
            throw ValidationException::withMessages(['nim' => 'Tidak ada tahun akademik aktif di SIAKAD.']);
        }

        $dipilih = collect($d['nim'])->unique()->values();
        $daftar = $dipilih->filter(fn ($nim) => $masihBelum->has($nim))
            ->map(fn ($nim) => ['nim' => (string) $nim, 'nama' => $masihBelum[$nim]['nama'] ?? null])->values();
        $dilewati = $dipilih->count() - $daftar->count();

        if ($daftar->isEmpty()) {
            throw ValidationException::withMessages(['nim' => 'Semua mahasiswa yang dipilih sudah mengisi KRS (atau tidak termasuk daftar), jadi tidak ada yang dikirimi.']);
        }

        $param = ReminderKrs::parameter($ta);
        $pesan = $layanan->buat($request->user(), [
            'jenis' => 'reminder_krs',
            'judul' => "Pengingat pengisian KRS {$param['semester']} {$param['academic_year']}",
            'isi' => ReminderKrs::teksInbox($param),
            'ke_orangtua' => $keOrangtua,
            'ke_mahasiswa' => $keMahasiswa,
            'kirim_wa' => $kirimWa,
            'target' => [
                'mode' => 'krs_belum',
                'label' => collect(['Belum mengisi KRS', Jenjang::label($d['jenjang'] ?? null, Jenjang::normalisasiAngkatan(isset($d['angkatan']) ? (int) $d['angkatan'] : null, $d['jenjang'] ?? null)) ?: null])->filter()->implode(' · '),
                'krs' => $param,
                'dilewati_sudah_krs' => $dilewati,
            ],
        ], $daftar);

        $catatan = $dilewati > 0 ? " {$dilewati} mahasiswa dilewati karena sudah mengisi KRS." : '';

        return redirect()->route('akademik.pesan.show', $pesan)->with('status', ($kirimWa
            ? 'Pengingat masuk ke antrean WhatsApp'.($keOrangtua ? ' dan inbox portal' : '').' dan sedang dikirim di latar belakang.'
            : 'Pengingat terkirim ke inbox portal.').$catatan);
    }

    /**
     * @param  list<string>  $templates
     *
     * @throws ValidationException
     */
    private function pastikanWhatsAppSiap(WhatsAppClient $wa, array $templates): void
    {
        foreach ($templates as $template) {
            if (! $wa->aktif($template)) {
                throw ValidationException::withMessages(['kirim_wa' => 'WhatsApp belum dikonfigurasi, jadi pengingat hanya dapat dikirim ke inbox portal.']);
            }
        }

        $mode = $wa->modeUji();
        if ($mode['terbatas'] && $mode['nomor'] === []) {
            throw ValidationException::withMessages(['kirim_wa' => $wa->alasanDiblokir().' Isi WA_NOMOR_UJI di .env (atau gunakan lingkungan production).']);
        }

        foreach ($templates as $template) {
            $info = $wa->infoTemplate($template);
            if ($info && $info['status'] !== 'APPROVED') {
                throw ValidationException::withMessages(['kirim_wa' => "Template \"{$template}\" berstatus {$info['status']} di Meta, belum boleh dipakai mengirim."]);
            }
        }
    }

    /**
     * Mahasiswa yang belum punya KRS disetujui: HANYA yang berstatus AKTIF, diberi keterangan jenjang,
     * dan disaring menurut jenjang bila dipilih. Angkatan disesuaikan dengan penulisan jenjangnya
     * (S1 = 2025, S2 = 25).
     *
     * @param  array<string,mixed>  $filter
     * @return Collection<int,array<string,mixed>>
     */
    private function ambilBelumKrs(SiakadClient $siakad, array $filter, int $ttl = 0): Collection
    {
        $jenjang = $filter['jenjang'] ?? null;
        $peta = Jenjang::peta($siakad);

        return collect($siakad->belumIsiKrs([
            'angkatan' => Jenjang::normalisasiAngkatan(isset($filter['angkatan']) ? (int) $filter['angkatan'] : null, $jenjang),
            'prodiId' => $filter['prodi_id'] ?? null,
        ], $ttl))
            ->filter(fn ($m) => ($m['statusMahasiswa'] ?? null) === 'AKTIF')
            ->map(fn ($m) => $m + ['jenjang' => $peta[$m['prodiId'] ?? ''] ?? null])
            ->when($jenjang, fn ($c) => $c->where('jenjang', $jenjang))
            ->values();
    }

    /** Hanya angkatan dari $tahunMin ke atas (S2 yang menulis 2 digit dihitung sebagai 2000 + angkatan). */
    private function dalamRentang(Collection $daftar, int $tahunMin): Collection
    {
        return $daftar->filter(fn ($m) => Jenjang::dalamRentang($m['angkatan'] ?? 0, $tahunMin))->values();
    }

    private function catatTes(Request $request, array $variabel, string $mentah, ?string $nomor, string $status, ?string $respons, string $template): void
    {
        DB::table('whatsapp_logs')->insert([
            'jenis' => self::JENIS_LOG_TES,
            'nim' => $variabel['nim'],
            'nama_mahasiswa' => $variabel['nama'],
            'tipe' => 'tes',
            'nama_penerima' => $request->user()->name,
            'no_hp' => $nomor,
            'template' => $template,
            'parameter' => json_encode($variabel, JSON_UNESCAPED_UNICODE),
            'status' => $status,
            'respons' => $respons,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
