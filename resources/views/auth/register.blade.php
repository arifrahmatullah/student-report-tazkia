<x-guest-layout>
    <x-slot name="judulPanel">
        Satu akun untuk <span class="text-accent-400">mendampingi studi anak Anda.</span>
    </x-slot>

    <x-slot name="panelKonten">
        <ol class="space-y-3">
            @foreach ([
                ['Buat akun', 'Isi data diri dan nomor WhatsApp Anda.'],
                ['Tautkan anak', 'Masukkan NIM dan tanggal lahir anak untuk verifikasi.'],
                ['Pantau & terima kabar', 'Lihat perkembangan studi dan pesan dari akademik.'],
            ] as $i => [$judul, $isi])
                <li class="flex items-start gap-4 rounded-2xl bg-white/[0.07] p-4 ring-1 ring-white/10 backdrop-blur-sm {{ $i === 0 ? 'ring-accent-400/60 bg-white/[0.12]' : '' }}">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-extrabold {{ $i === 0 ? 'bg-accent-500 text-white' : 'bg-white/15 text-white' }}">{{ $i + 1 }}</span>
                    <div>
                        <div class="font-bold text-white">{{ $judul }}</div>
                        <div class="mt-0.5 text-sm leading-relaxed text-white/65">{{ $isi }}</div>
                    </div>
                </li>
            @endforeach
        </ol>
    </x-slot>

    <div>
        <span class="inline-flex items-center gap-2 rounded-full bg-accent-50 px-3 py-1 text-xs font-semibold text-accent-700 ring-1 ring-accent-200/70">
            <span class="h-1.5 w-1.5 rounded-full bg-accent-500"></span> Langkah 1 dari 2 · Buat akun
        </span>
        <h2 class="mt-4 text-[2rem] font-extrabold tracking-tight text-tazkia-900">Daftar orang tua</h2>
        <p class="mt-2 text-[15px] text-ink-soft">Setelah akun dibuat, Anda tinggal menautkan anak dengan NIM dan tanggal lahirnya.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5" x-data="{ show: false, busy: false }" @submit="busy = true">
        @csrf

        <div>
            <x-input-label for="name" value="Nama lengkap" />
            <div class="relative mt-1.5">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.12a7.5 7.5 0 0115 0A17.93 17.93 0 0112 21.75c-2.68 0-5.22-.59-7.5-1.63z"/></svg>
                <x-text-input id="name" class="pl-12 {{ $errors->has('name') ? 'field-error' : '' }}" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Nama Anda" />
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <div class="relative mt-1.5">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.24a2.25 2.25 0 01-1.07 1.92l-7.5 4.62a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.92V6.75"/></svg>
                <x-text-input id="email" class="pl-12 {{ $errors->has('email') ? 'field-error' : '' }}" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="nama@email.com" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="phone" value="Nomor WhatsApp" />
            <div class="relative mt-1.5">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>
                <x-text-input id="phone" class="pl-12 {{ $errors->has('phone') ? 'field-error' : '' }}" type="tel" inputmode="tel" name="phone" :value="old('phone')" required autocomplete="tel" placeholder="081234567890" />
            </div>
            <p class="mt-1.5 text-xs text-ink-muted">Dipakai untuk menerima pemberitahuan dari bagian akademik.</p>
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            @foreach ([['password', 'Kata sandi', 'Min. 8 karakter'], ['password_confirmation', 'Ulangi kata sandi', 'Ketik ulang']] as [$id, $label, $ph])
                <div>
                    <x-input-label :for="$id" :value="$label" />
                    <div class="relative mt-1.5">
                        <x-text-input :id="$id" class="pr-12 {{ $errors->has($id) ? 'field-error' : '' }}" x-bind:type="show ? 'text' : 'password'" type="password" :name="$id" required autocomplete="new-password" :placeholder="$ph" />
                        @if ($id === 'password')
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-ink-muted transition hover:text-tazkia-700"
                                x-bind:aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                                <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 010-.64C3.42 7.51 7.36 4.5 12 4.5s8.58 3.01 9.96 7.18a1 1 0 010 .64C20.58 16.49 16.64 19.5 12 19.5s-8.58-3.01-9.96-7.18z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.48 10.48 0 001.93 12c1.29 4.34 5.31 7.5 10.07 7.5 1.99 0 3.84-.55 5.42-1.5M6.23 6.23A10.45 10.45 0 0112 4.5c4.76 0 8.77 3.16 10.07 7.5a10.5 10.5 0 01-4.29 5.77M6.23 6.23L3 3m3.23 3.23l3.65 3.65m7.89 7.89L21 21m-3.23-3.23l-3.65-3.65m0 0a3 3 0 10-4.24-4.24m4.24 4.24L9.88 9.88"/></svg>
                            </button>
                        @endif
                    </div>
                    <x-input-error :messages="$errors->get($id)" class="mt-2" />
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn-primary w-full py-3.5 text-[15px] shadow-lg shadow-tazkia-700/25" x-bind:disabled="busy">
            <span x-show="!busy">Buat akun</span>
            <span x-show="busy" x-cloak>Memproses…</span>
        </button>

        <p class="text-center text-xs leading-relaxed text-ink-muted">
            Pendaftaran ini khusus orang tua atau wali mahasiswa. Akun staf dibuat oleh admin.
        </p>
    </form>

    <div class="mt-6 border-t border-line pt-6 text-center text-sm text-ink-soft">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-semibold text-tazkia-700 transition hover:text-accent-600">Masuk</a>
    </div>
</x-guest-layout>
