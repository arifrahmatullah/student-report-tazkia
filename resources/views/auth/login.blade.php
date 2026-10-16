<x-guest-layout>
    <div>
        <span class="inline-flex items-center gap-2 rounded-full bg-accent-50 px-3 py-1 text-xs font-semibold text-accent-700 ring-1 ring-accent-200/70">
            <span class="h-1.5 w-1.5 rounded-full bg-accent-500"></span> Portal Orang Tua &amp; Staf
        </span>
        <h2 class="mt-4 text-[2rem] font-extrabold tracking-tight text-tazkia-900">Selamat datang kembali</h2>
        <p class="mt-2 text-[15px] text-ink-soft">Masuk untuk melanjutkan ke dashboard Anda.</p>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-tazkia-100 bg-tazkia-50 px-4 py-3 text-sm text-tazkia-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" x-data="{ show: false, busy: false }" @submit="busy = true">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <div class="relative mt-1.5">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.24a2.25 2.25 0 01-1.07 1.92l-7.5 4.62a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.92V6.75"/></svg>
                <x-text-input id="email" class="pl-12 {{ $errors->has('email') ? 'field-error' : '' }}" type="email" name="email"
                    :value="old('email')" required autofocus autocomplete="username" placeholder="nama@email.com" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Kata sandi" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-tazkia-700 hover:text-accent-600 transition">Lupa kata sandi?</a>
                @endif
            </div>
            <div class="relative mt-1.5">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                <x-text-input id="password" class="pl-12 pr-12" x-bind:type="show ? 'text' : 'password'" type="password" name="password"
                    required autocomplete="current-password" placeholder="Masukkan kata sandi" />
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-ink-muted hover:text-tazkia-700 transition"
                    x-bind:aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 010-.64C3.42 7.51 7.36 4.5 12 4.5s8.58 3.01 9.96 7.18a1 1 0 010 .64C20.58 16.49 16.64 19.5 12 19.5s-8.58-3.01-9.96-7.18z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.48 10.48 0 001.93 12c1.29 4.34 5.31 7.5 10.07 7.5 1.99 0 3.84-.55 5.42-1.5M6.23 6.23A10.45 10.45 0 0112 4.5c4.76 0 8.77 3.16 10.07 7.5a10.5 10.5 0 01-4.29 5.77M6.23 6.23L3 3m3.23 3.23l3.65 3.65m7.89 7.89L21 21m-3.23-3.23l-3.65-3.65m0 0a3 3 0 10-4.24-4.24m4.24 4.24L9.88 9.88"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex cursor-pointer items-center gap-2.5 select-none">
            <input id="remember_me" type="checkbox" name="remember"
                class="h-4 w-4 rounded border-line text-tazkia-700 focus:ring-2 focus:ring-tazkia-700/20 focus:ring-offset-0">
            <span class="text-sm text-ink-soft">Ingat saya di perangkat ini</span>
        </label>

        <button type="submit" class="btn-primary w-full py-3.5 text-[15px] shadow-lg shadow-tazkia-700/25" x-bind:disabled="busy">
            <span x-show="!busy">Masuk</span>
            <span x-show="busy" x-cloak>Memproses…</span>
        </button>
    </form>

    <div class="mt-8 border-t border-line pt-6 text-center text-sm text-ink-soft">
        Orang tua mahasiswa dan belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-tazkia-700 hover:text-accent-600 transition">Daftar di sini</a>
    </div>
</x-guest-layout>
