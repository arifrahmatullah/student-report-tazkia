<?php

namespace App\Http\Controllers\Orangtua;

use App\Http\Controllers\Controller;
use App\Models\PesanPenerima;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PesanController extends Controller
{
    public function index(Request $request): View
    {
        $pesan = $request->user()->pesanDiterima()
            ->with('pesan:id,judul,isi,created_at')
            ->latest()
            ->paginate(15);

        return view('orangtua.pesan.index', ['pesan' => $pesan]);
    }

    public function show(Request $request, int $penerima): View
    {
        // Dibatasi ke baris milik akun ini; baris orang lain menghasilkan 404.
        $baris = $request->user()->pesanDiterima()->with('pesan.pengirim:id,name')->findOrFail($penerima);

        if (! $baris->dibaca_pada) {
            $baris->update(['dibaca_pada' => now()]);
        }

        return view('orangtua.pesan.show', ['baris' => $baris]);
    }
}
