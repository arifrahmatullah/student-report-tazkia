<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/** Alamat (URL) memakai UUID, bukan nomor urut, agar tidak bisa ditebak. Kunci utama tetap id. */
trait PakaiUuid
{
    protected static function bootPakaiUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
