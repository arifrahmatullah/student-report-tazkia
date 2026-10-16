<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#194189">

        <title>{{ $title ?? config('app.name') }} · Universitas Tazkia</title>
        <link rel="icon" href="{{ asset('img/logo-tazkia.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col font-sans">
        @include('layouts.navigation')

        @isset($header)
            <header class="mx-auto w-full max-w-6xl px-4 pb-1 pt-8 sm:px-6">
                {{ $header }}
            </header>
        @endisset

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6">
            {{ $slot }}
        </main>

        <footer class="border-t border-line bg-white">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-5 text-xs text-ink-muted sm:flex-row sm:px-6">
                <span>&copy; {{ now()->year }} Universitas Tazkia</span>
                <span>Data akademik bersumber dari SIAKAD Tazkia</span>
            </div>
        </footer>
    </body>
</html>
