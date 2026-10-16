<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KlaimAnakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['siakad.api_key' => 'kunci-uji', 'siakad.base_url' => 'https://siakad.test']);
        foreach (['admin', 'akademik', 'orangtua'] as $r) {
            Role::findOrCreate($r);
        }
        RateLimiter::clear('klaim:ip:127.0.0.1');
    }

    private function orangtua(): User
    {
        return User::factory()->create()->assignRole('orangtua');
    }

    public function test_klaim_berhasil_menyimpan_nim_dan_tidak_menyimpan_tanggal_lahir(): void
    {
        Http::fake(['siakad.test/api/integrasi/mahasiswa/2510101078/verifikasi*' => Http::response([
            'cocok' => true, 'id' => 'abc', 'nim' => '2510101078', 'nama' => 'Wildan', 'ipk' => 3.3, 'totalSks' => 39,
        ])]);

        $user = $this->orangtua();
        $this->actingAs($user)
            ->post('/orangtua/klaim', ['nim' => '2510101078', 'tanggal_lahir' => '2005-03-21'])
            ->assertRedirect('/orangtua/anak/2510101078');

        $this->assertDatabaseHas('parent_mahasiswa', ['user_id' => $user->id, 'nim' => '2510101078', 'nama_mahasiswa' => 'Wildan']);
        $this->assertStringNotContainsString('2005-03-21', json_encode(\DB::table('parent_mahasiswa')->get()));
        Http::assertSent(fn ($r) => $r->hasHeader('X-Api-Key', 'kunci-uji'));
    }

    public function test_klaim_gagal_bila_tidak_cocok(): void
    {
        Http::fake(['*' => Http::response(['cocok' => false])]);

        $this->actingAs($this->orangtua())
            ->post('/orangtua/klaim', ['nim' => '2510101078', 'tanggal_lahir' => '2000-01-01'])
            ->assertSessionHasErrors('nim');

        $this->assertDatabaseCount('parent_mahasiswa', 0);
    }

    public function test_nim_tidak_ada_dijawab_sama_dengan_tanggal_salah(): void
    {
        Http::fake(['*' => Http::response(['code' => 'MAHASISWA_NOT_FOUND'], 404)]);

        $user = $this->orangtua();
        $a = $this->actingAs($user)->post('/orangtua/klaim', ['nim' => '9999999999', 'tanggal_lahir' => '2000-01-01']);

        Http::fake(['*' => Http::response(['cocok' => false])]);
        $b = $this->actingAs($user)->post('/orangtua/klaim', ['nim' => '2510101078', 'tanggal_lahir' => '2000-01-01']);

        $a->assertSessionHasErrors(['nim' => 'NIM atau tanggal lahir tidak cocok dengan data kampus.']);
        $b->assertSessionHasErrors(['nim' => 'NIM atau tanggal lahir tidak cocok dengan data kampus.']);
    }

    public function test_dibatasi_setelah_lima_percobaan_gagal(): void
    {
        Http::fake(['*' => Http::response(['cocok' => false])]);
        $user = $this->orangtua();
        RateLimiter::clear('klaim:user:'.$user->id);

        foreach (range(1, 5) as $i) {
            $this->actingAs($user)->post('/orangtua/klaim', ['nim' => '251010107'.$i, 'tanggal_lahir' => '2000-01-01']);
        }

        $this->actingAs($user)
            ->post('/orangtua/klaim', ['nim' => '2510101078', 'tanggal_lahir' => '2000-01-01'])
            ->assertSessionHasErrors(['nim' => 'Terlalu banyak percobaan. Coba lagi dalam 10 menit.']);

        Http::assertSentCount(5);
    }

    public function test_orangtua_tidak_bisa_melihat_nim_yang_tidak_diklaim(): void
    {
        $this->actingAs($this->orangtua())->get('/orangtua/anak/2510101078')->assertNotFound();
    }

    public function test_orangtua_tidak_bisa_masuk_area_staf(): void
    {
        $this->actingAs($this->orangtua())->get('/admin')->assertForbidden();
        $this->actingAs($this->orangtua())->get('/akademik')->assertForbidden();
    }

    public function test_halaman_anak_tetap_tampil_bila_sebagian_endpoint_gagal(): void
    {
        $user = $this->orangtua();
        $user->anak()->create(['nim' => '2510101078', 'nama_mahasiswa' => 'Wildan']);

        Http::fake([
            '*/mahasiswa/2510101078' => Http::response(['nim' => '2510101078', 'nama' => 'Wildan', 'ipk' => 3.3, 'totalSks' => 39, 'status' => 'AKTIF']),
            '*/tahun-akademik/aktif' => Http::response(['id' => 't1', 'nama' => 'Ganjil 2026/2027', 'tanggalMulaiKrs' => '2026-08-24', 'tanggalSelesaiKrs' => '2026-10-03']),
            '*' => Http::response(['message' => 'galat'], 500),
        ]);

        $this->actingAs($user)->get('/orangtua/anak/2510101078')
            ->assertOk()
            ->assertSee('3,30')
            ->assertSee('Data belum tersedia saat ini.');
    }

    public function test_pesan_orang_lain_tidak_bisa_dibuka(): void
    {
        $a = $this->orangtua();
        $b = $this->orangtua();
        $pengirim = User::factory()->create()->assignRole('akademik');
        $pesan = \App\Models\Pesan::create(['pengirim_id' => $pengirim->id, 'judul' => 'J', 'isi' => '<b>x</b>', 'target' => []]);
        $baris = $pesan->penerima()->create(['nim' => '1', 'tipe' => 'ayah', 'user_id' => $a->id]);

        $this->actingAs($b)->get("/orangtua/pesan/{$baris->id}")->assertNotFound();
        $this->actingAs($a)->get("/orangtua/pesan/{$baris->id}")->assertOk()->assertDontSee('<b>x</b>', false);
        $this->assertNotNull($baris->fresh()->dibaca_pada);
    }
}
