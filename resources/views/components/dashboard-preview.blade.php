{{-- Ilustrasi dashboard orang tua (data contoh, bukan data nyata). --}}
@props(['compact' => false])

<div {{ $attributes->merge(['class' => 'relative']) }} aria-hidden="true">
    <div class="rounded-2xl bg-white p-5 shadow-[0_24px_60px_-20px_rgba(10,28,59,0.45)] ring-1 ring-black/5 {{ $compact ? 'sm:p-5' : 'sm:p-6' }}">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-tazkia-100 text-sm font-bold text-tazkia-700">A</span>
                <div>
                    <div class="text-sm font-semibold text-ink">Ananda Pratama</div>
                    <div class="text-xs text-ink-muted">Manajemen Bisnis Syariah · Smt 5</div>
                </div>
            </div>
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">KRS disetujui</span>
        </div>

        <div class="mt-5 grid grid-cols-3 gap-3">
            @foreach ([['IPK', '3,72', 'text-tazkia-700'], ['Total SKS', '85', 'text-ink'], ['Hadir', '96%', 'text-accent-600']] as [$label, $nilai, $warna])
                <div class="rounded-xl bg-canvas px-3 py-3">
                    <div class="text-[11px] text-ink-muted">{{ $label }}</div>
                    <div class="mt-0.5 text-xl font-extrabold {{ $warna }}">{{ $nilai }}</div>
                </div>
            @endforeach
        </div>

        <div class="mt-5">
            <div class="mb-2 flex items-center justify-between text-xs">
                <span class="font-semibold text-ink-soft">IPS per semester</span>
                <span class="text-ink-muted">skala 4,00</span>
            </div>
            <div class="flex h-24 gap-2.5">
                @foreach ([3.45, 3.60, 3.71, 3.80, 3.85] as $i => $ips)
                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-1.5">
                        <div class="flex w-full flex-1 items-end">
                            <div class="w-full rounded-t-md {{ $i === 4 ? 'bg-accent-500' : 'bg-tazkia-700/85' }}" style="height: {{ round(($ips - 2.4) / 1.6 * 100) }}%"></div>
                        </div>
                        <span class="text-[10px] leading-none text-ink-muted">{{ $i + 1 }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        @unless ($compact)
            <div class="mt-5 flex items-center justify-between rounded-xl border border-line px-4 py-3">
                <div>
                    <div class="text-[11px] text-ink-muted">Tagihan belum dibayar</div>
                    <div class="text-sm font-bold text-ink">Rp 0</div>
                </div>
                <span class="text-xs font-medium text-emerald-700">Semua lunas</span>
            </div>
        @endunless
    </div>
</div>
