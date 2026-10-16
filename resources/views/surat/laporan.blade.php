@php
    // Format angka mengikuti surat resmi: titik desimal (3.30, 128.60).
    $n2 = fn ($v) => number_format((float) $v, 2, '.', '');
    $tgl = $surat->tempat_surat.', '.$surat->tanggal_surat->translatedFormat('j F Y').' M / '.$hijriah;
    $tglMasehi = $surat->tempat_surat.', '.$surat->tanggal_surat->translatedFormat('j F Y');
    $ps = $surat->penandatangan;
    $idPs = collect([$ps?->nidn ? 'NIDN. '.$ps->nidn : null, $ps?->nik ? 'NIK. '.$ps->nik : null])->filter()->implode(' / ');
    $angkatan = $surat->teks_angkatan ?: ($surat->filter_angkatan ? 'angkatan '.$surat->filter_angkatan : null);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $surat->perihal }}</title>
<style>
    /* Titik asal = (36pt, 36pt) dari tepi kertas. Margin bawah kecil: transkrip 4 semester nyaris penuh satu halaman. */
    @page { margin: 36pt 36pt 6pt 36pt; }
    body { margin: 0; font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #000; }
    .rel { position: relative; }
    .latar { position: absolute; left: -36pt; top: -36pt; width: 595.3pt; height: 841.9pt; z-index: -1; }

    /* ===== Surat & lampiran (Times 12pt, margin kiri/kanan 72pt) ===== */
    .surat { padding: 0 31pt 0 36pt; }
    .l15 { line-height: 14.95pt; }
    .jeda { height: 14.95pt; }
    table.meta { border-collapse: collapse; }
    table.meta td { padding: 0; vertical-align: top; line-height: 14.95pt; }
    table.meta td.kolon { padding-right: 2.8pt; }
    p.alinea { margin: 0; text-align: justify; line-height: 14.95pt; }
    .arab { height: 23pt; }
    .arab img { margin: -4pt 0 0 -2pt; }
    .ttd-blok { line-height: 13.8pt; }
    .ttd-ruang { position: relative; height: 62.5pt; }
    .ttd-ruang img { position: absolute; }
    table.lamp { border-collapse: collapse; width: 439pt; }
    table.lamp th { background: #bdd6ee; font-weight: bold; text-align: center; border: 1pt solid #000; padding: 2pt 1.5pt; line-height: 13.8pt; }
    table.lamp td { border: 1pt solid #000; padding: 1.5pt 1.6pt; vertical-align: middle; line-height: 13.8pt; }
    .c { text-align: center; }

    /* ===== Transkrip (Helvetica 8pt) ===== */
    .trans { font-family: Helvetica, Arial, sans-serif; font-size: 8pt; }
    .trans-judul { text-align: center; font-size: 12pt; line-height: 12pt; padding-top: 8.2pt; }
    .trans-sub { text-align: center; font-size: 8pt; line-height: 10pt; padding-top: 1.4pt; }
    table.info { border-collapse: collapse; margin: 16.2pt 0 0 132.8pt; }
    table.info td { padding: 0; line-height: 11.8pt; vertical-align: top; font-size: 8pt; }
    table.trs { border-collapse: collapse; width: 523pt; margin-top: 10pt; }
    table.trs th, table.trs td { font-size: 8pt; line-height: 8pt; border-left: .5pt solid #000; border-right: .5pt solid #000; padding: 3.8pt 2pt; vertical-align: middle; }
    table.trs th { border: .5pt solid #000; padding: 2pt 1pt; font-weight: bold; text-align: left; }
    table.trs tr.g td { border-top: .5pt solid #000; }
    table.trs tr.j td { border: .5pt solid #000; padding: 2pt 2pt; }
    table.trs tr.x td { border-bottom: .5pt solid #000; }
    .sm { text-align: center; }
    .koor { margin-top: 20pt; font-size: 8pt; line-height: 12pt; }
    .koor-ttd { overflow: hidden; }
    .koor-ttd img { vertical-align: top; }
</style>
</head>
<body>

{{-- ============ HALAMAN 1: SURAT PENGANTAR ============ --}}
<div class="rel surat" style="height: 760pt;">
    <img class="latar" src="{{ $aset['latar'] }}">
    <img src="{{ $aset['basmalah'] }}" style="position: absolute; left: 204pt; top: 37pt; width: 114pt; height: 22pt; z-index: 1;">

    <div style="padding-top: 67pt;" class="l15">{{ $tgl }}</div>
    <div class="jeda"></div>

    <table class="meta">
        <tr><td style="width: 72.1pt;">Nomor</td><td class="kolon" style="width: 3.3pt;">:</td><td style="width: 376.1pt;">{{ $surat->nomor_surat }}</td></tr>
        <tr><td>Lampiran</td><td>:</td><td>{{ $surat->lampiran }}</td></tr>
        <tr><td>Perihal</td><td>:</td><td><strong>{{ $surat->perihal }}</strong></td></tr>
    </table>

    <div class="jeda"></div><div class="jeda"></div>

    <div class="l15">Kepada Yth.</div>
    <div class="l15"><strong>{{ $surat->penerima_nama }}</strong></div>
    <div class="l15"><strong>{{ $surat->penerima_jabatan }}</strong></div>
    <div class="l15">{!! nl2br(e($surat->penerima_alamat)) !!}</div>

    <div class="jeda"></div>
    <div class="arab"><img src="{{ $aset['salamPembuka'] }}" style="width: 132.2pt; height: 21.3pt;"></div>

    <p class="alinea">Pertama-tama perkenankan kami menghaturkan Tahiyyah Islamiyah semoga Jajaran Pengurus {{ $surat->instansi_tujuan }} senantiasa berada dalam lindungan Allah Ta’ala Aamiin.</p>
    <div class="jeda"></div>
    <p class="alinea">Bersama ini kami laporkan perkembangan akademik mahasiswa Universitas Islam Tazkia penerima {{ $surat->nama_beasiswa_surat ?: $surat->beasiswa_nama }} sampai dengan {{ $semesterLabel }}@if ($angkatan) {{ $angkatan }}@endif. Semoga Allah SWT membalas kebaikan dengan pahala yang berlipat-lipat.</p>
    @if ($surat->catatan)
        <div style="height: 12pt;"></div>
        <p class="alinea">{!! nl2br(e($surat->catatan)) !!}</p>
    @endif
    <div style="height: 12pt;"></div>
    <p class="alinea">Demikian laporan ini kami sampaikan. Atas perhatian dan kerja sama yang diberikan, kami ucapkan terima kasih. <em>Jazakumullahu khairan katsira.</em></p>

    <div class="arab" style="margin-top: 8pt;"><img src="{{ $aset['salamPenutup'] }}" style="width: 137.4pt; height: 21.3pt;"></div>

    <div class="ttd-blok" style="margin: 15.8pt 0 0 -1.8pt;">
        Salam Ta’zim,<br>
        {{ $ps?->jabatan }}
        <div class="ttd-ruang">
            @if ($ttd)<img src="{{ $ttd }}" style="left: 0; top: 0; height: 62.5pt;">@endif
        </div>
        <strong><u>{{ $ps?->nama }}</u></strong><br>
        {{ $idPs }}
    </div>
</div>

{{-- ============ HALAMAN 2: LAMPIRAN RINCIAN IPK ============ --}}
<div class="rel surat" style="page-break-before: always; height: 760pt;">
    <img class="latar" src="{{ $aset['latar'] }}">

    <div style="padding-top: 77pt;">
        <table class="meta">
            <tr><td style="width: 72.1pt;">Lampiran</td><td class="kolon" style="width: 3.3pt;">:</td><td style="width: 376.1pt;">Rincian Perkembangan Indeks Prestasi Akademik (IPK)</td></tr>
            <tr><td>Nomor</td><td>:</td><td>{{ $surat->nomor_surat }}</td></tr>
            <tr><td>Tanggal</td><td>:</td><td>{{ $tgl }}</td></tr>
        </table>
    </div>

    <table class="lamp" style="margin-top: 13.8pt;">
        <thead>
            <tr><th style="width: 20.4pt;">No</th><th style="width: 64.3pt;">NIM</th><th style="width: 127.4pt;">Nama Mahasiswa</th><th style="width: 120.4pt;">Program Studi</th><th style="width: 51pt;">Angkatan</th><th style="width: 30.4pt;">IPK</th></tr>
        </thead>
        <tbody>
            @foreach ($penerima as $p)
                <tr>
                    <td class="c">{{ $loop->iteration }}</td>
                    <td>{{ $p['nim'] }}</td>
                    <td>{{ $p['nama'] }}</td>
                    <td>{{ $p['prodiNama'] ?? '-' }}</td>
                    <td class="c">{{ $p['angkatan'] ?? '-' }}</td>
                    <td class="c">{{ $n2($p['ipk'] ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="ttd-blok" style="margin: 32.5pt 0 0 5.4pt;">
        Salam Ta’zim,<br>
        {{ $ps?->jabatan }}
        <div class="ttd-ruang">
            @if ($ttd)<img src="{{ $ttd }}" style="left: 0; top: 0; height: 62.5pt;">@endif
        </div>
        <strong><u>{{ $ps?->nama }}</u></strong><br>
        {{ $idPs }}
    </div>
</div>

{{-- ============ HALAMAN 3+: TRANSKRIP PER MAHASISWA ============ --}}
@foreach ($penerima as $p)
    @php
        $namaK = $p['koordinatorProdi']['nama'] ?? null;

        // Tinggi baris (pt) hasil ukur PDF: 1 baris 15.7, nama 2 baris 23.7, +0.5 garis di awal semester.
        $tinggiKelompok = fn ($smt) => 0.5 + collect($smt['matakuliah'])->sum(fn ($mk) => mb_strlen($mk['nama'] ?? '') > 37 ? 23.7 : 15.7);
        $kapPertama = 686;   // sisa tinggi halaman pertama setelah kepala tabel
        $kapLanjut = 779;    // halaman lanjutan (kepala tabel saja di atas)
        $cadangan = 12;      // jaga-jaga selisih perkiraan
        $footer = 30;        // baris Jumlah + IPK

        // Pecah di batas semester agar satu semester tidak terbelah antar halaman.
        $bagian = []; $kini = []; $tinggi = 0; $kap = $kapPertama - $cadangan;
        foreach ($p['semester'] ?? [] as $smt) {
            $h = $tinggiKelompok($smt);
            if ($kini && $tinggi + $h > $kap) { $bagian[] = $kini; $kini = []; $tinggi = 0; $kap = $kapLanjut - $cadangan; }
            $kini[] = $smt; $tinggi += $h;
        }
        if ($kini && $tinggi + $footer > $kap && count($kini) > 1) { $terakhir = array_pop($kini); $bagian[] = $kini; $kini = [$terakhir]; }
        $bagian[] = $kini;

        // Muat satu halaman (A4 842pt)? Bagian atas sampai tabel ~130pt; blok tanda tangan normal ~134pt.
        $tinggiTabel = 12 + 24 + collect($p['semester'] ?? [])->sum($tinggiKelompok);
        $rapat = count($bagian) === 1 && 130 + $tinggiTabel + 134 > 826;
    @endphp
    <div class="trans rel" style="page-break-before: always;">
        <img src="{{ $aset['logo'] }}" style="position: absolute; left: 44pt; top: 9pt; width: 60pt; height: 40.6pt;">

        <div class="trans-judul">{{ config('surat.judul_transkrip') }}</div>
        <div class="trans-sub">Transkrip Nilai Akademik</div>

        <table class="info">
            <tr><td style="width: 110pt;">NIM</td><td style="width: 6.2pt;">:</td><td>{{ $p['nim'] }}</td></tr>
            <tr><td>Nama</td><td>:</td><td>{{ $p['nama'] }}</td></tr>
            <tr><td>Program Studi</td><td>:</td><td>{{ $p['prodiNama'] ?? '-' }}</td></tr>
        </table>

        @foreach ($bagian as $n => $kelompok)
            @php $akhir = $loop->last; @endphp
            <div style="{{ $n > 0 ? 'page-break-before: always;' : '' }}">
            <table class="trs" style="{{ $n > 0 ? 'margin-top: 0;' : '' }}">
                <thead>
                    <tr><th class="c" style="width: 65.7pt;">Semester</th><th style="width: 88.5pt;">Kode MK</th><th style="width: 156.6pt;">Nama Mata Kuliah</th><th style="width: 20.3pt;">SKS</th><th style="width: 65.7pt;">Nilai</th><th style="width: 43pt;">Bobot</th><th style="width: 65.7pt;">Mutu</th></tr>
                </thead>
                <tbody>
                    @foreach ($kelompok as $smt)
                        @php $tengah = intdiv(count($smt['matakuliah']) - 1, 2); @endphp
                        @foreach ($smt['matakuliah'] as $i => $mk)
                            <tr class="{{ $i === 0 ? 'g' : '' }} {{ ! $akhir && $loop->last && $loop->parent->last ? 'x' : '' }}">
                                <td class="sm">{{ $i === $tengah ? $smt['semesterKe'] : '' }}</td>
                                <td style="padding-left: 3pt;">{{ $mk['kode'] ?? '' }}</td>
                                <td>{{ $mk['nama'] }}</td>
                                <td class="c">{{ $mk['sks'] }}</td>
                                <td class="c">{{ $mk['nilai'] }}</td>
                                <td class="c">{{ $n2($mk['bobot']) }}</td>
                                <td class="c">{{ $n2($mk['mutu']) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    @if ($akhir)
                        <tr class="j"><td colspan="3">Jumlah :</td><td>{{ $p['totalSks'] ?? 0 }}</td><td colspan="2"></td><td>{{ $n2($p['totalMutu'] ?? 0) }}</td></tr>
                        <tr class="j"><td colspan="3">Indeks Prestasi Kumulatif (IPK) :</td><td>{{ $n2($p['ipk'] ?? 0) }}</td><td colspan="3"></td></tr>
                    @endif
                </tbody>
            </table>
            </div>
        @endforeach

        <div style="page-break-inside: avoid;">
            <div class="koor" style="margin-top: {{ $rapat ? 11 : 20 }}pt;">
                <strong>{{ $tglMasehi }}</strong><br>
                Koordinator Program Studi {{ $p['prodiNama'] ?? '' }}
                <div class="koor-ttd" style="height: {{ $rapat ? 34 : 44 }}pt;"></div>
                {{ $namaK }}
            </div>
        </div>
    </div>
@endforeach

</body>
</html>
