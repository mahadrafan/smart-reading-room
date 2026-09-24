<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\EbookController as UserEbookController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\BukuController;
use App\Http\Controllers\Admin\EbookController as AdminEbookController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\PeminjamController as AdminPeminjamController;
use App\Http\Controllers\Admin\KategoriPenulisController;
use App\Http\Controllers\Admin\PeminjamanAdminController;
use App\Http\Middleware\CekRole;
use Illuminate\Support\Facades\Route;

// halaman awal -> ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// ---------- login: satu halaman untuk admin dan peminjam ----------
Route::get('/login', [AuthController::class, 'formLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.proses');

// ---------- forgot password / reset password ----------
Route::get('/forgot-password', [AuthController::class, 'formLupaPassword'])->name('password.forgot');
Route::post('/forgot-password', [AuthController::class, 'prosesLupaPassword'])->name('password.proses');

// ---------- daftar akun (selalu jadi Peminjam) ----------
Route::get('/register', [AuthController::class, 'formRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.proses');

// ---------- logout ----------
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// =================================================================
// HALAMAN PEMINJAM (harus login sebagai Peminjam)
// =================================================================
Route::middleware(CekRole::class . ':Peminjam')->group(function () {

    // landing page: katalog buku
    Route::get('/dashboard', [KatalogController::class, 'index'])->name('dashboard');
    Route::get('/buku/{id}', [KatalogController::class, 'show'])->name('buku.detail');
    Route::post('/buku/{id}/ulasan', [ReviewController::class, 'store'])->name('ulasan.simpan');

    Route::get('/ebooks', [UserEbookController::class, 'index'])->name('ebooks.index');
    Route::get('/ebooks/{id}/download', [UserEbookController::class, 'download'])->name('ebooks.download');
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');

    // peminjaman (urutan penting: /peminjaman/buat harus di atas /peminjaman/{id})
    Route::get('/peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::get('/peminjaman/buat', [PeminjamanController::class, 'create'])->name('peminjaman.buat');
    Route::post('/peminjaman', [PeminjamanController::class, 'store'])->name('peminjaman.simpan');
    Route::get('/peminjaman/{id}', [PeminjamanController::class, 'show'])->name('peminjaman.detail');
    Route::delete('/peminjaman/{id}', [PeminjamanController::class, 'destroy'])->name('peminjaman.batal');

    // profil
    Route::get('/profil', [ProfilController::class, 'show'])->name('profil');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password');
});

// =================================================================
// HALAMAN ADMIN (harus login sebagai Admin)
// =================================================================
Route::middleware(CekRole::class . ':Admin')->prefix('admin')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');

    // Kategori & Penulis (satu halaman gabungan)
    Route::get('/kategori-penulis', [KategoriPenulisController::class, 'index'])->name('admin.kp.index');
    Route::post('/kategori-penulis/kategori', [KategoriPenulisController::class, 'simpanKategori'])->name('admin.kp.kategori.store');
    Route::put('/kategori-penulis/kategori/{id}', [KategoriPenulisController::class, 'updateKategori'])->name('admin.kp.kategori.update');
    Route::delete('/kategori-penulis/kategori/{id}', [KategoriPenulisController::class, 'hapusKategori'])->name('admin.kp.kategori.destroy');
    Route::post('/kategori-penulis/penulis', [KategoriPenulisController::class, 'simpanPenulis'])->name('admin.kp.penulis.store');
    Route::put('/kategori-penulis/penulis/{id}', [KategoriPenulisController::class, 'updatePenulis'])->name('admin.kp.penulis.update');
    Route::delete('/kategori-penulis/penulis/{id}', [KategoriPenulisController::class, 'hapusPenulis'])->name('admin.kp.penulis.destroy');

    Route::get('/peminjam', [AdminPeminjamController::class, 'index'])->name('admin.peminjam.index');
    Route::get('/ulasan', [AdminReviewController::class, 'index'])->name('admin.reviews.index');
    Route::delete('/ulasan/{id}', [AdminReviewController::class, 'destroy'])->name('admin.reviews.destroy');
    Route::get('/ebooks', [AdminEbookController::class, 'index'])->name('admin.ebooks.index');
    Route::get('/ebooks/tambah', [AdminEbookController::class, 'create'])->name('admin.ebooks.create');
    Route::post('/ebooks', [AdminEbookController::class, 'store'])->name('admin.ebooks.store');
    Route::delete('/ebooks/{id}', [AdminEbookController::class, 'destroy'])->name('admin.ebooks.destroy');

    // Kelola Buku
    Route::resource('buku', BukuController::class)
        ->except(['show'])
        ->names('admin.buku')
        ->parameters(['buku' => 'id']);

    // Peminjaman: Request Masuk + Riwayat
    Route::get('/peminjaman', [PeminjamanAdminController::class, 'index'])->name('admin.peminjaman.index');
    Route::put('/peminjaman/{id}/setujui', [PeminjamanAdminController::class, 'setujui'])->name('admin.peminjaman.setujui');
    Route::put('/peminjaman/{id}/tolak', [PeminjamanAdminController::class, 'tolak'])->name('admin.peminjaman.tolak');
    Route::get('/riwayat', [PeminjamanAdminController::class, 'riwayat'])->name('admin.peminjaman.riwayat');
    Route::put('/riwayat/{id}/dipinjam', [PeminjamanAdminController::class, 'dipinjam'])->name('admin.peminjaman.dipinjam');
    Route::put('/riwayat/{id}/kembalikan', [PeminjamanAdminController::class, 'kembalikan'])->name('admin.peminjaman.kembalikan');
    Route::put('/riwayat/{id}/denda', [PeminjamanAdminController::class, 'ubahStatusDenda'])->name('admin.peminjaman.denda');
    Route::delete('/riwayat/{id}', [PeminjamanAdminController::class, 'destroy'])->name('admin.peminjaman.destroy');
});
