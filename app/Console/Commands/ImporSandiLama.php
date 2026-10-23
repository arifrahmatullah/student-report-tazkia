<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Menyalin hash sandi BCrypt dari aplikasi lama (Spring, awalan $2a$) ke akun orang tua yang sudah ada,
 * sehingga mereka masuk dengan sandi yang sama. Hash tidak pernah dicetak.
 */
class ImporSandiLama extends Command
{
    protected $signature = 'sandi:impor-lama {berkas : CSV berisi kolom email dan hash sandi} {--kolom-email=email_orang_tua} {--kolom-sandi=password} {--dry-run : Hanya hitung, tidak menulis}';

    protected $description = 'Salin hash sandi BCrypt aplikasi lama ke akun orang tua yang sudah ada (cocok lewat email).';

    public function handle(): int
    {
        $berkas = $this->argument('berkas');
        if (! is_file($berkas) || ! is_readable($berkas)) {
            $this->error('Berkas tidak ditemukan atau tidak bisa dibaca.');

            return self::FAILURE;
        }

        $fh = fopen($berkas, 'r');
        $kepala = array_map(fn ($h) => trim((string) $h, "\xEF\xBB\xBF \t"), fgetcsv($fh, 0, ',', '"', '') ?: []);
        $kE = array_search($this->option('kolom-email'), $kepala, true);
        $kS = array_search($this->option('kolom-sandi'), $kepala, true);
        if ($kE === false || $kS === false) {
            $this->error('Kolom email/sandi tidak ada di berkas. Kolom yang ada: '.implode(', ', $kepala));
            fclose($fh);

            return self::FAILURE;
        }

        $peta = [];
        $tidakValid = 0;
        $konflik = 0;
        while (($r = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            $email = Str::lower(trim((string) ($r[$kE] ?? '')));
            $hash = self::keBcryptLaravel(trim((string) ($r[$kS] ?? '')));
            if ($email === '' || $hash === null) {
                $tidakValid++;

                continue;
            }
            if (array_key_exists($email, $peta) && $peta[$email] !== $hash) {
                $konflik++;   // satu email, dua hash berbeda: tidak ada yang dipakai
                $peta[$email] = false;

                continue;
            }
            $peta[$email] ??= $hash;
        }
        fclose($fh);
        $peta = array_filter($peta);

        $pengguna = User::whereIn('email', array_keys($peta))->get(['id', 'email']);
        $diperbarui = 0;
        $bukanOrangTua = 0;

        foreach ($pengguna as $u) {
            // Hanya akun orang tua. Akun staf tidak boleh diubah sandinya lewat berkas ini.
            if (! $u->hasRole(User::ROLE_ORANGTUA) || $u->hasAnyRole([User::ROLE_ADMIN, User::ROLE_AKADEMIK])) {
                $bukanOrangTua++;

                continue;
            }
            if (! $this->option('dry-run')) {
                DB::table('users')->where('id', $u->id)->update(['password' => $peta[$u->email], 'remember_token' => null, 'updated_at' => now()]);
            }
            $diperbarui++;
        }

        $this->line(sprintf(
            '%s: email dengan hash valid %d | akun cocok %d | %s %d | tanpa akun %d | dilewati bukan orang tua %d | hash tidak valid/kosong %d | email dengan hash bentrok %d',
            $this->option('dry-run') ? 'UJI COBA (tidak menulis)' : 'SELESAI',
            count($peta), $pengguna->count(), $this->option('dry-run') ? 'akan diperbarui' : 'diperbarui', $diperbarui,
            count($peta) - $pengguna->count(), $bukanOrangTua, $tidakValid, $konflik
        ));

        return self::SUCCESS;
    }

    /** Hash BCrypt Spring ($2a$/$2b$) -> bentuk yang diterima Laravel ($2y$). Algoritmanya sama. Null bila bukan BCrypt. */
    public static function keBcryptLaravel(string $hash): ?string
    {
        if (! preg_match('/^\$2[abxy]\$(\d\d)\$[.\/A-Za-z0-9]{53}$/', $hash)) {
            return null;
        }

        return '$2y$'.substr($hash, 4);
    }
}
