<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penandatangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PenandatanganController extends Controller
{
    public function index(): View
    {
        return view('admin.penandatangan.index', [
            'daftar' => Penandatangan::orderByDesc('is_active')->orderBy('nama')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.penandatangan.form', ['p' => new Penandatangan(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $p = Penandatangan::create($this->data($request));

        return redirect()->route('admin.penandatangan.index')->with('status', "Penandatangan {$p->nama} ditambahkan.");
    }

    public function edit(Penandatangan $penandatangan): View
    {
        return view('admin.penandatangan.form', ['p' => $penandatangan]);
    }

    public function update(Request $request, Penandatangan $penandatangan): RedirectResponse
    {
        $penandatangan->update($this->data($request, $penandatangan));

        return redirect()->route('admin.penandatangan.index')->with('status', 'Perubahan disimpan.');
    }

    public function destroy(Penandatangan $penandatangan): RedirectResponse
    {
        // Surat yang sudah dibuat menyimpan salinan angkanya; penandatangan_id menjadi null (nullOnDelete).
        if ($penandatangan->ttd_path) {
            Storage::disk('local')->delete($penandatangan->ttd_path);
        }
        $penandatangan->delete();

        return redirect()->route('admin.penandatangan.index')->with('status', 'Penandatangan dihapus.');
    }

    /** Pratinjau tanda tangan; berkas privat, hanya untuk staf. */
    public function ttd(Penandatangan $penandatangan): StreamedResponse
    {
        abort_unless($penandatangan->ttd_path && Storage::disk('local')->exists($penandatangan->ttd_path), 404);

        return Storage::disk('local')->response($penandatangan->ttd_path);
    }

    private function data(Request $request, ?Penandatangan $ada = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'nidn' => ['nullable', 'string', 'max:50'],
            'nik' => ['nullable', 'string', 'max:50'],
            'ttd' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'hapus_ttd' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'ttd.max' => 'Ukuran gambar tanda tangan maksimal 1 MB.',
            'ttd.image' => 'Berkas tanda tangan harus berupa gambar PNG atau JPG.',
            'ttd.mimes' => 'Berkas tanda tangan harus berupa gambar PNG atau JPG.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['prodi_id'] = null; // koordinator prodi diambil dari SIAKAD; kolom lama dikosongkan
        unset($data['ttd'], $data['hapus_ttd']);

        if ($request->hasFile('ttd')) {
            if ($ada?->ttd_path) {
                Storage::disk('local')->delete($ada->ttd_path);
            }
            $data['ttd_path'] = $request->file('ttd')->store('ttd', 'local');
        } elseif ($ada?->ttd_path && $request->boolean('hapus_ttd')) {
            Storage::disk('local')->delete($ada->ttd_path);
            $data['ttd_path'] = null;
        }

        return $data;
    }
}
