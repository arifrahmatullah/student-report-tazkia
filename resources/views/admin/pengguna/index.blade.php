@php
    $labelPeran = ['admin' => 'Admin', 'akademik' => 'Akademik', 'orangtua' => 'Orang tua'];
    $tab = ['staf' => 'Staf', 'orangtua' => 'Orang tua', 'semua' => 'Semua'];
@endphp
<x-app-layout title="Pengguna">
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-tazkia-900">Pengguna</h1>
                <p class="mt-1.5 text-ink-soft">Kelola akun staf. Staf masuk dengan Google memakai email yang Anda daftarkan di sini.</p>
            </div>
            <a href="{{ route('admin.pengguna.create') }}" class="btn-primary shadow-lg shadow-tazkia-700/25">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                Tambah staf
            </a>
        </div>
    </x-slot>

    <div class="space-y-4">
        <x-flash />

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="inline-flex rounded-xl bg-white p-1 shadow-card">
                @foreach ($tab as $k => $nama)
                    <a href="{{ route('admin.pengguna.index', ['peran' => $k, 'q' => $q ?: null]) }}"
                       class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $peran === $k ? 'bg-tazkia-700 text-white' : 'text-ink-soft hover:text-tazkia-700' }}">{{ $nama }}</a>
                @endforeach
            </div>
            <form method="GET" class="flex gap-2">
                <input type="hidden" name="peran" value="{{ $peran }}">
                <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau email" class="field w-full sm:w-64">
                <button class="btn-secondary">Cari</button>
            </form>
        </div>

        <div class="card overflow-hidden">
            @forelse ($daftar as $u)
                @php
                    $r = $u->roles->pluck('name');
                    $namaPeran = $r->contains('admin') ? 'admin' : ($r->contains('akademik') ? 'akademik' : 'orangtua');
                @endphp
                <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-4 last:border-0 {{ $u->is_active ? '' : 'opacity-60' }}">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="truncate font-bold text-tazkia-900">{{ $u->name }}</span>
                            <span class="rounded-full bg-tazkia-50 px-2.5 py-0.5 text-xs font-semibold text-tazkia-700">{{ $labelPeran[$namaPeran] }}</span>
                            @unless ($u->is_active)
                                <span class="rounded-full bg-canvas px-2.5 py-0.5 text-xs font-semibold text-ink-soft">Nonaktif</span>
                            @endunless
                        </div>
                        <div class="truncate text-sm text-ink-soft">{{ $u->email }}</div>
                        <div class="mt-0.5 text-xs text-ink-muted">{{ $u->google_id ? 'Sudah masuk dengan Google' : ($namaPeran === 'orangtua' ? 'Masuk dengan email dan kata sandi' : 'Belum pernah masuk dengan Google') }}</div>
                    </div>
                    <a href="{{ route('admin.pengguna.edit', $u) }}" class="shrink-0 text-sm font-semibold text-tazkia-700 hover:text-accent-600">Ubah</a>
                </div>
            @empty
                <div class="px-6 py-14 text-center">
                    <h2 class="text-lg font-bold text-tazkia-900">Tidak ada pengguna</h2>
                    <p class="mt-1.5 text-ink-soft">Coba ubah kata kunci atau filter.</p>
                </div>
            @endforelse
        </div>

        {{ $daftar->links() }}
    </div>
</x-app-layout>
