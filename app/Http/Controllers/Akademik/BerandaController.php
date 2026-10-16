<?php

namespace App\Http\Controllers\Akademik;

use App\Http\Controllers\Controller;
use App\Models\ParentMahasiswa;
use App\Models\Pesan;
use App\Models\PesanPenerima;
use App\Models\User;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(): View
    {
        $bulanIni = now()->startOfMonth();
        $inboxBulanIni = PesanPenerima::whereNotNull('user_id')->where('created_at', '>=', $bulanIni);

        return view('akademik.beranda', [
            'pesanBulanIni' => Pesan::where('created_at', '>=', $bulanIni)->count(),
            'jangkauan' => (clone $inboxBulanIni)->count(),
            'persenDibaca' => ($total = (clone $inboxBulanIni)->count()) > 0
                ? round((clone $inboxBulanIni)->whereNotNull('dibaca_pada')->count() / $total * 100)
                : null,
            'orangtua' => User::role(User::ROLE_ORANGTUA)->count(),
            'anakTertaut' => ParentMahasiswa::distinct('nim')->count('nim'),
            'terbaru' => Pesan::latest()->limit(5)->get(),
        ]);
    }
}
