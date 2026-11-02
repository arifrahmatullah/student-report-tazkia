<?php

use App\Http\Controllers\Admin\BerandaController as AdminBerandaController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PenandatanganController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Akademik\BerandaController as AkademikBerandaController;
use App\Http\Controllers\Akademik\LogWaController;
use App\Http\Controllers\Akademik\PesanController as AkademikPesanController;
use App\Http\Controllers\Akademik\ReminderKrsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Orangtua\AnakController;
use App\Http\Controllers\Orangtua\KlaimController;
use App\Http\Controllers\Orangtua\PesanController as OrangtuaPesanController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('beranda');

// Mengarahkan ke beranda sesuai role.
Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:orangtua'])->prefix('orangtua')->name('orangtua.')->group(function () {
    Route::get('/', [AnakController::class, 'index'])->name('beranda');
    Route::get('/klaim', [KlaimController::class, 'create'])->name('klaim.create');
    Route::post('/klaim', [KlaimController::class, 'store'])->name('klaim.store');
    Route::get('/anak/{nim}', [AnakController::class, 'show'])->where('nim', '[0-9]+')->name('anak.show');
    Route::delete('/anak/{nim}', [AnakController::class, 'destroy'])->where('nim', '[0-9]+')->name('anak.destroy');
});

Route::middleware(['auth', 'role:orangtua'])->prefix('orangtua/pesan')->name('orangtua.pesan.')->group(function () {
    Route::get('/', [OrangtuaPesanController::class, 'index'])->name('index');
    Route::get('/{penerima}', [OrangtuaPesanController::class, 'show'])->whereNumber('penerima')->name('show');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminBerandaController::class)->name('beranda');

    Route::resource('laporan', LaporanController::class)->only(['index', 'create', 'store', 'show', 'destroy'])
        ->parameters(['laporan' => 'laporan']);
    Route::get('laporan/{laporan}/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');

    Route::resource('penandatangan', PenandatanganController::class)->except(['show'])
        ->parameters(['penandatangan' => 'penandatangan']);
    Route::get('penandatangan/{penandatangan}/ttd', [PenandatanganController::class, 'ttd'])->name('penandatangan.ttd');

    Route::resource('pengguna', PenggunaController::class)->only(['index', 'create', 'store', 'edit', 'update'])
        ->parameters(['pengguna' => 'pengguna']);
});

Route::middleware(['auth', 'role:akademik|admin'])->prefix('akademik')->name('akademik.')->group(function () {
    Route::get('/', AkademikBerandaController::class)->name('beranda');

    Route::get('reminder-krs', [ReminderKrsController::class, 'index'])->name('reminder-krs.index');
    Route::get('reminder-krs/kontak', [ReminderKrsController::class, 'kontak'])->name('reminder-krs.kontak');
    Route::post('reminder-krs/tes', [ReminderKrsController::class, 'tes'])->name('reminder-krs.tes');
    Route::post('reminder-krs/kirim', [ReminderKrsController::class, 'kirim'])->name('reminder-krs.kirim');
    Route::get('log-wa', [LogWaController::class, 'index'])->name('log-wa.index');

    Route::get('pesan/estimasi', [AkademikPesanController::class, 'estimasi'])->name('pesan.estimasi');
    Route::resource('pesan', AkademikPesanController::class)->only(['index', 'create', 'store', 'show'])
        ->parameters(['pesan' => 'pesan']);
});

require __DIR__.'/auth.php';
