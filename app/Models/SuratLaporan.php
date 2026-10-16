<?php

namespace App\Models;

use App\Models\Concerns\PakaiUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratLaporan extends Model
{
    use PakaiUuid;

    protected $table = 'surat_laporan';

    protected $fillable = [
        'dibuat_oleh', 'penandatangan_id', 'beasiswa_id', 'beasiswa_nama',
        'sampai_tahun_akademik_id', 'sampai_tahun_akademik_nama',
        'filter_prodi_id', 'filter_angkatan', 'nomor_surat', 'tanggal_surat', 'tanggal_hijriah',
        'tempat_surat', 'perihal', 'catatan', 'snapshot', 'lampiran', 'nama_beasiswa_surat',
        'instansi_tujuan', 'penerima_nama', 'penerima_jabatan', 'penerima_alamat', 'teks_angkatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'snapshot' => 'array',
        ];
    }

    public function penandatangan(): BelongsTo
    {
        return $this->belongsTo(Penandatangan::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
