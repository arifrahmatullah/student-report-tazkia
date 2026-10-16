<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        return match (true) {
            $user->hasRole(User::ROLE_ADMIN) => redirect()->route('admin.beranda'),
            $user->hasRole(User::ROLE_AKADEMIK) => redirect()->route('akademik.beranda'),
            $user->hasRole(User::ROLE_ORANGTUA) => redirect()->route('orangtua.beranda'),
            default => abort(403, 'Akun ini belum memiliki peran.'),
        };
    }
}
