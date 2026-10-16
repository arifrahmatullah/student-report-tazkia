<?php

namespace App\Models;

use App\Models\Concerns\PakaiUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pesan extends Model
{
    use PakaiUuid;

    protected $table = 'pesan';

    protected $fillable = [
        'pengirim_id', 'jenis', 'judul', 'isi', 'target', 'daftar_nim', 'jumlah_mahasiswa', 'ke_mahasiswa', 'ke_orangtua',
        'kirim_wa', 'status', 'dijadwalkan_pada', 'selesai_pada',
    ];

    protected function casts(): array
    {
        return [
            'target' => 'array',
            'daftar_nim' => 'array',
            'ke_mahasiswa' => 'boolean',
            'ke_orangtua' => 'boolean',
            'kirim_wa' => 'boolean',
            'dijadwalkan_pada' => 'datetime',
            'selesai_pada' => 'datetime',
        ];
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengirim_id');
    }

    public function penerima(): HasMany
    {
        return $this->hasMany(PesanPenerima::class);
    }
}
