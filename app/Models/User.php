<?php

namespace App\Models;

use App\Models\Concerns\PakaiUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PakaiUuid;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_AKADEMIK = 'akademik';
    public const ROLE_ORANGTUA = 'orangtua';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function anak(): HasMany
    {
        return $this->hasMany(ParentMahasiswa::class);
    }

    public function pesanDiterima(): HasMany
    {
        return $this->hasMany(PesanPenerima::class);
    }
}
