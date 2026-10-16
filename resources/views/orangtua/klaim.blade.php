<x-app-layout title="Tambah anak">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('orangtua.beranda') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft transition hover:text-tazkia-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Kembali
        </a>

        <div class="card mt-4 overflow-hidden">
            <div class="relative bg-gradient-to-br from-tazkia-700 to-tazkia-900 px-7 py-8 text-white">
                <div class="absolute -right-10 -top-12 h-40 w-40 rounded-full bg-accent-500/30 blur-[50px]"></div>
                <div class="relative">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.04A11.96 11.96 0 013.6 6 12 12 0 003 9.75c0 5.59 3.82 10.29 9 11.62 5.18-1.33 9-6.03 9-11.62 0-1.31-.21-2.57-.6-3.75h-.15c-3.2 0-6.1-1.25-8.25-3.29z"/></svg>
                    </span>
                    <h1 class="mt-4 text-2xl font-extrabold tracking-tight">Tautkan anak Anda</h1>
                    <p class="mt-1.5 text-white/75">Verifikasi singkat untuk memastikan hanya orang tua yang berhak yang dapat melihat data.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('orangtua.klaim.store') }}" class="space-y-5 p-7" x-data="{ busy: false }" @submit="busy = true">
                @csrf

                <div>
                    <x-input-label for="nim" value="NIM mahasiswa" />
                    <x-text-input id="nim" name="nim" type="text" inputmode="numeric" pattern="[0-9]*" class="mt-1.5 {{ $errors->has('nim') ? 'field-error' : '' }}"
                        :value="old('nim')" required autofocus placeholder="Contoh: 2510101078" />
                    <x-input-error :messages="$errors->get('nim')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="tanggal_lahir" value="Tanggal lahir mahasiswa" />
                    <x-text-input id="tanggal_lahir" name="tanggal_lahir" type="date" class="mt-1.5 {{ $errors->has('tanggal_lahir') ? 'field-error' : '' }}"
                        :value="old('tanggal_lahir')" required max="{{ now()->subDay()->toDateString() }}" />
                    <x-input-error :messages="$errors->get('tanggal_lahir')" class="mt-2" />
                </div>

                <div class="flex gap-3 rounded-xl bg-tazkia-50 p-4 text-sm leading-relaxed text-tazkia-900">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-tazkia-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    <p>Tanggal lahir hanya dipakai untuk verifikasi dan <strong>tidak disimpan</strong>. Data dicocokkan langsung dengan SIAKAD.</p>
                </div>

                <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:justify-end">
                    <a href="{{ route('orangtua.beranda') }}" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary shadow-lg shadow-tazkia-700/25" x-bind:disabled="busy">
                        <span x-show="!busy">Verifikasi &amp; tautkan</span>
                        <span x-show="busy" x-cloak>Memverifikasi…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
