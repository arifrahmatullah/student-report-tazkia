<?php

namespace App\Services\WhatsApp;

use App\Support\NomorHp;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Pengirim pesan template lewat WhatsApp Cloud API (Meta). */
class WhatsAppClient
{
    /**
     * Siap mengirim? Butuh token, ID nomor, dan nama template yang dimaksud
     * (tanpa argumen: template pengumuman umum).
     */
    public function aktif(?string $template = null): bool
    {
        return filled(config('whatsapp.token'))
            && filled(config('whatsapp.phone_number_id'))
            && filled($template ?? config('whatsapp.template_broadcast'));
    }

    /**
     * Kirim template. Variabel berindeks angka dikirim sebagai {{1}}, {{2}}, …;
     * variabel berkunci teks dikirim sebagai variabel BERNAMA ({{nama}}, {{nim}}, …).
     *
     * @param  array<int|string,string>  $variabel
     * @return string id pesan dari Meta
     *
     * @throws PengirimanDiblokir bila ditolak pengaman lingkungan uji (tidak ada yang dikirim)
     * @throws RuntimeException bila gagal; pesan galat sudah dibersihkan dari token
     */
    public function kirimTemplate(string $ke, string $template, array $variabel): string
    {
        if (! $this->boleh($ke)) {
            throw new PengirimanDiblokir($this->alasanDiblokir());
        }

        $parameter = [];
        foreach ($variabel as $kunci => $nilai) {
            $baris = ['type' => 'text', 'text' => (string) $nilai];
            if (is_string($kunci)) {
                $baris['parameter_name'] = $kunci;
            }
            $parameter[] = $baris;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $ke,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => config('whatsapp.bahasa')],
            ],
        ];
        if ($parameter) {
            $payload['template']['components'] = [['type' => 'body', 'parameters' => $parameter]];
        }

        try {
            $res = $this->http()->post($this->url(config('whatsapp.phone_number_id').'/messages'), $payload);
        } catch (ConnectionException) {
            throw new RuntimeException('WhatsApp tidak dapat dihubungi.');
        }

        if ($res->failed()) {
            throw new RuntimeException('['.$res->status().'] '.($res->json('error.message') ?? 'Permintaan ke WhatsApp ditolak.'));
        }

        return (string) ($res->json('messages.0.id') ?? '');
    }

    /**
     * Status dan isi template dari Meta (hanya membaca). Null bila WA_WABA_ID kosong, template tidak ada,
     * atau Meta tidak terjangkau; pemanggil menampilkan "tidak dapat diperiksa" dan tidak memblokir.
     *
     * @return array{nama:string,status:string,kategori:?string,isi:?string}|null
     */
    public function infoTemplate(string $nama): ?array
    {
        if (blank(config('whatsapp.token')) || blank(config('whatsapp.waba_id'))) {
            return null;
        }

        return Cache::remember("wa:template:{$nama}", 600, function () use ($nama) {
            try {
                $res = $this->http()->get($this->url(config('whatsapp.waba_id').'/message_templates'), [
                    'name' => $nama, 'fields' => 'name,status,category,components',
                ]);
            } catch (ConnectionException) {
                return null;
            }
            if ($res->failed()) {
                return null;
            }

            $t = collect($res->json('data', []))->firstWhere('name', $nama);
            if (! $t) {
                return null;
            }
            $isi = collect($t['components'] ?? [])->firstWhere('type', 'BODY')['text'] ?? null;

            return ['nama' => $t['name'], 'status' => $t['status'] ?? '?', 'kategori' => $t['category'] ?? null, 'isi' => $isi];
        });
    }

    /**
     * Pengaman lingkungan uji. Di production semua nomor boleh. Di lingkungan lain hanya nomor
     * yang ada di WA_NOMOR_UJI ("*" = semua).
     *
     * @return array{terbatas:bool,nomor:list<string>,semua:bool}
     */
    public function modeUji(): array
    {
        $daftar = collect(preg_split('/[,;\r\n]+/', (string) config('whatsapp.nomor_uji'), -1, PREG_SPLIT_NO_EMPTY));
        $semua = $daftar->map(fn ($n) => trim($n))->contains('*');
        $nomor = $daftar->map(fn ($n) => trim($n))->reject(fn ($n) => $n === '' || $n === '*')->map(fn ($n) => NomorHp::normalisasi($n))->filter()->unique()->values()->all();

        return ['terbatas' => ! app()->environment('production') && ! $semua, 'nomor' => $nomor, 'semua' => $semua];
    }

    public function boleh(string $nomor): bool
    {
        $mode = $this->modeUji();

        return ! $mode['terbatas'] || in_array(NomorHp::normalisasi($nomor), $mode['nomor'], true);
    }

    public function alasanDiblokir(): string
    {
        $n = count($this->modeUji()['nomor']);

        return 'Diblokir pengaman lingkungan uji ('.app()->environment().'): WhatsApp hanya dikirim ke '
            .($n ? "{$n} nomor di WA_NOMOR_UJI" : 'nomor yang didaftarkan di WA_NOMOR_UJI (saat ini kosong)').'.';
    }

    private function http()
    {
        return Http::withToken((string) config('whatsapp.token'))
            ->acceptJson()
            ->timeout(config('whatsapp.timeout'));
    }

    private function url(string $path): string
    {
        return rtrim(config('whatsapp.api_url'), '/').'/'.ltrim($path, '/');
    }
}
