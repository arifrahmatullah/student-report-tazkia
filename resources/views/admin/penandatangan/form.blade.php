@php
    $ada = $p->exists;
    $in = fn ($k) => $errors->has($k) ? 'field-error' : '';
@endphp
<x-app-layout :title="$ada ? 'Ubah penandatangan' : 'Tambah penandatangan'">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.penandatangan.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft transition hover:text-tazkia-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Kembali
        </a>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-tazkia-900">{{ $ada ? 'Ubah penandatangan' : 'Tambah penandatangan' }}</h1>

        <form method="POST" enctype="multipart/form-data" class="card mt-6 space-y-5 p-6 sm:p-7" x-data="{ busy: false }" @submit="busy = true"
              action="{{ $ada ? route('admin.penandatangan.update', $p) : route('admin.penandatangan.store') }}">
            @csrf
            @if ($ada) @method('PUT') @endif

            <div>
                <x-input-label for="nama" value="Nama lengkap dan gelar" />
                <x-text-input id="nama" name="nama" class="mt-1.5 {{ $in('nama') }}" :value="old('nama', $p->nama)" required autofocus />
                <x-input-error :messages="$errors->get('nama')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="jabatan" value="Jabatan" />
                <x-text-input id="jabatan" name="jabatan" class="mt-1.5 {{ $in('jabatan') }}" :value="old('jabatan', $p->jabatan)" required placeholder="Contoh: Wakil Rektor I Bidang Akademik" />
                <x-input-error :messages="$errors->get('jabatan')" class="mt-2" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="nidn" value="NIDN (opsional)" />
                    <x-text-input id="nidn" name="nidn" class="mt-1.5" :value="old('nidn', $p->nidn)" />
                </div>
                <div>
                    <x-input-label for="nik" value="NIK (opsional)" />
                    <x-text-input id="nik" name="nik" class="mt-1.5" :value="old('nik', $p->nik)" />
                </div>
            </div>

            <div>
                <x-input-label for="ttd" value="Gambar tanda tangan (opsional)" />
                @if ($ada && $p->ttd_path)
                    <div class="mt-2 flex items-center gap-4 rounded-xl border border-line bg-canvas/60 p-3">
                        <img src="{{ route('admin.penandatangan.ttd', $p) }}" alt="Tanda tangan saat ini" class="max-h-14 max-w-[8rem] object-contain">
                        <label class="flex items-center gap-2 text-sm text-ink-soft"><input type="checkbox" name="hapus_ttd" value="1" class="rounded border-line text-tazkia-700 focus:ring-tazkia-700/20"> Hapus gambar ini</label>
                    </div>
                @endif
                <input id="ttd" name="ttd" type="file" accept="image/png,image/jpeg"
                       class="mt-2 block w-full text-sm text-ink-soft file:mr-4 file:rounded-lg file:border-0 file:bg-tazkia-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-tazkia-700 hover:file:bg-tazkia-100">
                <p class="mt-1.5 text-xs text-ink-muted">PNG atau JPG, maksimal 1 MB. PNG berlatar transparan hasilnya paling rapi. Berkas disimpan privat dan hanya dipakai pada surat.</p>
                <x-input-error :messages="$errors->get('ttd')" class="mt-2" />
            </div>

            <label class="flex cursor-pointer items-center gap-2.5 select-none">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $p->is_active)) class="h-4 w-4 rounded border-line text-tazkia-700 focus:ring-2 focus:ring-tazkia-700/20 focus:ring-offset-0">
                <span class="text-sm text-ink-soft">Aktif (muncul sebagai pilihan saat membuat surat)</span>
            </label>

            <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.penandatangan.index') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary shadow-lg shadow-tazkia-700/25" x-bind:disabled="busy">
                    <span x-show="!busy">Simpan</span><span x-show="busy" x-cloak>Menyimpan…</span>
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
