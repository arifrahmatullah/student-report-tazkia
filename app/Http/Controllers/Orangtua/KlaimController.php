<?php

namespace App\Http\Controllers\Orangtua;

use App\Http\Controllers\Controller;
use App\Models\ParentMahasiswa;
use App\Services\Siakad\SiakadClient;
use App\Services\Siakad\SiakadException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KlaimController extends Controller
{
    /** Satu mahasiswa paling banyak dipantau oleh sekian akun (ayah, ibu, wali). */
    private const MAKS_AKUN_PER_MAHASISWA = 3;

    public function create(): View
    {
        return view('orangtua.klaim');
    }

    public function store(Request $request, SiakadClient $siakad): RedirectResponse
    {
        $data = $request->validate([
            'nim' => ['required', 'regex:/^[0-9]{6,15}$/'],
            'tanggal_lahir' => ['required', 'date_format:Y-m-d', 'before:today'],
        ], [
            'nim.regex' => 'NIM harus berupa angka.',
            'tanggal_lahir.date_format' => 'Format tanggal lahir tidak valid.',
        ]);

        $this->batasiPercobaan($request, $data['nim']);

        try {
            $hasil = $siakad->verifikasi($data['nim'], $data['tanggal_lahir']);
        } catch (SiakadException) {
            throw ValidationException::withMessages([
                'nim' => 'Layanan data akademik sedang tidak dapat dihubungi. Coba lagi beberapa saat.',
            ]);
        }

        // Pesan sengaja sama untuk "NIM tidak ada" dan "tanggal lahir salah".
        if (! ($hasil['cocok'] ?? false)) {
            throw ValidationException::withMessages([
                'nim' => 'NIM atau tanggal lahir tidak cocok dengan data kampus.',
            ]);
        }

        $user = $request->user();
        $nim = $hasil['nim'] ?? $data['nim'];

        if ($user->anak()->where('nim', $nim)->exists()) {
            return redirect()->route('orangtua.anak.show', $nim)->with('status', 'Mahasiswa ini sudah ada di daftar Anda.');
        }

        if (ParentMahasiswa::where('nim', $nim)->count() >= self::MAKS_AKUN_PER_MAHASISWA) {
            throw ValidationException::withMessages([
                'nim' => 'Jumlah akun orang tua untuk mahasiswa ini sudah mencapai batas. Hubungi bagian akademik.',
            ]);
        }

        $user->anak()->create([
            'nim' => $nim,
            'nama_mahasiswa' => $hasil['nama'] ?? null,
            'siakad_mahasiswa_id' => $hasil['id'] ?? null,
        ]);

        RateLimiter::clear($this->kunciUser($request));

        return redirect()->route('orangtua.anak.show', $nim)->with('status', 'Berhasil. Mahasiswa ditambahkan ke daftar Anda.');
    }

    /**
     * SIAKAD belum membatasi laju /verifikasi, jadi dibatasi di sini:
     * per akun, per IP, dan per NIM (mencegah tebak tanggal lahir dari banyak akun).
     */
    private function batasiPercobaan(Request $request, string $nim): void
    {
        $batas = [
            [$this->kunciUser($request), 5, 600],
            ['klaim:ip:'.$request->ip(), 20, 3600],
            ['klaim:nim:'.$nim, 10, 3600],
        ];

        foreach ($batas as [$kunci, $maks]) {
            if (RateLimiter::tooManyAttempts($kunci, $maks)) {
                $detik = RateLimiter::availableIn($kunci);
                throw ValidationException::withMessages([
                    'nim' => 'Terlalu banyak percobaan. Coba lagi dalam '.ceil($detik / 60).' menit.',
                ]);
            }
        }

        foreach ($batas as [$kunci, , $peluruh]) {
            RateLimiter::hit($kunci, $peluruh);
        }
    }

    private function kunciUser(Request $request): string
    {
        return 'klaim:user:'.$request->user()->id;
    }
}
