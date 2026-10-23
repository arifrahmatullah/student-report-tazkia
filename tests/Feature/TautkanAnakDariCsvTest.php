<?php

namespace Tests\Feature;

use App\Models\ParentMahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TautkanAnakDariCsvTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([User::ROLE_ADMIN, User::ROLE_AKADEMIK, User::ROLE_ORANGTUA] as $r) {
            Role::findOrCreate($r);
        }
        config(['siakad.api_key' => 'kunci-uji', 'siakad.base_url' => 'https://siakad.test']);
        Http::fake([
            '*/mahasiswa/100001*' => Http::response(['id' => 'sid-1', 'nim' => '100001', 'nama' => 'Ani Lestari', 'status' => 'AKTIF']),
            '*/mahasiswa/100002*' => Http::response(['id' => 'sid-2', 'nim' => '100002', 'nama' => 'Budi Santoso', 'status' => 'AKTIF']),
            '*/mahasiswa/100003*' => Http::response(['id' => 'sid-3', 'nim' => '100003', 'nama' => 'Citra Dewi', 'status' => 'AKTIF']),
            '*/mahasiswa/999999*' => Http::response(['message' => 'tidak ada'], 404),
            '*/mahasiswa/*' => Http::response(['id' => 'sid-x', 'nim' => '0', 'nama' => 'Lain', 'status' => 'AKTIF']),
        ]);
    }

    private function ortu(string $email): User
    {
        $u = User::factory()->create(['email' => $email]);
        $u->assignRole(User::ROLE_ORANGTUA);

        return $u;
    }

    private function csv(array $baris): string
    {
        $p = tempnam(sys_get_temp_dir(), 'anak');
        $f = fopen($p, 'w');
        fputcsv($f, ['id', 'email_orang_tua', 'nim_mahasiswa', 'nama_mahasiswa'], ',', '"', '');
        foreach ($baris as $b) {
            fputcsv($f, $b, ',', '"', '');
        }
        fclose($f);

        return $p;
    }

    public function test_anak_ditautkan_dengan_data_dari_siakad_dan_duplikat_baris_diabaikan(): void
    {
        $u = $this->ortu('ibu@gmail.com');
        $berkas = $this->csv([['1', 'Ibu@Gmail.com', '100001', 'x'], ['1', 'ibu@gmail.com', '100001', 'x'], ['2', 'ibu@gmail.com', '100002', 'y']]);

        $this->artisan('anak:tautkan-dari-csv', ['berkas' => [$berkas]])->assertSuccessful();

        $this->assertSame(['100001', '100002'], $u->anak()->orderBy('nim')->pluck('nim')->all());
        $a = $u->anak()->where('nim', '100001')->first();
        $this->assertSame('Ani Lestari', $a->nama_mahasiswa);
        $this->assertSame('sid-1', $a->siakad_mahasiswa_id);
        @unlink($berkas);
    }

    public function test_tautan_membuat_orang_tua_bisa_membuka_halaman_anak(): void
    {
        $u = $this->ortu('ayah@gmail.com');
        $berkas = $this->csv([['1', 'ayah@gmail.com', '100001', 'x']]);
        $this->artisan('anak:tautkan-dari-csv', ['berkas' => [$berkas]])->assertSuccessful();

        $this->actingAs($u)->get('/orangtua')->assertOk()->assertSee('Ani Lestari');
        @unlink($berkas);
    }

    public function test_dry_run_tidak_menulis(): void
    {
        $u = $this->ortu('wali@gmail.com');
        $berkas = $this->csv([['1', 'wali@gmail.com', '100001', 'x']]);

        $this->artisan('anak:tautkan-dari-csv', ['berkas' => [$berkas], '--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, $u->anak()->count());
        @unlink($berkas);
    }

    public function test_nim_yang_tidak_ada_di_siakad_dilewati(): void
    {
        $u = $this->ortu('ibu@gmail.com');
        $berkas = $this->csv([['1', 'ibu@gmail.com', '999999', 'x'], ['1', 'ibu@gmail.com', '100001', 'x']]);

        $this->artisan('anak:tautkan-dari-csv', ['berkas' => [$berkas]])->assertSuccessful();

        $this->assertSame(['100001'], $u->anak()->pluck('nim')->all());
        @unlink($berkas);
    }

    public function test_akun_dengan_terlalu_banyak_anak_dilewati(): void
    {
        $u = $this->ortu('banyak@gmail.com');
        $baris = [];
        foreach (range(1, 6) as $i) {
            $baris[] = ['1', 'banyak@gmail.com', '30000'.$i, 'x'];
        }
        $berkas = $this->csv($baris);

        $this->artisan('anak:tautkan-dari-csv', ['berkas' => [$berkas]])->expectsOutputToContain('terlalu banyak anak (6)')->assertSuccessful();

        $this->assertSame(0, $u->anak()->count());
        @unlink($berkas);
    }

    public function test_batas_tiga_akun_per_mahasiswa_dihormati(): void
    {
        $akun = [$this->ortu('a@gmail.com'), $this->ortu('b@gmail.com'), $this->ortu('c@gmail.com'), $this->ortu('d@gmail.com')];
        $berkas = $this->csv([['1', 'a@gmail.com', '100003', 'x'], ['2', 'b@gmail.com', '100003', 'x'], ['3', 'c@gmail.com', '100003', 'x'], ['4', 'd@gmail.com', '100003', 'x']]);

        $this->artisan('anak:tautkan-dari-csv', ['berkas' => [$berkas]])->assertSuccessful();

        $this->assertSame(3, ParentMahasiswa::where('nim', '100003')->count());
        @unlink($berkas);
    }

    public function test_dijalankan_dua_kali_tidak_menggandakan_dan_staf_tidak_disentuh(): void
    {
        $u = $this->ortu('ibu@gmail.com');
        $staf = User::factory()->create(['email' => 'staf@tazkia.ac.id']);
        $staf->assignRole(User::ROLE_ADMIN);
        $berkas = $this->csv([['1', 'ibu@gmail.com', '100001', 'x'], ['2', 'staf@tazkia.ac.id', '100002', 'y']]);

        $this->artisan('anak:tautkan-dari-csv', ['berkas' => [$berkas]])->assertSuccessful();
        $this->artisan('anak:tautkan-dari-csv', ['berkas' => [$berkas]])->assertSuccessful();

        $this->assertSame(1, $u->anak()->count());
        $this->assertSame(0, $staf->anak()->count());
        @unlink($berkas);
    }

    public function test_gabungan_dua_berkas_dan_kolom_hilang_gagal_dengan_jelas(): void
    {
        $u = $this->ortu('ibu@gmail.com');
        $b1 = $this->csv([['1', 'ibu@gmail.com', '100001', 'x']]);
        $b2 = $this->csv([['2', 'ibu@gmail.com', '100002', 'y']]);

        $this->artisan('anak:tautkan-dari-csv', ['berkas' => [$b1, $b2]])->assertSuccessful();
        $this->assertSame(2, $u->anak()->count());

        $rusak = tempnam(sys_get_temp_dir(), 'anak');
        file_put_contents($rusak, "a,b\n1,2\n");
        $this->artisan('anak:tautkan-dari-csv', ['berkas' => [$rusak]])->assertFailed();
        @unlink($b1);
        @unlink($b2);
        @unlink($rusak);
    }
}
