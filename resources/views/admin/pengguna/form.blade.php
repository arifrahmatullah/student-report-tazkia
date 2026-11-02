@php
    $ada = $u->exists;
    $orangtua = $ada && $peranSaatIni === 'orangtua';
    $diriSendiri = $diriSendiri ?? false;
    $in = fn ($k) => $errors->has($k) ? 'field-error' : '';
@endphp
<x-app-layout :title="$ada ? 'Ubah pengguna' : 'Tambah staf'">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.pengguna.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft transition hover:text-tazkia-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Kembali
        </a>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-tazkia-900">{{ $ada ? 'Ubah pengguna' : 'Tambah staf' }}</h1>

        <form method="POST" class="card mt-6 space-y-5 p-6 sm:p-7" x-data="{ busy: false }" @submit="busy = true"
              action="{{ $ada ? route('admin.pengguna.update', $u) : route('admin.pengguna.store') }}">
            @csrf
            @if ($ada) @method('PUT') @endif

            @if ($orangtua)
                <div>
                    <div class="text-xs font-medium text-ink-muted">Nama</div>
                    <div class="font-semibold text-ink">{{ $u->name }}</div>
                </div>
            @else
                <div>
                    <x-input-label for="name" value="Nama lengkap" />
                    <x-text-input id="name" name="name" class="mt-1.5 {{ $in('name') }}" :value="old('name', $u->name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
            @endif

            <div>
                @if ($ada)
                    <div class="text-xs font-medium text-ink-muted">Email</div>
                    <div class="font-semibold text-ink">{{ $u->email }}</div>
                @else
                    <x-input-label for="email" value="Email Google" />
                    <x-text-input id="email" name="email" type="email" class="mt-1.5 {{ $in('email') }}" :value="old('email')" required placeholder="nama@gmail.com atau nama@tazkia.ac.id" />
                    <p class="mt-1.5 text-xs text-ink-muted">Harus email yang dipakai untuk akun Google orangnya. Staf masuk lewat tombol "Masuk dengan Google"; tidak ada kata sandi.</p>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                @endif
            </div>

            @unless ($orangtua)
                <div>
                    <x-input-label for="peran" value="Peran" />
                    @if ($diriSendiri)
                        <input type="hidden" name="peran" value="{{ $peranSaatIni }}">
                        <div class="mt-1.5 font-semibold text-ink">{{ $peranSaatIni === 'admin' ? 'Admin' : 'Akademik' }} <span class="text-xs font-normal text-ink-muted">(akun Anda sendiri)</span></div>
                    @else
                        <select id="peran" name="peran" class="field mt-1.5 {{ $in('peran') }}">
                            <option value="akademik" @selected(old('peran', $peranSaatIni) === 'akademik')>Akademik — kirim pesan, pengingat KRS, log WhatsApp</option>
                            <option value="admin" @selected(old('peran', $peranSaatIni) === 'admin')>Admin — semua fitur akademik, ditambah surat laporan, penandatangan, dan pengguna</option>
                        </select>
                    @endif
                    <x-input-error :messages="$errors->get('peran')" class="mt-2" />
                </div>
            @endunless

            @if ($ada)
                @if ($diriSendiri)
                    <input type="hidden" name="is_active" value="1">
                @else
                    <label class="flex cursor-pointer items-center gap-2.5 select-none">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $u->is_active)) class="h-4 w-4 rounded border-line text-tazkia-700 focus:ring-2 focus:ring-tazkia-700/20 focus:ring-offset-0">
                        <span class="text-sm text-ink-soft">Aktif (bila dimatikan, akun langsung keluar dari semua perangkat dan tidak bisa masuk)</span>
                    </label>
                @endif
                @if ($orangtua)
                    <p class="rounded-xl bg-canvas px-4 py-3 text-xs text-ink-muted">Akun orang tua mendaftar sendiri, jadi perannya tidak bisa diubah dari sini.</p>
                @endif
            @endif

            <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.pengguna.index') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary shadow-lg shadow-tazkia-700/25" x-bind:disabled="busy">
                    {{ $ada ? 'Simpan perubahan' : 'Buat akun' }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
