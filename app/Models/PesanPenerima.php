<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesanPenerima extends Model
{
    protected $table = 'pesan_penerima';

    protected $fillable = [
        'pesan_id', 'nim', 'nama_mahasiswa', 'tipe', 'nama_penerima', 'no_hp', 'user_id',
        'dibaca_pada', 'wa_status', 'wa_error', 'wa_terkirim_pada',
    ];

    protected function casts(): array
    {
        return [
            'dibaca_pada' => 'datetime',
            'wa_terkirim_pada' => 'datetime',
        ];
    }

    public function pesan(): BelongsTo
    {
        return $this->belongsTo(Pesan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
