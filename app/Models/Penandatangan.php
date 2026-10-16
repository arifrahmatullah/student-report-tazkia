<?php

namespace App\Models;

use App\Models\Concerns\PakaiUuid;
use Illuminate\Database\Eloquent\Model;

class Penandatangan extends Model
{
    use PakaiUuid;

    protected $table = 'penandatangan';

    protected $fillable = ['nama', 'jabatan', 'nidn', 'nik', 'prodi_id', 'ttd_path', 'pakai_cap', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'pakai_cap' => 'boolean'];
    }
}
