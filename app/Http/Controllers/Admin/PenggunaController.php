<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Kelola pengguna oleh admin. Akun staf dibuat tanpa kata sandi yang diketahui siapa pun: staf masuk dengan
 * Google memakai email yang sama, dan GoogleController menautkannya otomatis (peran staf dipercaya).
 * Akun orang tua hanya bisa dilihat dan diaktifkan/dinonaktifkan, tidak pernah dinaikkan menjadi staf,
 * karena orang tua mendaftar sendiri dan emailnya belum tentu milik pendaftar.
 */
class PenggunaController extends Controller
{
    private const PERAN_STAF = [User::ROLE_ADMIN, User::ROLE_AKADEMIK];

    public function index(Request $request): View
    {
        $peran = $request->query('peran', 'staf');
        $q = trim((string) $request->query('q', ''));

        $daftar = User::query()->with('roles:id,name')
            ->when($peran === 'orangtua', fn ($k) => $k->role(User::ROLE_ORANGTUA)->whereDoesntHave('roles', fn ($r) => $r->whereIn('name', self::PERAN_STAF)))
            ->when($peran === 'staf', fn ($k) => $k->role(self::PERAN_STAF))
            ->when($q !== '', fn ($k) => $k->where(fn ($w) => $w->where('name', 'like', '%'.addcslashes($q, '%_\\').'%')->orWhere('email', 'like', '%'.addcslashes($q, '%_\\').'%')))
            ->orderByDesc('is_active')->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('admin.pengguna.index', ['daftar' => $daftar, 'peran' => $peran, 'q' => $q]);
    }

    public function create(): View
    {
        return view('admin.pengguna.form', ['u' => new User(['is_active' => true]), 'peranSaatIni' => User::ROLE_AKADEMIK]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'peran' => ['required', Rule::in(self::PERAN_STAF)],
        ], ['email.unique' => 'Email ini sudah terdaftar. Akun yang sudah ada tidak bisa dijadikan staf dari sini.']);

        $user = User::create([
            'name' => $data['name'], 'email' => $data['email'], 'is_active' => true,
            'password' => Str::random(48), // tidak diketahui siapa pun; staf masuk lewat Google
        ]);
        $user->assignRole($data['peran']);

        return redirect()->route('admin.pengguna.index')
            ->with('status', "Akun {$user->name} dibuat. Minta mereka masuk dengan Google memakai {$user->email}.");
    }

    public function edit(Request $request, User $pengguna): View
    {
        return view('admin.pengguna.form', [
            'u' => $pengguna,
            'peranSaatIni' => $pengguna->hasRole(User::ROLE_ADMIN) ? User::ROLE_ADMIN : ($pengguna->hasRole(User::ROLE_AKADEMIK) ? User::ROLE_AKADEMIK : User::ROLE_ORANGTUA),
            'diriSendiri' => $pengguna->is($request->user()),
        ]);
    }

    public function update(Request $request, User $pengguna): RedirectResponse
    {
        $staf = $pengguna->hasAnyRole(self::PERAN_STAF);
        $diriSendiri = $pengguna->is($request->user());
        $aktif = $request->boolean('is_active');

        if ($staf) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'peran' => ['required', Rule::in(self::PERAN_STAF)],
            ]);
            $peranBaru = $data['peran'];
        } else {
            $peranBaru = null;
        }

        $kehilanganAdmin = $pengguna->hasRole(User::ROLE_ADMIN) && ($peranBaru !== null && $peranBaru !== User::ROLE_ADMIN || ! $aktif);
        if ($kehilanganAdmin) {
            if ($diriSendiri) {
                return back()->withErrors(['peran' => 'Anda tidak bisa menurunkan atau menonaktifkan akun Anda sendiri.'])->withInput();
            }
            $adminLain = User::role(User::ROLE_ADMIN)->where('is_active', true)->where('id', '!=', $pengguna->id)->exists();
            if (! $adminLain) {
                return back()->withErrors(['peran' => 'Harus tersisa minimal satu admin aktif.'])->withInput();
            }
        }
        if ($diriSendiri && ! $aktif) {
            return back()->withErrors(['peran' => 'Anda tidak bisa menonaktifkan akun Anda sendiri.'])->withInput();
        }

        if ($staf) {
            $pengguna->name = $data['name'];
            $pengguna->syncRoles([$peranBaru]);
        }
        $pengguna->is_active = $aktif;
        $pengguna->save();

        if (! $aktif) {
            // Akun nonaktif langsung keluar dari semua perangkat.
            DB::table('sessions')->where('user_id', $pengguna->id)->delete();
            $pengguna->forceFill(['remember_token' => null])->save();
        }

        return redirect()->route('admin.pengguna.index')->with('status', 'Perubahan disimpan.');
    }
}
