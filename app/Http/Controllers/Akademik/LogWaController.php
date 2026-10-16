<?php

namespace App\Http\Controllers\Akademik;

use App\Http\Controllers\Controller;
use App\Models\Pesan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Log seluruh percobaan pengiriman WhatsApp: pengingat KRS, pengumuman, dan tes kirim. */
class LogWaController extends Controller
{
    public const JENIS = [
        'reminder_krs' => 'Pengingat KRS',
        'broadcast' => 'Pengumuman',
        'tes_reminder_krs' => 'Tes kirim',
    ];

    public function index(Request $request): View
    {
        $f = $request->validate([
            'jenis' => ['nullable', 'in:'.implode(',', array_keys(self::JENIS))],
            'status' => ['nullable', 'in:terkirim,gagal,tanpa_nomor,nomor_tidak_valid,duplikat,diblokir'],
            'q' => ['nullable', 'string', 'max:100'],
            'pesan' => ['nullable', 'uuid'],
        ]);

        $pesanId = ! empty($f['pesan']) ? Pesan::where('uuid', $f['pesan'])->value('id') : null;

        $dasar = DB::table('whatsapp_logs')
            ->when($f['jenis'] ?? null, fn ($q, $v) => $q->where('jenis', $v))
            ->when(! empty($f['pesan']), fn ($q) => $q->where('pesan_id', $pesanId ?? 0))
            ->when($f['q'] ?? null, function ($q, $v) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $v).'%';
                $q->where(fn ($w) => $w->where('nim', 'like', $like)->orWhere('nama_mahasiswa', 'like', $like)
                    ->orWhere('nama_penerima', 'like', $like)->orWhere('no_hp', 'like', $like));
            });

        // Ringkasan mengikuti penyaring jenis/pencarian, tetapi bukan penyaring status (supaya semua status tetap terlihat).
        $ringkas = (clone $dasar)->selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status');

        $baris = (clone $dasar)->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('id')->paginate(30)->withQueryString();

        return view('akademik.log-wa.index', [
            'baris' => $baris,
            'uuidPesan' => Pesan::whereIn('id', $baris->pluck('pesan_id')->filter()->unique())->pluck('uuid', 'id'),
            'ringkas' => $ringkas,
            'f' => $f,
            'jenis' => self::JENIS,
        ]);
    }
}
