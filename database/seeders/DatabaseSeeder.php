<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_AKADEMIK, User::ROLE_ORANGTUA] as $role) {
            Role::findOrCreate($role, 'web');
        }

        // Akun awal hanya untuk pengembangan. Ganti kata sandinya sebelum production.
        if (app()->environment('local')) {
            User::firstOrCreate(
                ['email' => 'admin@example.test'],
                ['name' => 'Admin', 'password' => 'password']
            )->assignRole(User::ROLE_ADMIN);

            User::firstOrCreate(
                ['email' => 'akademik@example.test'],
                ['name' => 'Staf Akademik', 'password' => 'password']
            )->assignRole(User::ROLE_AKADEMIK);
        }
    }
}
