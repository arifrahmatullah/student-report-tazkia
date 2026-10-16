@php
    $user = Auth::user();
    $menu = config('menu.'.($user->getRoleNames()->first() ?? 'none'), []);
    $pesanBaru = $user->hasRole('orangtua') ? $user->pesanDiterima()->whereNull('dibaca_pada')->count() : 0;
    $inisial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1));
    $peran = ['orangtua' => 'Orang tua', 'akademik' => 'Akademik', 'admin' => 'Admin'][$user->getRoleNames()->first()] ?? '';
@endphp

<nav x-data="{ open: false, menu: false }" class="sticky top-0 z-40 border-b border-line bg-white/85 backdrop-blur-lg">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <div class="flex items-center gap-8">
            <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-3">
                <img src="{{ asset('img/logo-tazkia.png') }}" alt="Universitas Tazkia" class="h-10 w-auto">
                <span class="hidden text-[15px] font-extrabold leading-tight tracking-tight text-tazkia-900 lg:block">Universitas Tazkia</span>
            </a>

            <div class="hidden items-center gap-1 md:flex">
                @foreach ($menu as $item)
                    @php $aktif = request()->routeIs($item['aktif']); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="relative rounded-lg px-3.5 py-2 text-sm font-semibold transition {{ $aktif ? 'bg-tazkia-50 text-tazkia-700' : 'text-ink-soft hover:bg-canvas hover:text-tazkia-700' }}">
                        {{ $item['label'] }}
                        @if ($item['route'] === 'orangtua.pesan.index' && $pesanBaru > 0)
                            <span class="ml-1.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-accent-500 px-1.5 text-[11px] font-bold text-white">{{ $pesanBaru }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-2">
            {{-- Menu akun --}}
            <div class="relative" @click.outside="menu = false" @keydown.escape.window="menu = false">
                <button @click="menu = !menu" class="flex items-center gap-2.5 rounded-xl py-1.5 pl-1.5 pr-3 transition hover:bg-canvas" aria-haspopup="true" x-bind:aria-expanded="menu">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-tazkia-700 text-sm font-bold text-white">{{ $inisial }}</span>
                    <span class="hidden text-left leading-tight sm:block">
                        <span class="block max-w-[10rem] truncate text-sm font-semibold text-ink">{{ $user->name }}</span>
                        <span class="block text-xs text-ink-muted">{{ $peran }}</span>
                    </span>
                    <svg class="hidden h-4 w-4 text-ink-muted sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </button>

                <div x-show="menu" x-cloak x-transition.origin.top.right
                     class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-line bg-white py-1.5 shadow-card">
                    <div class="border-b border-line px-4 pb-2.5 pt-1.5 sm:hidden">
                        <div class="truncate text-sm font-semibold text-ink">{{ $user->name }}</div>
                        <div class="truncate text-xs text-ink-muted">{{ $user->email }}</div>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink-soft transition hover:bg-canvas hover:text-tazkia-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.12a7.5 7.5 0 0115 0A17.93 17.93 0 0112 21.75c-2.68 0-5.22-.59-7.5-1.63z"/></svg>
                        Profil saya
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-ink-soft transition hover:bg-red-50 hover:text-red-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>

            <button @click="open = !open" class="flex h-10 w-10 items-center justify-center rounded-lg text-ink-soft hover:bg-canvas md:hidden" aria-label="Menu">
                <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </div>

    <div x-show="open" x-cloak x-transition class="border-t border-line bg-white px-4 pb-4 pt-2 md:hidden">
        @foreach ($menu as $item)
            @php $aktif = request()->routeIs($item['aktif']); @endphp
            <a href="{{ route($item['route']) }}" class="flex items-center justify-between rounded-lg px-3 py-3 text-sm font-semibold {{ $aktif ? 'bg-tazkia-50 text-tazkia-700' : 'text-ink-soft' }}">
                {{ $item['label'] }}
                @if ($item['route'] === 'orangtua.pesan.index' && $pesanBaru > 0)
                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-accent-500 px-1.5 text-[11px] font-bold text-white">{{ $pesanBaru }}</span>
                @endif
            </a>
        @endforeach
    </div>
</nav>
