<?php

namespace App\Services\Pesan;

use App\Support\NomorHp;
use Illuminate\Support\Carbon;

/** Aturan pengingat KRS yang dipakai bersama oleh pratinjau, tes kirim, dan pengiriman. */
class ReminderKrs
{
    private const SEMESTER = ['GASAL' => 'Ganjil', 'GANJIL' => 'Ganjil', 'GENAP' => 'Genap', 'PENDEK' => 'Pendek'];

    /** Urutan kontak orang tua yang dicoba (sama seperti aplikasi lama: ayah dulu). */
    public const URUTAN = ['ayah', 'ibu', 'wali'];

    /**
     * Isi variabel template dari tahun akademik aktif.
     *
     * @param  array<string,mixed>  $ta
     * @return array{semester:string,academic_year:string,date:string}
     */
    public static function parameter(array $ta): array
    {
        return [
            'semester' => self::SEMESTER[strtoupper($ta['semester'] ?? '')] ?? (string) ($ta['semester'] ?? ''),
            'academic_year' => (string) ($ta['tahun'] ?? ''),
            'date' => ! empty($ta['tanggalSelesaiKrs'])
                ? Carbon::parse($ta['tanggalSelesaiKrs'])->translatedFormat('d F Y')
                : 'segera',
        ];
    }

    /**
     * Kontak orang tua pertama yang punya nomor HP valid.
     *
     * @return array{peran:string,nama:?string,nomor:string}|null
     */
    public static function pilihKontak(array $kontak): ?array
    {
        foreach (self::URUTAN as $peran) {
            $o = $kontak[$peran] ?? null;
            $nomor = is_array($o) ? NomorHp::normalisasi($o['noHp'] ?? null) : null;
            if ($nomor !== null) {
                return ['peran' => $peran, 'nama' => $o['nama'] ?? null, 'nomor' => $nomor];
            }
        }

        return null;
    }

    /** Template yang dipakai untuk penerima 'orangtua' atau 'mahasiswa' (mahasiswa kembali ke template orang tua bila belum ada). */
    public static function template(string $penerima): string
    {
        $mahasiswa = (string) config('whatsapp.template_reminder_krs_mahasiswa');

        return $penerima === 'mahasiswa' && $mahasiswa !== '' ? $mahasiswa : (string) config('whatsapp.template_reminder_krs');
    }

    /** True bila ada template khusus mahasiswa (bukan sekadar cadangan template orang tua). */
    public static function adaTemplateMahasiswa(): bool
    {
        return (string) config('whatsapp.template_reminder_krs_mahasiswa') !== '';
    }

    /**
     * Nomor mahasiswa dari data kontak SIAKAD.
     *
     * @return array{ada:bool,nomor?:string,mentah?:string}  nomor = format lokal bertanda hubung (0812-3456-7890)
     */
    public static function nomorMahasiswa(array $kontak): array
    {
        $mentah = trim((string) ($kontak['noHpMahasiswa'] ?? ''));
        $normal = NomorHp::normalisasi($mentah);

        if ($normal !== null) {
            $lokal = '0'.substr($normal, 2);

            return ['ada' => true, 'nomor' => substr($lokal, 0, 4).'-'.substr($lokal, 4, 4).'-'.substr($lokal, 8)];
        }

        return $mentah === '' ? ['ada' => false] : ['ada' => false, 'mentah' => $mentah];
    }

    /** 6281234567890 -> 0812-••••-890 (cukup untuk dikenali staf, tanpa memamerkan seluruh nomor di layar). */
    public static function samarkan(string $nomor62): string
    {
        $lokal = '0'.substr($nomor62, 2);

        return substr($lokal, 0, 4).'-••••-'.substr($lokal, -3);
    }

    /** Teks inbox portal (WhatsApp memakai template Meta, tetapi inbox perlu teks sendiri). */
    public static function teksInbox(array $p): string
    {
        return "Assalamu'alaikum Wr. Wb.\n\n"
            ."Diinformasikan bahwa Ananda belum menyelesaikan pengisian KRS Semester {$p['semester']} TA {$p['academic_year']}.\n\n"
            ."Mohon Ananda segera menyelesaikan pengisian KRS paling lambat {$p['date']}. "
            ."Jika KRS tidak diisi pada waktu yang ditetapkan, Ananda tidak dapat mengikuti perkuliahan dan ujian semester.\n\n"
            ."Demikian, terima kasih.\nWassalamu'alaikum Wr. Wb.\n\nBagian Akademik";
    }
}
