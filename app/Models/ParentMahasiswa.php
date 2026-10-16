<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentMahasiswa extends Model
{
    protected $table = 'parent_mahasiswa';

    protected $fillable = ['user_id', 'nim', 'nama_mahasiswa', 'siakad_mahasiswa_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
