<x-app-layout title="Penandatangan">
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-tazkia-900">Penandatangan</h1>
                <p class="mt-1.5 text-ink-soft">Pejabat yang menandatangani surat laporan, lengkap dengan gambar tanda tangannya.</p>
            </div>
            <a href="{{ route('admin.penandatangan.create') }}" class="btn-primary shadow-lg shadow-tazkia-700/25">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                Tambah penandatangan
            </a>
        </div>
    </x-slot>

    <div class="space-y-4">
        <x-flash />

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($daftar as $p)
                <div class="card flex flex-col p-6 {{ $p->is_active ? '' : 'opacity-70' }}">
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-tazkia-600 to-tazkia-800 text-lg font-extrabold text-white">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($p->nama, 0, 1)) }}</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $p->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-canvas text-ink-soft' }}">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                    <h2 class="mt-4 font-bold leading-snug text-tazkia-900">{{ $p->nama }}</h2>
                    <p class="text-sm text-ink-soft">{{ $p->jabatan }}</p>
                    <p class="mt-1 text-xs text-ink-muted">@if ($p->nidn) NIDN {{ $p->nidn }} @endif @if ($p->nidn && $p->nik) · @endif @if ($p->nik) NIK {{ $p->nik }} @endif</p>

                    <div class="mt-4 flex h-20 items-center justify-center rounded-xl border border-dashed border-line bg-canvas/60">
                        @if ($p->ttd_path)
                            <img src="{{ route('admin.penandatangan.ttd', $p) }}" alt="Tanda tangan {{ $p->nama }}" class="max-h-16 max-w-[70%] object-contain">
                        @else
                            <span class="text-xs text-ink-muted">Belum ada gambar tanda tangan</span>
                        @endif
                    </div>

                    <div class="mt-5 flex items-center justify-between border-t border-line pt-4 text-sm">
                        <a href="{{ route('admin.penandatangan.edit', $p) }}" class="font-semibold text-tazkia-700 hover:text-accent-600">Ubah</a>
                        <form method="POST" action="{{ route('admin.penandatangan.destroy', $p) }}" onsubmit="return confirm('Hapus penandatangan ini?')">
                            @csrf @method('DELETE')
                            <button class="text-ink-muted transition hover:text-red-600">Hapus</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="card col-span-full px-6 py-16 text-center">
                    <h2 class="text-lg font-bold text-tazkia-900">Belum ada penandatangan</h2>
                    <p class="mx-auto mt-1.5 max-w-sm text-ink-soft">Tambahkan pejabat yang akan menandatangani surat laporan.</p>
                    <a href="{{ route('admin.penandatangan.create') }}" class="btn-primary mt-6">Tambah penandatangan</a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
