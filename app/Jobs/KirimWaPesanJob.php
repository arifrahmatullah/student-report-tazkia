<?php

namespace App\Jobs;

use App\Models\Pesan;
use App\Models\PesanPenerima;
use App\Services\Pesan\ReminderKrs;
use App\Services\Siakad\SiakadClient;
use App\Services\Siakad\SiakadException;
use App\Services\WhatsApp\PengirimanDiblokir;
use App\Services\WhatsApp\WhatsAppClient;
use App\Support\NomorHp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Mengirim notifikasi WhatsApp (template) untuk satu pesan: mencari kontak tiap mahasiswa di SIAKAD,
 * lalu mengirim satu per satu dengan jeda. SETIAP hasil dicatat di whatsapp_logs, termasuk kontak yang
 * dilewati (tanpa nomor, nomor tidak valid, nomor sama), supaya staf bisa melihat siapa yang tidak terjangkau.
 *
 * Jenis pesan:
 *  - umum         : ke semua kontak (ayah, ibu, wali) bila diminta; variabel berurutan {{1}} nama, {{2}} judul.
 *  - reminder_krs : ke SATU kontak orang tua per mahasiswa (ayah, lalu ibu, lalu wali; yang pertama bernomor valid);
 *                   variabel bernama nama, nim, semester, academic_year, date.
 */
class KirimWaPesanJob implements ShouldQueue
{
    use Queueable;

    private const URUTAN_ORANGTUA = ['ayah', 'ibu', 'wali'];

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(public int $pesanId) {}

    public function handle(SiakadClient $siakad, WhatsAppClient $wa): void
    {
        $pesan = Pesan::find($this->pesanId);
        if (! $pesan) {
            return;
        }

        // Sudah pernah diproses (mis. antrean dijalankan ulang): jangan kirim dua kali.
        if ($pesan->penerima()->where('tipe', '!=', 'akun')->exists()) {
            return;
        }

        $reminder = $pesan->jenis === 'reminder_krs';
        $sudahDikirimKe = [];

        foreach ($pesan->daftar_nim ?? [] as $m) {
            try {
                $kontak = $siakad->orangTua($m['nim']);
            } catch (SiakadException $e) {
                $this->hasil($pesan, $m, 'kontak', null, null, 'gagal', 'Data kontak tidak dapat dimuat dari SIAKAD.');
                Log::warning('Kontak WA gagal dimuat', ['status' => $e->status]);

                continue;
            }

            foreach ($this->sasaran($pesan, $kontak, $m, $reminder) as [$tipe, $nama, $mentah]) {
                $nomor = NomorHp::normalisasi($mentah);

                if ($nomor === null) {
                    $this->hasil($pesan, $m, $tipe, $nama, null, filled($mentah) ? 'nomor_tidak_valid' : 'tanpa_nomor');

                    continue;
                }
                if (isset($sudahDikirimKe[$nomor])) {
                    $this->hasil($pesan, $m, $tipe, $nama, $nomor, 'duplikat', 'Nomor yang sama sudah menerima pesan ini.');

                    continue;
                }
                $sudahDikirimKe[$nomor] = true;

                $template = $reminder
                    ? ReminderKrs::template($tipe === 'mahasiswa' ? 'mahasiswa' : 'orangtua')
                    : (string) config('whatsapp.template_broadcast');
                $variabel = $reminder ? $this->variabelKrs($pesan, $m) : [$this->sapaan($tipe, $nama), $pesan->judul];
                $this->kirim($wa, $pesan, $m, $tipe, $nama, $nomor, $template, $variabel);
                usleep(config('whatsapp.jeda_ms') * 1000);
            }
        }

        $pesan->update(['status' => 'selesai', 'selesai_pada' => now()]);
    }

    public function failed(\Throwable $e): void
    {
        Pesan::whereKey($this->pesanId)->update(['status' => 'gagal', 'selesai_pada' => now()]);
        Log::error('KirimWaPesanJob gagal', ['pesan' => $this->pesanId, 'galat' => $e->getMessage()]);
    }

    /**
     * Daftar tujuan untuk satu mahasiswa: [tipe, nama penerima, nomor mentah].
     *
     * @return list<array{0:string,1:?string,2:?string}>
     */
    private function sasaran(Pesan $pesan, array $kontak, array $m, bool $reminder): array
    {
        $orangtua = [];
        foreach (self::URUTAN_ORANGTUA as $peran) {
            if (isset($kontak[$peran]) && is_array($kontak[$peran])) {
                $orangtua[] = [$peran, $kontak[$peran]['nama'] ?? null, $kontak[$peran]['noHp'] ?? null];
            }
        }

        $daftar = [];
        if ($pesan->ke_orangtua) {
            if ($reminder) {
                // Satu kontak saja: yang pertama bernomor valid; bila tidak ada, catat satu baris "tanpa nomor".
                $pilih = collect($orangtua)->first(fn ($o) => NomorHp::normalisasi($o[2]) !== null)
                    ?? $orangtua[0] ?? ['ayah', null, null];
                $daftar[] = $pilih;
            } else {
                $daftar = $orangtua;
            }
        }
        if ($pesan->ke_mahasiswa) {
            $daftar[] = ['mahasiswa', $m['nama'] ?? null, $kontak['noHpMahasiswa'] ?? null];
        }

        return $daftar;
    }

    /** @return array<string,string> */
    private function variabelKrs(Pesan $pesan, array $m): array
    {
        $krs = $pesan->target['krs'] ?? [];

        return [
            'nama' => (string) ($m['nama'] ?? ''),
            'nim' => (string) $m['nim'],
            'semester' => (string) ($krs['semester'] ?? ''),
            'academic_year' => (string) ($krs['academic_year'] ?? ''),
            'date' => (string) ($krs['date'] ?? 'segera'),
        ];
    }

    private function kirim(WhatsAppClient $wa, Pesan $pesan, array $m, string $tipe, ?string $nama, string $nomor, string $template, array $variabel): void
    {
        $baris = $this->buatBaris($pesan, $m, $tipe, $nama, $nomor, 'antre');

        try {
            $idPesan = $wa->kirimTemplate($nomor, $template, $variabel);
            $baris->update(['wa_status' => 'terkirim', 'wa_terkirim_pada' => now()]);
            $this->log($pesan, $baris, $m, $tipe, $nama, $nomor, 'terkirim', $idPesan, $template, $variabel);
        } catch (PengirimanDiblokir $e) {
            $baris->update(['wa_status' => 'diblokir', 'wa_error' => $e->getMessage()]);
            $this->log($pesan, $baris, $m, $tipe, $nama, $nomor, 'diblokir', $e->getMessage(), $template, $variabel);
        } catch (RuntimeException $e) {
            $baris->update(['wa_status' => 'gagal', 'wa_error' => $e->getMessage()]);
            $this->log($pesan, $baris, $m, $tipe, $nama, $nomor, 'gagal', $e->getMessage(), $template, $variabel);
        }
    }

    private function sapaan(string $tipe, ?string $nama): string
    {
        return match (true) {
            filled($nama) && $tipe === 'ayah' => 'Bapak '.$nama,
            filled($nama) && $tipe === 'ibu' => 'Ibu '.$nama,
            filled($nama) => $nama,
            $tipe === 'mahasiswa' => 'Mahasiswa',
            default => 'Bapak/Ibu',
        };
    }

    /** Hasil yang tidak berupa pengiriman (dilewati/gagal memuat kontak): baris penerima + catatan log. */
    private function hasil(Pesan $pesan, array $m, string $tipe, ?string $nama, ?string $nomor, string $status, ?string $keterangan = null): void
    {
        $baris = $this->buatBaris($pesan, $m, $tipe, $nama, $nomor, $status, $keterangan);
        $this->log($pesan, $baris, $m, $tipe, $nama, $nomor, $status, $keterangan, null, null);
    }

    private function buatBaris(Pesan $pesan, array $m, string $tipe, ?string $nama, ?string $nomor, string $status, ?string $galat = null): PesanPenerima
    {
        return $pesan->penerima()->create([
            'nim' => $m['nim'],
            'nama_mahasiswa' => $m['nama'] ?? null,
            'tipe' => $tipe,
            'nama_penerima' => $nama,
            'no_hp' => $nomor,
            'wa_status' => $status,
            'wa_error' => $galat,
        ]);
    }

    private function log(Pesan $pesan, PesanPenerima $baris, array $m, string $tipe, ?string $nama, ?string $nomor, string $status, ?string $respons, ?string $template, ?array $variabel): void
    {
        DB::table('whatsapp_logs')->insert([
            'jenis' => $pesan->jenis === 'reminder_krs' ? 'reminder_krs' : 'broadcast',
            'pesan_id' => $pesan->id,
            'pesan_penerima_id' => $baris->id,
            'nim' => $m['nim'],
            'nama_mahasiswa' => $m['nama'] ?? null,
            'tipe' => $tipe,
            'nama_penerima' => $nama,
            'no_hp' => $nomor,
            'template' => $template,
            'parameter' => $variabel === null ? null : json_encode($variabel, JSON_UNESCAPED_UNICODE),
            'status' => $status,
            'respons' => $respons,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
