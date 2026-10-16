<?php

namespace App\Http\Controllers\Orangtua;

use App\Http\Controllers\Controller;
use App\Services\Siakad\ProfilMahasiswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnakController extends Controller
{
    public function index(Request $request, ProfilMahasiswa $profil): View
    {
        $anak = $request->user()->anak()->orderBy('nama_mahasiswa')->get();

        return view('orangtua.beranda', [
            'anak' => $anak,
            // nim => data SIAKAD (di-cache) atau null bila gagal
            'ringkasan' => $anak->mapWithKeys(fn ($a) => [$a->nim => $profil->ringkas($a->nim)]),
            'pesanBaru' => $request->user()->pesanDiterima()->whereNull('dibaca_pada')->count(),
        ]);
    }

    public function show(Request $request, string $nim, ProfilMahasiswa $profil): View
    {
        // Hanya NIM yang diklaim akun ini. 404 (bukan 403) agar NIM lain tidak bisa dibedakan.
        $anak = $request->user()->anak()->where('nim', $nim)->firstOrFail();

        return view('orangtua.anak', ['anak' => $anak] + $profil->kumpulkan($nim));
    }

    public function destroy(Request $request, string $nim): RedirectResponse
    {
        $request->user()->anak()->where('nim', $nim)->firstOrFail()->delete();

        return redirect()->route('orangtua.beranda')->with('status', 'Mahasiswa dihapus dari daftar Anda.');
    }
}
