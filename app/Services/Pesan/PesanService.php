<?php

namespace App\Services\Pesan;

use App\Jobs\KirimWaPesanJob;
use App\Models\ParentMahasiswa;
use App\Models\Pesan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PesanService
{
    /**
     * Membuat pesan dan menyalurkannya ke inbox akun orang tua yang sudah menautkan mahasiswa sasaran.
     * Satu baris per (mahasiswa, akun). Mahasiswa yang belum punya akun orang tua mendapat satu baris
     * tanpa akun, supaya laporan bisa menunjukkan siapa yang belum terjangkau.
     * Pengiriman WhatsApp (bila diminta) dikerjakan di antrean.
     *
     * @param  array{judul:string,isi:string,ke_orangtua:bool,ke_mahasiswa:bool,kirim_wa:bool,target:array,jenis?:string}  $data
     * @param  Collection<int,array{nim:string,nama:?string}>  $daftar
     */
    public function buat(User $pengirim, array $data, Collection $daftar): Pesan
    {
        $pesan = DB::transaction(function () use ($pengirim, $data, $daftar) {
            $pesan = Pesan::create([
                'pengirim_id' => $pengirim->id,
                'jenis' => $data['jenis'] ?? 'umum',
                'judul' => $data['judul'],
                'isi' => $data['isi'],
                'target' => $data['target'],
                'daftar_nim' => $daftar->values()->all(),
                'jumlah_mahasiswa' => $daftar->count(),
                'ke_orangtua' => $data['ke_orangtua'],
                'ke_mahasiswa' => $data['ke_mahasiswa'],
                'kirim_wa' => $data['kirim_wa'],
                'status' => $data['kirim_wa'] ? 'diproses' : 'selesai',
                'selesai_pada' => $data['kirim_wa'] ? null : now(),
            ]);

            if ($data['ke_orangtua']) {
                $this->buatBarisInbox($pesan, $daftar);
            }

            return $pesan;
        });

        if ($data['kirim_wa']) {
            KirimWaPesanJob::dispatch($pesan->id);
        }

        return $pesan;
    }

    /** @param Collection<int,array{nim:string,nama:?string}> $daftar */
    private function buatBarisInbox(Pesan $pesan, Collection $daftar): void
    {
        $akunPerNim = ParentMahasiswa::whereIn('nim', $daftar->pluck('nim'))
            ->get(['user_id', 'nim'])->groupBy('nim');

        $now = now();
        $baris = [];
        foreach ($daftar as $m) {
            $akun = $akunPerNim->get($m['nim']);
            foreach ($akun?->pluck('user_id')->unique() ?? [null] as $userId) {
                $baris[] = [
                    'pesan_id' => $pesan->id,
                    'nim' => $m['nim'],
                    'nama_mahasiswa' => $m['nama'],
                    'tipe' => 'akun',
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($baris, 500) as $bagian) {
            DB::table('pesan_penerima')->insert($bagian);
        }
    }
}
