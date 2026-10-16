@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $m = $mahasiswa;
    $nama = $m['nama'] ?? $anak->nama_mahasiswa ?? 'Mahasiswa';
    $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    $tgl = fn ($d) => $d ? Carbon::parse($d)->translatedFormat('d F Y') : '-';
    $dec = fn ($n) => number_format((float) $n, 2, ',', '');
    $aktif = ($m['status'] ?? null) === 'AKTIF';
    $disetujui = $krs['sudahIsiKrs'] ?? false;
    $statusKrs = $krs['statusKrs'] ?? null;

    $tingkat = [
        'bahaya' => ['wadah' => 'border-red-200 bg-red-50', 'ikon' => 'bg-red-100 text-red-600', 'judul' => 'text-red-900', 'isi' => 'text-red-800/80'],
        'peringatan' => ['wadah' => 'border-accent-200 bg-accent-50', 'ikon' => 'bg-accent-100 text-accent-600', 'judul' => 'text-ink', 'isi' => 'text-ink-soft'],
        'info' => ['wadah' => 'border-tazkia-100 bg-tazkia-50', 'ikon' => 'bg-tazkia-100 text-tazkia-700', 'judul' => 'text-tazkia-900', 'isi' => 'text-ink-soft'],
    ];
@endphp
<x-app-layout :title="$nama">
    <div class="space-y-6">
        <a href="{{ route('orangtua.beranda') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft transition hover:text-tazkia-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Semua anak
        </a>

        @if (session('status'))
            <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('status') }}
            </div>
        @endif

        {{-- Identitas --}}
        <section class="relative overflow-hidden rounded-xl2 bg-gradient-to-br from-tazkia-700 via-tazkia-800 to-tazkia-950 p-6 text-white shadow-card sm:p-8">
            <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-accent-500/25 blur-[70px]"></div>
            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-5">
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-2xl font-extrabold ring-1 ring-white/25 sm:h-20 sm:w-20 sm:text-3xl">{{ Str::upper(Str::substr($nama, 0, 1)) }}</span>
                    <div>
                        <h1 class="text-2xl font-extrabold leading-tight tracking-tight sm:text-3xl">{{ $nama }}</h1>
                        <p class="mt-1 text-white/70">NIM {{ $anak->nim }}</p>
                        @if ($m)
                            <p class="mt-1 text-sm text-white/80">{{ $m['prodiNama'] ?? '' }}@isset($m['angkatan']) · Angkatan {{ $m['angkatan'] }}@endisset</p>
                        @endif
                    </div>
                </div>
                @if ($m)
                    <div class="flex flex-wrap items-center gap-2 sm:flex-col sm:items-end">
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $aktif ? 'bg-emerald-400/20 text-emerald-200 ring-1 ring-emerald-300/40' : 'bg-white/15 text-white ring-1 ring-white/25' }}">{{ ucfirst(strtolower($m['status'] ?? '-')) }}</span>
                        @if (! empty($m['dosenWaliNama']))
                            <span class="text-sm text-white/70">Dosen wali: <span class="font-semibold text-white">{{ $m['dosenWaliNama'] }}</span></span>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        {{-- Perlu perhatian --}}
        @if (count($perhatian))
            <section aria-label="Perlu perhatian" class="space-y-3">
                @foreach ($perhatian as $p)
                    @php $t = $tingkat[$p['tingkat']] ?? $tingkat['info']; @endphp
                    <div class="flex items-start gap-4 rounded-2xl border p-4 {{ $t['wadah'] }}">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $t['ikon'] }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.3 3.4c-.87 1.5.22 3.35 1.95 3.35h14.7c1.73 0 2.82-1.85 1.95-3.35L13.95 4.1a2.25 2.25 0 00-3.9 0L2.7 16.15zM12 15.75h.01"/></svg>
                        </span>
                        <div>
                            <div class="font-bold {{ $t['judul'] }}">{{ $p['judul'] }}</div>
                            <div class="mt-0.5 text-sm leading-relaxed {{ $t['isi'] }}">{{ $p['isi'] }}</div>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- Angka utama --}}
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card p-5">
                <div class="text-xs font-medium text-ink-muted">IPK</div>
                <div class="mt-1 text-3xl font-extrabold text-tazkia-700">{{ isset($m['ipk']) ? $dec($m['ipk']) : '-' }}</div>
                <div class="mt-0.5 text-xs text-ink-muted">dari 4,00</div>
            </div>
            <div class="card p-5">
                <div class="text-xs font-medium text-ink-muted">Total SKS</div>
                <div class="mt-1 text-3xl font-extrabold text-ink">{{ $m['totalSks'] ?? '-' }}</div>
                <div class="mt-0.5 text-xs text-ink-muted">sudah ditempuh</div>
            </div>
            <div class="card p-5">
                <div class="text-xs font-medium text-ink-muted">Semester</div>
                <div class="mt-1 text-3xl font-extrabold text-ink">{{ $m['semester'] ?? '-' }}</div>
                <div class="mt-0.5 text-xs text-ink-muted">{{ $tahunAkademik['nama'] ?? '' }}</div>
            </div>
            <div class="card p-5">
                <div class="text-xs font-medium text-ink-muted">KRS semester ini</div>
                @if ($krs)
                    <div class="mt-2">
                        @if ($disetujui)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-sm font-bold text-emerald-700">Disetujui</span>
                        @elseif ($statusKrs === 'DRAFT')
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-accent-50 px-3 py-1 text-sm font-bold text-accent-700">Draf</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-sm font-bold text-red-700">Belum diisi</span>
                        @endif
                    </div>
                    <div class="mt-2 text-xs text-ink-muted">{{ $krs['jumlahMataKuliah'] ?? 0 }} mata kuliah · {{ $krs['sksSemesterIni'] ?? 0 }} SKS</div>
                @else
                    <div class="mt-2 text-sm text-ink-muted">Belum tersedia</div>
                @endif
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">

                {{-- Riwayat IPS --}}
                <section class="card p-6">
                    <div class="flex items-baseline justify-between">
                        <h2 class="text-lg font-bold text-tazkia-900">Indeks prestasi per semester</h2>
                        <span class="text-xs text-ink-muted">IPS, skala 0–4</span>
                    </div>

                    @if (is_array($riwayatIpk) && count($riwayatIpk))
                        <div class="mt-6">
                            {{-- Area plot: garis sumbu dan bar memakai tinggi yang sama, jadi tinggi bar = nilai IPS sebenarnya (skala 0-4). --}}
                            <div class="relative h-52">
                                <div class="absolute inset-x-0 bottom-0 top-6">
                                    @foreach ([4, 3, 2, 1] as $g)
                                        <div class="absolute inset-x-0 flex items-center gap-2" style="bottom: {{ $g / 4 * 100 }}%">
                                            <span class="w-4 -translate-y-px text-[10px] leading-none text-ink-muted">{{ $g }}</span>
                                            <div class="flex-1 border-t border-dashed border-line"></div>
                                        </div>
                                    @endforeach
                                    <div class="absolute inset-y-0 left-6 right-0 flex items-end justify-around gap-2 sm:gap-4">
                                        @foreach ($riwayatIpk as $r)
                                            @php $terakhir = $loop->last; $ips = (float) ($r['ips'] ?? 0); @endphp
                                            <div class="flex h-full max-w-[4.5rem] flex-1 flex-col items-center justify-end">
                                                <div class="mb-1 shrink-0 text-xs font-bold leading-none {{ $terakhir ? 'text-accent-600' : 'text-tazkia-700' }}">{{ $dec($ips) }}</div>
                                                <div class="w-full shrink-0 rounded-t-lg {{ $terakhir ? 'bg-accent-500' : 'bg-tazkia-700' }}" style="height: {{ max(0, min(100, $ips / 4 * 100)) }}%"></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 flex justify-around gap-2 pl-6 sm:gap-4">
                                @foreach ($riwayatIpk as $r)
                                    <div class="max-w-[4.5rem] flex-1 text-center">
                                        <div class="text-xs font-semibold text-ink-soft">Smt {{ $r['semesterKe'] ?? '' }}</div>
                                        <div class="text-[10px] text-ink-muted">{{ $r['sks'] ?? 0 }} SKS</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif (is_array($riwayatIpk))
                        <p class="mt-4 rounded-xl bg-canvas px-4 py-6 text-center text-sm text-ink-muted">Belum ada nilai final yang dapat ditampilkan.</p>
                    @else
                        <p class="mt-4 rounded-xl bg-canvas px-4 py-6 text-center text-sm text-ink-muted">Data belum tersedia saat ini. Coba muat ulang beberapa saat lagi.</p>
                    @endif
                </section>

                {{-- Kehadiran --}}
                <section class="card p-6">
                    <div class="flex items-baseline justify-between">
                        <h2 class="text-lg font-bold text-tazkia-900">Kehadiran</h2>
                        <span class="text-xs text-ink-muted">{{ $tahunAkademik['nama'] ?? '' }}</span>
                    </div>

                    @if (is_array($presensi) && count($presensi))
                        <ul class="mt-5 divide-y divide-line">
                            @foreach ($presensi as $p)
                                @php
                                    $total = $p['totalPertemuan'] ?? 0;
                                    $persen = (float) ($p['persenHadir'] ?? 0);
                                    $rendah = $total > 0 && $persen < \App\Services\Siakad\ProfilMahasiswa::BATAS_HADIR;
                                @endphp
                                <li class="py-4 first:pt-0 last:pb-0">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="font-semibold text-ink">{{ $p['mkNama'] ?? '-' }}</div>
                                            <div class="mt-0.5 text-xs text-ink-muted">
                                                @if ($total > 0)
                                                    Hadir {{ $p['hadir'] ?? 0 }} · Izin {{ $p['izin'] ?? 0 }} · Sakit {{ $p['sakit'] ?? 0 }} · Alpha {{ $p['alpha'] ?? 0 }} &nbsp;dari {{ $total }} pertemuan
                                                @else
                                                    Belum ada pertemuan tercatat
                                                @endif
                                            </div>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            @if ($total > 0)
                                                <div class="text-lg font-extrabold {{ $rendah ? 'text-red-600' : 'text-tazkia-700' }}">{{ number_format($persen, 0) }}%</div>
                                            @else
                                                <div class="text-lg font-extrabold text-ink-muted">–</div>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($total > 0)
                                        <div class="mt-2.5 h-2 overflow-hidden rounded-full bg-canvas">
                                            <div class="h-full rounded-full {{ $rendah ? 'bg-red-500' : 'bg-tazkia-700' }}" style="width: {{ min(100, $persen) }}%"></div>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-4 text-xs text-ink-muted">Persentase dihitung atas pertemuan yang sudah tercatat. Merah berarti di bawah {{ \App\Services\Siakad\ProfilMahasiswa::BATAS_HADIR }}%.</p>
                    @elseif (is_array($presensi))
                        <p class="mt-4 rounded-xl bg-canvas px-4 py-6 text-center text-sm text-ink-muted">Belum ada mata kuliah pada semester ini.</p>
                    @else
                        <p class="mt-4 rounded-xl bg-canvas px-4 py-6 text-center text-sm text-ink-muted">Data belum tersedia saat ini. Coba muat ulang beberapa saat lagi.</p>
                    @endif
                </section>
            </div>

            <div class="space-y-6">
                {{-- Tagihan --}}
                <section class="card p-6">
                    <h2 class="text-lg font-bold text-tazkia-900">Tagihan</h2>
                    @if ($tagihan)
                        @php
                            $totalTagih = (float) ($tagihan['totalTagih'] ?? 0);
                            $dibayar = (float) ($tagihan['totalDibayar'] ?? 0);
                            $sisa = (float) ($tagihan['totalBelumDibayar'] ?? 0);
                            $persenBayar = $totalTagih > 0 ? min(100, $dibayar / $totalTagih * 100) : 100;
                        @endphp
                        <div class="mt-4">
                            <div class="text-xs font-medium text-ink-muted">Belum dibayar</div>
                            <div class="mt-0.5 text-3xl font-extrabold {{ $sisa > 0 ? 'text-ink' : 'text-emerald-600' }}">{{ $sisa > 0 ? $rp($sisa) : 'Lunas' }}</div>
                        </div>

                        <div class="mt-5 h-2.5 overflow-hidden rounded-full bg-canvas">
                            <div class="h-full rounded-full bg-gradient-to-r from-tazkia-600 to-tazkia-700" style="width: {{ $persenBayar }}%"></div>
                        </div>
                        <div class="mt-2 flex justify-between text-xs text-ink-muted">
                            <span>Dibayar {{ $rp($dibayar) }}</span>
                            <span>Total {{ $rp($totalTagih) }}</span>
                        </div>

                        <dl class="mt-5 space-y-3 border-t border-line pt-4 text-sm">
                            <div class="flex justify-between"><dt class="text-ink-soft">Tagihan lunas</dt><dd class="font-semibold">{{ $tagihan['jumlahLunas'] ?? 0 }} dari {{ $tagihan['jumlahTagihan'] ?? 0 }}</dd></div>
                            <div class="flex justify-between"><dt class="text-ink-soft">Jatuh tempo terdekat</dt><dd class="font-semibold">{{ $tgl($tagihan['jatuhTempoTerdekat'] ?? null) }}</dd></div>
                        </dl>
                    @else
                        <p class="mt-4 rounded-xl bg-canvas px-4 py-6 text-center text-sm text-ink-muted">Data belum tersedia saat ini.</p>
                    @endif
                </section>

                {{-- Masa KRS --}}
                @if ($tahunAkademik && ! empty($tahunAkademik['tanggalMulaiKrs']))
                    <section class="card p-6">
                        <h2 class="text-lg font-bold text-tazkia-900">Jadwal KRS</h2>
                        <p class="mt-1 text-sm text-ink-muted">{{ $tahunAkademik['nama'] }}</p>
                        <div class="mt-4 flex items-center gap-3">
                            <div class="flex-1 rounded-xl bg-canvas p-3 text-center"><div class="text-[11px] text-ink-muted">Mulai</div><div class="text-sm font-bold text-ink">{{ $tgl($tahunAkademik['tanggalMulaiKrs']) }}</div></div>
                            <svg class="h-4 w-4 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            <div class="flex-1 rounded-xl bg-canvas p-3 text-center"><div class="text-[11px] text-ink-muted">Selesai</div><div class="text-sm font-bold text-ink">{{ $tgl($tahunAkademik['tanggalSelesaiKrs'] ?? null) }}</div></div>
                        </div>
                        @if ($masaKrs)
                            <p class="mt-3 inline-flex items-center gap-2 rounded-full bg-accent-50 px-3 py-1 text-xs font-semibold text-accent-700"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-accent-500"></span>Sedang masa pengisian KRS</p>
                        @endif
                    </section>
                @endif

                <form method="POST" action="{{ route('orangtua.anak.destroy', $anak->nim) }}" onsubmit="return confirm('Hapus mahasiswa ini dari daftar Anda?')" class="text-center">
                    @csrf @method('DELETE')
                    <button class="text-sm text-ink-muted underline-offset-2 transition hover:text-red-600 hover:underline">Hapus dari daftar saya</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
