<?php

namespace Tests\Feature;

use App\Models\Penandatangan;
use App\Models\SuratLaporan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['siakad.api_key' => 'kunci-uji', 'siakad.base_url' => 'https://siakad.test']);
        foreach (['admin', 'akademik', 'orangtua'] as $r) {
            Role::findOrCreate($r);
        }
        Storage::fake('local');
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function laporanApi(): array
    {
        $mk = fn ($kode, $nama, $sks, $nilai, $bobot) => ['kode' => $kode, 'nama' => $nama, 'sks' => $sks, 'nilai' => $nilai, 'bobot' => $bobot, 'mutu' => $sks * $bobot];

        return [
            'beasiswa' => ['id' => 'b1', 'nama' => 'BEASISWA KABUPATEN BALANGAN', 'status' => 'AKTIF', 'jumlahPenerima' => 1],
            'sampaiTahunAkademik' => ['id' => 't2', 'nama' => 'Genap 2025/2026', 'kode' => '20252'],
            'penerima' => [[
                'nim' => '2510101078', 'nama' => 'Muhammad Wildan Amin', 'prodiNama' => 'Manajemen Bisnis Syariah', 'angkatan' => 2025,
                'koordinatorProdi' => ['nama' => 'Aminah Nuriyah, S.E.I., M.E.', 'jabatan' => 'Koordinator Program Studi'],
                'semester' => [[
                    'semesterKe' => 1, 'tahunAkademik' => 'Ganjil 2025/2026',
                    'matakuliah' => [$mk('MKUT.001', 'Bahasa Indonesia', 2, 'A', 4.0), $mk('MKUT.002', 'Pendidikan Pancasila', 2, 'B+', 3.3)],
                    'totalSks' => 4, 'totalMutu' => 14.6, 'ips' => 3.65,
                ]],
                'totalSks' => 4, 'totalMutu' => 14.6, 'ipk' => 3.65,
            ]],
        ];
    }

    private function formLaporan(Penandatangan $p, array $ubah = []): array
    {
        return $ubah + [
            'beasiswa_id' => 'b1', 'tahun_akademik_id' => 't2', 'penandatangan_id' => $p->id,
            'nomor_surat' => '123/WR1/X/2026', 'tempat_surat' => 'Bogor', 'tanggal_surat' => '2026-10-02',
            'perihal' => 'Laporan Akademik', 'lampiran' => 'Satu Berkas', 'nama_beasiswa_surat' => 'Beasiswa Pemerintah Kabupaten Balangan',
            'instansi_tujuan' => 'Pemerintah Kabupaten Balangan', 'penerima_nama' => 'Bapak Norwanto, SE',
            'penerima_jabatan' => 'Kepala Bagian Kesejahteraan Rakyat', 'penerima_alamat' => 'Jln. Jend. Ahmad Yani No. 01 Paringin',
        ];
    }

    public function test_hanya_admin_boleh_masuk(): void
    {
        $this->get('/admin/laporan')->assertRedirect('/login');

        foreach (['orangtua', 'akademik'] as $role) {
            $u = User::factory()->create()->assignRole($role);
            $this->actingAs($u)->get('/admin')->assertForbidden();
            $this->actingAs($u)->get('/admin/laporan')->assertForbidden();
            $this->actingAs($u)->get('/admin/penandatangan')->assertForbidden();
        }
    }

    public function test_penandatangan_dengan_gambar_ttd_tersimpan_privat(): void
    {
        $this->actingAs($this->admin())->post('/admin/penandatangan', [
            'nama' => 'Dr. Contoh, M.E.', 'jabatan' => 'Wakil Rektor I', 'nidn' => '123', 'is_active' => 1,
            'ttd' => UploadedFile::fake()->image('ttd.png', 200, 80),
        ])->assertRedirect('/admin/penandatangan');

        $p = Penandatangan::firstOrFail();
        Storage::disk('local')->assertExists($p->ttd_path);
        $this->actingAs($this->admin())->get("/admin/penandatangan/{$p->uuid}/ttd")->assertOk();
        $this->actingAs(User::factory()->create()->assignRole('orangtua'))->get("/admin/penandatangan/{$p->uuid}/ttd")->assertForbidden();
    }

    public function test_penandatangan_menolak_berkas_bukan_gambar(): void
    {
        $this->actingAs($this->admin())->post('/admin/penandatangan', [
            'nama' => 'A', 'jabatan' => 'B', 'ttd' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors('ttd');
    }

    public function test_membuat_laporan_menyimpan_salinan_dan_menghasilkan_pdf(): void
    {
        Http::fake(['*/beasiswa/b1/laporan-akademik*' => Http::response($this->laporanApi())]);
        $p = Penandatangan::create(['nama' => 'Dr. Contoh', 'jabatan' => 'Wakil Rektor I', 'is_active' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/laporan', $this->formLaporan($p))->assertRedirect();

        $surat = SuratLaporan::firstOrFail();
        $this->assertSame('BEASISWA KABUPATEN BALANGAN', $surat->beasiswa_nama);
        $this->assertSame('Genap 2025/2026', $surat->sampai_tahun_akademik_nama);
        $this->assertSame(3.65, $surat->snapshot['penerima'][0]['ipk']);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'sampaiTahunAkademikId=t2'));

        $this->actingAs($admin)->get("/admin/laporan/{$surat->uuid}")->assertOk()->assertSee('Muhammad Wildan Amin')->assertSee('3,65');

        $res = $this->actingAs($admin)->get("/admin/laporan/{$surat->uuid}/pdf");
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_pdf_memakai_salinan_bukan_data_siakad_terbaru(): void
    {
        Http::fake(['*' => Http::response($this->laporanApi())]);
        $p = Penandatangan::create(['nama' => 'Dr. Contoh', 'jabatan' => 'Wakil Rektor I', 'is_active' => true]);
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/laporan', $this->formLaporan($p));
        $surat = SuratLaporan::firstOrFail();

        Http::fake(['*' => Http::response(['message' => 'down'], 500)]); // SIAKAD mati: PDF tetap harus jadi
        $this->actingAs($admin)->get("/admin/laporan/{$surat->uuid}/pdf")->assertOk();
        Http::assertNothingSent();
    }

    public function test_tanpa_penerima_laporan_tidak_dibuat(): void
    {
        Http::fake(['*' => Http::response(['beasiswa' => ['nama' => 'X'], 'sampaiTahunAkademik' => ['nama' => 'Y'], 'penerima' => []])]);
        $p = Penandatangan::create(['nama' => 'A', 'jabatan' => 'B', 'is_active' => true]);

        $this->actingAs($this->admin())->post('/admin/laporan', $this->formLaporan($p))->assertSessionHasErrors('beasiswa_id');
        $this->assertDatabaseCount('surat_laporan', 0);
    }

    public function test_siakad_gagal_dijawab_pesan_ramah(): void
    {
        Http::fake(['*' => Http::response(['code' => 'X'], 500)]);
        $p = Penandatangan::create(['nama' => 'A', 'jabatan' => 'B', 'is_active' => true]);

        $this->actingAs($this->admin())->post('/admin/laporan', $this->formLaporan($p))
            ->assertSessionHasErrors(['beasiswa_id' => 'SIAKAD tidak dapat dihubungi. Coba lagi beberapa saat.']);
    }

    public function test_hijriah_manual_dipakai_bila_diisi(): void
    {
        Http::fake(['*' => Http::response($this->laporanApi())]);
        $p = Penandatangan::create(['nama' => 'A', 'jabatan' => 'B', 'is_active' => true]);
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/laporan', $this->formLaporan($p, ['tanggal_hijriah' => '20 Rabiul Awal 1448 H']));

        $this->actingAs($admin)->get('/admin/laporan/'.SuratLaporan::firstOrFail()->uuid)->assertSee('20 Rabiul Awal 1448 H');
    }

    public function test_beranda_admin_tetap_tampil_saat_siakad_mati(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->actingAs($this->admin())->get('/admin')->assertOk()->assertSee('SIAKAD belum dapat dihubungi');
    }

    public function test_form_laporan_terisi_dan_satu_daftar_gagal_tidak_mengosongkan_yang_lain(): void
    {
        Http::fake([
            '*/api/integrasi/beasiswa' => Http::response([['id' => 'b1', 'nama' => 'BEASISWA UJI', 'status' => 'AKTIF', 'jumlahPenerima' => 3]]),
            '*/api/integrasi/tahun-akademik' => Http::response([['id' => 't2', 'nama' => 'Genap 2025/2026', 'kode' => '20252', 'aktif' => false]]),
            '*/api/integrasi/prodi' => Http::response(['message' => 'galat'], 500),
        ]);
        Penandatangan::create(['nama' => 'Dr. Contoh', 'jabatan' => 'Wakil Rektor I', 'is_active' => true]);

        $this->actingAs($this->admin())->get('/admin/laporan/create')
            ->assertOk()
            ->assertSee('BEASISWA UJI')
            ->assertSee('Genap 2025/2026')
            ->assertSee('Sebagian data dari SIAKAD tidak dapat dimuat')
            ->assertSee('Muat ulang');
    }

    public function test_semua_penandatangan_aktif_bisa_dipilih_dan_nama_koordinator_transkrip_dari_siakad(): void
    {
        Http::fake([
            '*/api/integrasi/beasiswa' => Http::response([]),
            '*/api/integrasi/tahun-akademik' => Http::response([]),
            '*/api/integrasi/prodi' => Http::response([]),
        ]);
        Penandatangan::create(['nama' => 'Dr. Lama', 'jabatan' => 'Kaprodi', 'prodi_id' => 'p-1', 'is_active' => true]);
        Penandatangan::create(['nama' => 'Dr. Nonaktif', 'jabatan' => 'X', 'is_active' => false]);

        $this->actingAs($this->admin())->get('/admin/laporan/create')
            ->assertOk()
            ->assertSee('Dr. Lama — Kaprodi', false)
            ->assertDontSee('Dr. Nonaktif');
    }

    public function test_alamat_laporan_dan_penandatangan_memakai_uuid_bukan_nomor_urut(): void
    {
        $admin = $this->admin();
        $p = Penandatangan::create(['nama' => 'A', 'jabatan' => 'B', 'is_active' => true]);
        $surat = SuratLaporan::create([
            'dibuat_oleh' => $admin->id, 'penandatangan_id' => $p->id, 'beasiswa_id' => 'b1', 'beasiswa_nama' => 'B',
            'sampai_tahun_akademik_id' => 't1', 'sampai_tahun_akademik_nama' => 'Genap 2025/2026', 'nomor_surat' => '1',
            'tanggal_surat' => '2026-08-14', 'tempat_surat' => 'Bogor', 'perihal' => 'P', 'snapshot' => ['penerima' => []],
        ]);

        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $surat->uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $p->uuid);

        $this->actingAs($admin)->get('/admin/laporan/'.$surat->id)->assertNotFound();
        $this->actingAs($admin)->get('/admin/laporan/'.$surat->id.'/pdf')->assertNotFound();
        $this->actingAs($admin)->get('/admin/penandatangan/'.$p->id.'/ttd')->assertNotFound();
        $this->actingAs($admin)->get('/admin/laporan/'.$surat->uuid)->assertOk();
    }

    public function test_urutan_mahasiswa_dan_matakuliah_mengikuti_abjad_nama(): void
    {
        $mk = fn ($nama) => ['kode' => 'X', 'nama' => $nama, 'sks' => 2, 'nilai' => 'A', 'bobot' => 4.0, 'mutu' => 8.0];
        $penerima = fn ($nama, $nim, $smt) => ['nim' => $nim, 'nama' => $nama, 'prodiNama' => 'MBS', 'angkatan' => 2025, 'semester' => $smt, 'totalSks' => 2, 'totalMutu' => 8.0, 'ipk' => 4.0];

        Http::fake(['*/laporan-akademik*' => Http::response([
            'beasiswa' => ['nama' => 'B'], 'sampaiTahunAkademik' => ['nama' => 'Genap 2025/2026'],
            'penerima' => [
                $penerima('Syahida', '2', [
                    ['semesterKe' => 2, 'matakuliah' => [$mk('B')], 'totalSks' => 2, 'totalMutu' => 8, 'ips' => 4],
                    ['semesterKe' => 1, 'matakuliah' => [$mk('Pengantar Ekonomi'), $mk('Bahasa Arab'), $mk('etika')], 'totalSks' => 6, 'totalMutu' => 24, 'ips' => 4],
                ]),
                $penerima('Muhammad Wildan', '1', []),
            ],
        ])]);
        $p = Penandatangan::create(['nama' => 'A', 'jabatan' => 'B', 'is_active' => true]);

        $this->actingAs($this->admin())->post('/admin/laporan', $this->formLaporan($p))->assertSessionHasNoErrors();

        $snap = SuratLaporan::firstOrFail()->snapshot['penerima'];
        $this->assertSame(['Muhammad Wildan', 'Syahida'], array_column($snap, 'nama'));
        $this->assertSame([1, 2], array_column($snap[1]['semester'], 'semesterKe'));
        $this->assertSame(['Bahasa Arab', 'etika', 'Pengantar Ekonomi'], array_column($snap[1]['semester'][0]['matakuliah'], 'nama'));
    }

    public function test_pdf_berisi_surat_lampiran_dan_satu_transkrip_per_mahasiswa(): void
    {
        Http::fake(['*' => Http::response($this->laporanApi())]); // satu mahasiswa
        $p = Penandatangan::create(['nama' => 'Dr. Contoh', 'jabatan' => 'Wakil Rektor I', 'nidn' => '123', 'nik' => '456', 'is_active' => true]);
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/laporan', $this->formLaporan($p));
        $surat = SuratLaporan::firstOrFail();

        $pdf = $this->actingAs($admin)->get("/admin/laporan/{$surat->uuid}/pdf")->getContent();

        $this->assertSame(3, preg_match_all('#/Type\s*/Page(?!s)#', $pdf), 'surat + lampiran + 1 transkrip');
    }

    public function test_hijriah_bawaan_memakai_koreksi_dari_konfigurasi(): void
    {
        $this->assertSame(1, config('surat.koreksi_hijriah'));
        // 14 Agustus 2026 = 1 Rabi’ul Awal 1448 H pada surat contoh (hitungan sipil memberi 30 Safar).
        $this->assertSame('1 Rabi’ul Awal 1448 H', \App\Support\Hijri::format(\Illuminate\Support\Carbon::create(2026, 8, 14)->addDays(config('surat.koreksi_hijriah'))));
    }
}
