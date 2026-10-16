<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#194189">

        <title>{{ $title ?? 'Masuk' }} · {{ config('app.name') }}</title>
        <link rel="icon" href="{{ asset('img/logo-tazkia.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans">
        <div class="min-h-screen lg:grid lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">

            {{-- Panel merek: hanya tampil di layar lebar --}}
            <aside class="relative hidden overflow-hidden lg:flex lg:flex-col lg:justify-between lg:p-12 xl:p-16">
                <div class="absolute inset-0 bg-gradient-to-br from-tazkia-700 via-tazkia-800 to-tazkia-950"></div>
                {{-- Kisi titik halus --}}
                <div class="absolute inset-0 opacity-[0.18]"
                     style="background-image: radial-gradient(circle, #fff 1px, transparent 1.2px); background-size: 28px 28px; mask-image: linear-gradient(to bottom right, #000, transparent 70%); -webkit-mask-image: linear-gradient(to bottom right, #000, transparent 70%);"></div>
                {{-- Cahaya oranye lembut --}}
                <div class="absolute -bottom-40 -right-24 h-[30rem] w-[30rem] rounded-full bg-accent-500/25 blur-[110px]"></div>
                <div class="absolute -left-32 -top-32 h-[26rem] w-[26rem] rounded-full bg-tazkia-400/20 blur-[100px]"></div>

                <a href="{{ url('/') }}" class="relative inline-flex w-fit items-center gap-3 rounded-2xl bg-white px-4 py-2.5 shadow-lg shadow-black/10">
                    <img src="{{ asset('img/logo-tazkia.png') }}" alt="Universitas Tazkia" class="h-9 w-auto">
                    <span class="hidden text-sm font-bold leading-tight text-tazkia-800 xl:block">Portal Orang Tua<br><span class="font-medium text-ink-muted">Universitas Tazkia</span></span>
                </a>

                <div class="relative">
                    <h1 class="max-w-lg text-[2.5rem] font-extrabold leading-[1.1] tracking-tight text-white xl:text-5xl">
                        @isset($judulPanel)
                            {{ $judulPanel }}
                        @else
                            Kabar akademik anak Anda, <span class="text-accent-400">selalu dalam genggaman.</span>
                        @endisset
                    </h1>

                    @isset($panelKonten)
                        <div class="relative mt-10 max-w-md">{{ $panelKonten }}</div>
                    @else
                    <div class="relative mt-10 max-w-md">
                        <x-dashboard-preview compact class="rotate-[-2deg]" />
                        <div class="absolute -bottom-9 -right-6 flex items-center gap-2.5 rounded-xl bg-white px-3.5 py-2.5 shadow-xl ring-1 ring-black/5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-accent-100 text-accent-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.86 17.08a23 23 0 005.45-1.08A8.97 8.97 0 0118 9.75V9A6 6 0 006 9v.75a8.97 8.97 0 01-2.31 6.25 23 23 0 005.45 1.08m5.72 0a24 24 0 01-5.72 0m5.72 0a3 3 0 11-5.72 0"/></svg>
                            </span>
                            <div class="text-xs leading-tight"><div class="font-bold text-ink">Pesan baru</div><div class="text-ink-muted">dari Bagian Akademik</div></div>
                        </div>
                    </div>
                    @endisset
                </div>

                <p class="relative text-xs text-white/45">&copy; {{ now()->year }} Universitas Tazkia</p>
            </aside>

            {{-- Panel formulir --}}
            <main class="relative flex min-h-screen flex-col px-6 py-8 sm:px-10">
                <div class="flex items-center justify-between">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 lg:hidden"><img src="{{ asset('img/logo-tazkia.png') }}" alt="Universitas Tazkia" class="h-12 w-auto"><span class="text-base font-extrabold leading-tight tracking-tight text-tazkia-900">Universitas Tazkia</span></a>
                    <a href="{{ url('/') }}" class="ml-auto inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft transition hover:text-tazkia-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                        Beranda
                    </a>
                </div>

                <div class="flex flex-1 items-center justify-center py-10">
                    <div class="w-full max-w-[25rem]">
                        {{ $slot }}
                    </div>
                </div>

                <p class="text-center text-xs text-ink-muted lg:hidden">&copy; {{ now()->year }} Universitas Tazkia</p>
            </main>
        </div>
    </body>
</html>
