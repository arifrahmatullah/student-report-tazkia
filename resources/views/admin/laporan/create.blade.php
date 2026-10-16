@php
    $in = fn ($err) => $errors->has($err) ? 'field-error' : '';
    $seksi = 'card p-6 sm:p-7';
@endphp
<x-app-layout title="Buat laporan">
    <x-slot name="header">
        <a href="{{ route('admin.laporan.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft transition hover:text-tazkia-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Semua laporan
        </a>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-tazkia-900">Buat laporan hasil belajar</h1>
        <p class="mt-1.5 text-ink-soft">Angka diambil langsung dari SIAKAD saat Anda menekan tombol buat, lalu disimpan sebagai salinan pada surat.</p>
    </x-slot>

    @if ($gagal)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <span>Sebagian data dari SIAKAD tidak dapat dimuat, sehingga pilihan di bawah mungkin belum lengkap.</span>
            <a href="{{ route('admin.laporan.create') }}" class="shrink-0 font-bold hover:underline">Muat ulang</a>
        </div>
    @endif

    @if ($penandatangan->isEmpty())
        <div class="mb-6 flex items-center justify-between gap-4 rounded-xl border border-accent-200 bg-accent-50 px-4 py-3 text-sm text-ink">
            <span>Belum ada penandatangan aktif. Surat membutuhkannya.</span>
            <a href="{{ route('admin.penandatangan.create') }}" class="shrink-0 font-bold text-accent-700 hover:underline">Tambah penandatangan</a>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.laporan.store') }}" class="space-y-6" x-data="laporanForm(@js($beasiswa->pluck('nama', 'id')), @js(old('beasiswa_id', '')), @js(old('nama_beasiswa_surat', '')), @js(old('perihal', '')))" @submit="busy = true">
        @csrf

        {{-- 1. Data laporan --}}
        <section class="{{ $seksi }}">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-tazkia-700 text-sm font-extrabold text-white">1</span>
                <h2 class="text-lg font-bold text-tazkia-900">Isi laporan</h2>
            </div>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="beasiswa_id" value="Program beasiswa" />
                    <select id="beasiswa_id" name="beasiswa_id" required x-model="sel" @change="pilih()" class="field mt-1.5 {{ $in('beasiswa_id') }}">
                        <option value="">Pilih beasiswa…</option>
                        @foreach ($beasiswa as $b)
                            <option value="{{ $b['id'] }}" @selected(old('beasiswa_id') === $b['id'])>{{ $b['nama'] }} — {{ $b['jumlahPenerima'] }} penerima</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('beasiswa_id')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="tahun_akademik_id" value="Laporan sampai dengan" />
                    <select id="tahun_akademik_id" name="tahun_akademik_id" required class="field mt-1.5 {{ $in('tahun_akademik_id') }}">
                        @foreach ($tahun as $t)
                            <option value="{{ $t['id'] }}" @selected(old('tahun_akademik_id', $tahunBawaan) === $t['id'])>{{ $t['nama'] }}{{ ($t['aktif'] ?? false) ? ' (berjalan)' : '' }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-ink-muted">Hanya nilai final sampai semester ini yang dimasukkan.</p>
                    <x-input-error :messages="$errors->get('tahun_akademik_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="prodi_id" value="Program studi (opsional)" />
                    <select id="prodi_id" name="prodi_id" class="field mt-1.5">
                        <option value="">Semua program studi</option>
                        @foreach ($prodi as $p)
                            <option value="{{ $p['id'] }}" @selected(old('prodi_id') === $p['id'])>{{ $p['nama'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="angkatan" value="Angkatan (opsional)" />
                    <x-text-input id="angkatan" name="angkatan" type="number" inputmode="numeric" min="1990" max="2100" class="mt-1.5 {{ $in('angkatan') }}" :value="old('angkatan')" placeholder="Semua angkatan" />
                    <x-input-error :messages="$errors->get('angkatan')" class="mt-2" />
                </div>
            </div>
        </section>

        {{-- 2. Data surat --}}
        <section class="{{ $seksi }}">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-tazkia-700 text-sm font-extrabold text-white">2</span>
                <h2 class="text-lg font-bold text-tazkia-900">Surat pengantar</h2>
            </div>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="nomor_surat" value="Nomor surat" />
                    <x-text-input id="nomor_surat" name="nomor_surat" class="mt-1.5 {{ $in('nomor_surat') }}" :value="old('nomor_surat')" required placeholder="Contoh: 129-B/KBM/Universitas Tazkia/Akd/08/2026" />
                    <x-input-error :messages="$errors->get('nomor_surat')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="lampiran" value="Lampiran" />
                    <x-text-input id="lampiran" name="lampiran" class="mt-1.5 {{ $in('lampiran') }}" :value="old('lampiran', 'Satu Berkas')" required />
                    <x-input-error :messages="$errors->get('lampiran')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="tempat_surat" value="Tempat" />
                    <x-text-input id="tempat_surat" name="tempat_surat" class="mt-1.5 {{ $in('tempat_surat') }}" :value="old('tempat_surat', config('surat.tempat_default'))" required />
                    <x-input-error :messages="$errors->get('tempat_surat')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="tanggal_surat" value="Tanggal surat" />
                    <x-text-input id="tanggal_surat" name="tanggal_surat" type="date" class="mt-1.5 {{ $in('tanggal_surat') }}" :value="old('tanggal_surat', now()->toDateString())" required />
                    <x-input-error :messages="$errors->get('tanggal_surat')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="tanggal_hijriah" value="Tanggal Hijriah (opsional)" />
                    <x-text-input id="tanggal_hijriah" name="tanggal_hijriah" class="mt-1.5" :value="old('tanggal_hijriah')" placeholder="Contoh: 1 Rabi’ul Awal 1448 H" />
                </div>
                <p class="-mt-2 text-xs text-ink-muted sm:col-span-2">Kosongkan agar dihitung otomatis. Hitungan otomatis memakai kalender sipil dan bisa berbeda satu hari dari penetapan Kemenag; isi manual bila perlu.</p>

                <div class="sm:col-span-2">
                    <x-input-label for="nama_beasiswa_surat" value="Nama beasiswa pada surat" />
                    <x-text-input id="nama_beasiswa_surat" name="nama_beasiswa_surat" x-model="namaSurat" @input="namaDiedit = true" class="mt-1.5 {{ $in('nama_beasiswa_surat') }}" required placeholder="Contoh: Beasiswa Pemerintah Kabupaten Balangan" />
                    <p class="mt-1.5 text-xs text-ink-muted">Terisi otomatis dari pilihan beasiswa; ubah bila penulisan di surat berbeda.</p>
                    <x-input-error :messages="$errors->get('nama_beasiswa_surat')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="perihal" value="Perihal" />
                    <x-text-input id="perihal" name="perihal" x-model="perihal" @input="perihalDiedit = true" class="mt-1.5 {{ $in('perihal') }}" required />
                    <x-input-error :messages="$errors->get('perihal')" class="mt-2" />
                </div>

                <div class="border-t border-line pt-5 sm:col-span-2">
                    <h3 class="text-sm font-bold text-tazkia-900">Kepada Yth.</h3>
                </div>
                <div>
                    <x-input-label for="penerima_nama" value="Nama penerima surat" />
                    <x-text-input id="penerima_nama" name="penerima_nama" class="mt-1.5 {{ $in('penerima_nama') }}" :value="old('penerima_nama')" required placeholder="Bapak Norwanto, SE" />
                    <x-input-error :messages="$errors->get('penerima_nama')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="penerima_jabatan" value="Jabatan" />
                    <x-text-input id="penerima_jabatan" name="penerima_jabatan" class="mt-1.5 {{ $in('penerima_jabatan') }}" :value="old('penerima_jabatan')" required placeholder="Kepala Bagian Kesejahteraan Rakyat" />
                    <x-input-error :messages="$errors->get('penerima_jabatan')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="penerima_alamat" value="Alamat" />
                    <textarea id="penerima_alamat" name="penerima_alamat" rows="3" required class="field mt-1.5 {{ $in('penerima_alamat') }}" placeholder="Jln. Jend. Ahmad Yani No. 01 …">{{ old('penerima_alamat') }}</textarea>
                    <x-input-error :messages="$errors->get('penerima_alamat')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="instansi_tujuan" value="Instansi (untuk kalimat salam pembuka)" />
                    <x-text-input id="instansi_tujuan" name="instansi_tujuan" class="mt-1.5 {{ $in('instansi_tujuan') }}" :value="old('instansi_tujuan')" required placeholder="Pemerintah Kabupaten Balangan" />
                    <p class="mt-1.5 text-xs text-ink-muted">Dipakai pada kalimat “… semoga Jajaran Pengurus <em>instansi</em> senantiasa berada dalam lindungan Allah …”.</p>
                    <x-input-error :messages="$errors->get('instansi_tujuan')" class="mt-2" />
                </div>

                <div class="border-t border-line pt-5 sm:col-span-2">
                    <x-input-label for="teks_angkatan" value="Keterangan angkatan pada surat (opsional)" />
                    <x-text-input id="teks_angkatan" name="teks_angkatan" class="mt-1.5" :value="old('teks_angkatan')" placeholder="Contoh: angkatan 2025" />
                    <p class="mt-1.5 text-xs text-ink-muted">Ditulis setelah “sampai dengan Semester …”. Jika kosong dan filter angkatan di atas diisi, dipakai filter itu.</p>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="catatan" value="Paragraf tambahan (opsional)" />
                    <textarea id="catatan" name="catatan" rows="3" class="field mt-1.5" placeholder="Disisipkan sebelum kalimat penutup, bila ada">{{ old('catatan') }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="penandatangan_id" value="Penandatangan" />
                    <select id="penandatangan_id" name="penandatangan_id" required class="field mt-1.5 {{ $in('penandatangan_id') }}">
                        <option value="">Pilih penandatangan…</option>
                        @foreach ($penandatangan as $p)
                            <option value="{{ $p->id }}" @selected((string) old('penandatangan_id') === (string) $p->id)>{{ $p->nama }} — {{ $p->jabatan }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('penandatangan_id')" class="mt-2" />
                </div>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.laporan.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary shadow-lg shadow-tazkia-700/25" x-bind:disabled="busy">
                <span x-show="!busy">Buat laporan</span>
                <span x-show="busy" x-cloak>Mengambil data dari SIAKAD…</span>
            </button>
        </div>
    </form>

    <script>
        function laporanForm(daftar, terpilih, namaAwal, perihalAwal) {
            const judul = (t) => t.toLowerCase().replace(/(^|\s)(\S)/g, (m, a, b) => a + b.toUpperCase());
            return {
                busy: false, sel: terpilih, namaSurat: namaAwal,
                perihal: perihalAwal || 'Laporan Akademik Mahasiswa Penerima Beasiswa',
                namaDiedit: namaAwal !== '', perihalDiedit: perihalAwal !== '',
                pilih() {
                    const nama = daftar[this.sel];
                    if (!nama) return;
                    if (!this.namaDiedit) this.namaSurat = judul(nama);
                    if (!this.perihalDiedit) this.perihal = 'Laporan Akademik Mahasiswa ' + this.namaSurat;
                },
            };
        }
    </script>
</x-app-layout>
