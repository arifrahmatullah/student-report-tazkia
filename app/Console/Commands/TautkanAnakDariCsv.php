<?php

namespace App\Console\Commands;

use App\Models\ParentMahasiswa;
use App\Models\User;
use App\Services\Siakad\SiakadClient;
use App\Services\Siakad\SiakadException;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Menautkan akun orang tua ke mahasiswa berdasarkan NIM di CSV (melewati verifikasi tanggal lahir di halaman klaim,
 * karena daftar ini berasal dari data resmi kampus). Pengaman: NIM harus ada di SIAKAD, satu mahasiswa maksimal
 * 3 akun (sama seperti klaim biasa), dan akun dengan terlalu banyak anak dilewati untuk ditinjau manual.
 */
class TautkanAnakDariCsv extends Command
{
    private const MAKS_AKUN_PER_MAHASISWA = 3;

    protected $signature = 'anak:tautkan-dari-csv {berkas* : Satu atau lebih CSV} {--kolom-email=email_orang_tua} {--kolom-nim=nim_mahasiswa} {--maks-anak=5 : Akun dengan anak lebih banyak dari ini dilewati} {--dry-run : Hanya hitung, tidak menulis}';

    protected $description = 'Tautkan akun orang tua ke anaknya dari NIM di CSV (NIM dicek ke SIAKAD).';

    public function handle(SiakadClient $siakad): int
    {
        $peta = [];
        foreach ($this->argument('berkas') as $berkas) {
            if (! is_file($berkas) || ! is_readable($berkas)) {
                $this->error("Berkas tidak ditemukan atau tidak bisa dibaca: {$berkas}");

                return self::FAILURE;
            }
            $fh = fopen($berkas, 'r');
            $kepala = array_map(fn ($h) => trim((string) $h, "\xEF\xBB\xBF \t"), fgetcsv($fh, 0, ',', '"', '') ?: []);
            $kE = array_search($this->option('kolom-email'), $kepala, true);
            $kN = array_search($this->option('kolom-nim'), $kepala, true);
            if ($kE === false || $kN === false) {
                $this->error("Kolom email/NIM tidak ada di {$berkas}. Kolom yang ada: ".implode(', ', $kepala));
                fclose($fh);

                return self::FAILURE;
            }
            while (($r = fgetcsv($fh, 0, ',', '"', '')) !== false) {
                $email = Str::lower(trim((string) ($r[$kE] ?? '')));
                $nim = trim((string) ($r[$kN] ?? ''));
                if ($email !== '' && preg_match('/^[0-9]{6,15}$/', $nim)) {
                    $peta[$email][$nim] = true;
                }
            }
            fclose($fh);
        }

        $akun = User::role(User::ROLE_ORANGTUA)->whereIn('email', array_keys($peta))->get(['id', 'email']);
        $maks = (int) $this->option('maks-anak');
        $dry = (bool) $this->option('dry-run');

        $dilewati = [];   // [email tersamar => alasan]
        $dipakai = [];    // user_id => [nim, ...]
        foreach ($akun as $u) {
            $nims = array_keys($peta[$u->email]);
            if (count($nims) > $maks) {
                $dilewati[self::samarkan($u->email)] = 'terlalu banyak anak ('.count($nims).') — periksa manual';

                continue;
            }
            $dipakai[$u->id] = $nims;
        }

        // Cek tiap NIM ke SIAKAD sekali saja (panggilan berurutan; SIAKAD menolak paralel).
        $siakadInfo = [];
        $tidakDitemukan = 0;
        $galat = 0;
        foreach (array_unique(array_merge(...array_values($dipakai) ?: [[]])) as $nim) {
            try {
                $m = $siakad->mahasiswa($nim);
                $siakadInfo[$nim] = ['nama' => $m['nama'] ?? null, 'id' => $m['id'] ?? null];
            } catch (SiakadException $e) {
                $e->isNotFound() ? $tidakDitemukan++ : $galat++;
            }
        }

        $baru = 0;
        $sudahAda = 0;
        $penuh = 0;
        $tanpaSiakad = 0;
        $dihitung = [];   // nim => jumlah akun tertaut (termasuk yang baru pada putaran ini)
        foreach ($dipakai as $userId => $nims) {
            foreach ($nims as $nim) {
                if (! isset($siakadInfo[$nim])) {
                    $tanpaSiakad++;

                    continue;
                }
                if (ParentMahasiswa::where('user_id', $userId)->where('nim', $nim)->exists()) {
                    $sudahAda++;

                    continue;
                }
                $dihitung[$nim] ??= ParentMahasiswa::where('nim', $nim)->count();
                if ($dihitung[$nim] >= self::MAKS_AKUN_PER_MAHASISWA) {
                    $penuh++;

                    continue;
                }
                if (! $dry) {
                    ParentMahasiswa::create(['user_id' => $userId, 'nim' => $nim, 'nama_mahasiswa' => $siakadInfo[$nim]['nama'], 'siakad_mahasiswa_id' => $siakadInfo[$nim]['id']]);
                }
                $dihitung[$nim]++;
                $baru++;
            }
        }

        $this->line(sprintf(
            '%s: akun cocok %d | akun dilewati %d | NIM dicek ke SIAKAD %d (tidak ditemukan %d, gagal cek %d) | %s %d tautan | sudah tertaut %d | batas 3 akun per mahasiswa tercapai %d | dilewati karena NIM tak dikenal %d',
            $dry ? 'UJI COBA (tidak menulis)' : 'SELESAI', $akun->count(), count($dilewati), count($siakadInfo) + $tidakDitemukan + $galat,
            $tidakDitemukan, $galat, $dry ? 'akan dibuat' : 'dibuat', $baru, $sudahAda, $penuh, $tanpaSiakad
        ));
        foreach ($dilewati as $email => $alasan) {
            $this->line("  dilewati {$email}: {$alasan}");
        }

        return self::SUCCESS;
    }

    private static function samarkan(string $email): string
    {
        [$u, $d] = array_pad(explode('@', $email, 2), 2, '');

        return (strlen($u) > 3 ? substr($u, 0, 2).'***'.substr($u, -1) : substr($u, 0, 1).'***').'@'.$d;
    }
}
