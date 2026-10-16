<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentMahasiswa;
use App\Models\Penandatangan;
use App\Models\SuratLaporan;
use App\Models\User;
use App\Services\Siakad\SiakadClient;
use App\Services\Siakad\SiakadException;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(SiakadClient $siakad): View
    {
        // Angka dari SIAKAD bersifat tambahan; kegagalannya tidak boleh merusak beranda.
        try {
            $beasiswa = collect($siakad->beasiswa());
            $beasiswaAktif = $beasiswa->where('status', 'AKTIF')->count();
            $penerima = $beasiswa->where('status', 'AKTIF')->sum('jumlahPenerima');
            $tahunAktif = $siakad->tahunAkademikAktif();
        } catch (SiakadException) {
            $beasiswaAktif = $penerima = $tahunAktif = null;
        }

        return view('admin.beranda', [
            'beasiswaAktif' => $beasiswaAktif,
            'penerima' => $penerima,
            'tahunAktif' => $tahunAktif,
            'jumlahLaporan' => SuratLaporan::count(),
            'jumlahPenandatangan' => Penandatangan::where('is_active', true)->count(),
            'orangtua' => User::role(User::ROLE_ORANGTUA)->count(),
            'anakTertaut' => ParentMahasiswa::distinct('nim')->count('nim'),
            'terbaru' => SuratLaporan::latest()->limit(5)->get(),
        ]);
    }
}
