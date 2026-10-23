<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * Masuk dengan Google. Google hanya membuktikan pemilik email; hak akses tetap dari peran di aplikasi ini.
 *
 * - Akun staf (admin/akademik) dibuat oleh admin; email yang sama dengan akun Google ditautkan otomatis.
 * - Orang tua baru yang masuk lewat Google dibuatkan akun orang tua (tanpa kata sandi yang bisa ditebak).
 * - Akun yang dibuat lewat formulir (email belum terverifikasi) TIDAK ditautkan otomatis. Kalau ditautkan,
 *   orang lain yang lebih dulu mendaftar dengan email korban masih memegang kata sandinya.
 */
class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        abort_unless(self::aktif(), 404);

        return Socialite::driver('google')->scopes(['openid', 'profile', 'email'])->redirect();
    }

    public function callback(): RedirectResponse
    {
        abort_unless(self::aktif(), 404);

        try {
            $g = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Login Google gagal: '.$e::class);

            return $this->tolak('Masuk dengan Google dibatalkan atau gagal. Silakan coba lagi.');
        }

        $email = Str::lower((string) $g->getEmail());
        $terverifikasi = filter_var($g->user['email_verified'] ?? $g->user['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($email === '' || ! $terverifikasi) {
            return $this->tolak('Email Google Anda belum terverifikasi, jadi tidak bisa dipakai untuk masuk.');
        }

        $user = User::where('google_id', $g->getId())->first() ?? User::where('email', $email)->first();

        if ($user) {
            $punyaGoogle = $user->google_id === $g->getId();
            $dipercaya = $punyaGoogle || $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_AKADEMIK]) || $user->email_verified_at !== null;

            if (! $dipercaya) {
                return $this->tolak('Email ini sudah terdaftar dengan kata sandi. Masuklah dengan email dan kata sandi.');
            }
            if ($user->google_id !== null && ! $punyaGoogle) {
                return $this->tolak('Email ini sudah tertaut ke akun Google lain.');
            }
            $user->forceFill(['google_id' => $g->getId(), 'email_verified_at' => $user->email_verified_at ?? now()])->save();
        } else {
            $user = User::create(['name' => Str::limit($g->getName() ?: Str::before($email, '@'), 255, ''), 'email' => $email, 'password' => Str::random(48), 'is_active' => true]);
            $user->forceFill(['google_id' => $g->getId(), 'email_verified_at' => now()])->save();
            $user->assignRole(User::ROLE_ORANGTUA);
            event(new Registered($user));
        }

        if (! $user->is_active) {
            return $this->tolak('Akun Anda dinonaktifkan. Hubungi bagian akademik.');
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /** Tombol Google hanya tampil bila kredensialnya sudah diisi. */
    public static function aktif(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    private function tolak(string $pesan): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $pesan]);
    }
}
