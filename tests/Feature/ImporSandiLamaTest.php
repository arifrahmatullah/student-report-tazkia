<?php

namespace Tests\Feature;

use App\Console\Commands\ImporSandiLama;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImporSandiLamaTest extends TestCase
{
    use RefreshDatabase;

    /** Hash seperti buatan Spring: awalan $2a$ (isi BCrypt sama persis dengan $2y$). */
    private function hashSpring(string $sandi): string
    {
        return '$2a$'.substr(password_hash($sandi, PASSWORD_BCRYPT, ['cost' => 10]), 4);
    }

    private function csv(array $baris): string
    {
        $p = tempnam(sys_get_temp_dir(), 'sandi');
        $f = fopen($p, 'w');
        fputcsv($f, ['id', 'email_orang_tua', 'password'], ',', '"', '');
        foreach ($baris as $b) {
            fputcsv($f, $b, ',', '"', '');
        }
        fclose($f);

        return $p;
    }

    private function ortu(string $email): User
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_AKADEMIK, User::ROLE_ORANGTUA] as $r) {
            Role::findOrCreate($r);
        }
        $u = User::factory()->create(['email' => $email, 'email_verified_at' => now()]);
        $u->assignRole(User::ROLE_ORANGTUA);

        return $u;
    }

    public function test_konversi_awalan_hash_spring_ke_laravel(): void
    {
        $asli = $this->hashSpring('apa-saja');
        $this->assertStringStartsWith('$2a$', $asli);
        $this->assertSame(60, strlen($asli));

        $this->assertSame('$2y$'.substr($asli, 4), ImporSandiLama::keBcryptLaravel($asli));
        $this->assertNull(ImporSandiLama::keBcryptLaravel('bukan-hash'));
        $this->assertNull(ImporSandiLama::keBcryptLaravel('NULL'));
        $this->assertNull(ImporSandiLama::keBcryptLaravel(''));
    }

    public function test_orang_tua_bisa_login_dengan_sandi_lama_setelah_impor(): void
    {
        $u = $this->ortu('ayah@gmail.com');
        $berkas = $this->csv([['1', 'Ayah@Gmail.com', $this->hashSpring('SandiLamaAyah#1')]]);

        $this->artisan('sandi:impor-lama', ['berkas' => $berkas])->assertSuccessful();

        $this->assertTrue(Hash::check('SandiLamaAyah#1', $u->fresh()->password));
        $this->post('/login', ['email' => 'ayah@gmail.com', 'password' => 'SandiLamaAyah#1'])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($u);
        @unlink($berkas);
    }

    public function test_sandi_salah_tetap_ditolak(): void
    {
        $this->ortu('ibu@gmail.com');
        $berkas = $this->csv([['1', 'ibu@gmail.com', $this->hashSpring('benar-123')]]);
        $this->artisan('sandi:impor-lama', ['berkas' => $berkas])->assertSuccessful();

        $this->post('/login', ['email' => 'ibu@gmail.com', 'password' => 'salah-123'])->assertSessionHasErrors('email');
        $this->assertGuest();
        @unlink($berkas);
    }

    public function test_dry_run_tidak_menulis_apa_pun(): void
    {
        $u = $this->ortu('wali@gmail.com');
        $sebelum = $u->password;
        $berkas = $this->csv([['1', 'wali@gmail.com', $this->hashSpring('rahasia-1')]]);

        $this->artisan('sandi:impor-lama', ['berkas' => $berkas, '--dry-run' => true])->assertSuccessful();

        $this->assertSame($sebelum, $u->fresh()->password);
        @unlink($berkas);
    }

    public function test_akun_staf_tidak_pernah_diubah_dan_hash_rusak_dilewati(): void
    {
        $x = $this->ortu('x@gmail.com');
        $sandiX = $x->password;
        $staf = User::factory()->create(['email' => 'staf@tazkia.ac.id']);
        $staf->assignRole(User::ROLE_ADMIN);
        $sebelum = $staf->password;
        $berkas = $this->csv([
            ['1', 'staf@tazkia.ac.id', $this->hashSpring('coba-ganti-1')],
            ['2', 'x@gmail.com', 'NULL'],
            ['3', 'x@gmail.com', 'bukan-bcrypt'],
        ]);

        $this->artisan('sandi:impor-lama', ['berkas' => $berkas])->assertSuccessful();

        $this->assertSame($sebelum, $staf->fresh()->password);
        $this->assertFalse(Hash::check('coba-ganti-1', $staf->fresh()->password));
        $this->assertSame($sandiX, $x->fresh()->password);
        @unlink($berkas);
    }

    public function test_email_dengan_dua_hash_berbeda_tidak_dipakai(): void
    {
        $u = $this->ortu('dua@gmail.com');
        $sebelum = $u->password;
        $berkas = $this->csv([['1', 'dua@gmail.com', $this->hashSpring('satu-123')], ['2', 'dua@gmail.com', $this->hashSpring('dua-456')]]);

        $this->artisan('sandi:impor-lama', ['berkas' => $berkas])->assertSuccessful();

        $this->assertSame($sebelum, $u->fresh()->password);
        @unlink($berkas);
    }

    public function test_berkas_tanpa_kolom_yang_dibutuhkan_gagal(): void
    {
        $p = tempnam(sys_get_temp_dir(), 'sandi');
        file_put_contents($p, "a,b\n1,2\n");

        $this->artisan('sandi:impor-lama', ['berkas' => $p])->assertFailed();
        @unlink($p);
    }
}
