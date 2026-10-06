<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\BukuController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\PeminjamController as AdminPeminjamController;
use App\Http\Controllers\Admin\PeminjamanAdminController;
use App\Http\Controllers\Admin\KategoriPenulisController;
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

    // Review & Ulasan: semua ulasan, edit & hapus ulasan milik sendiri
    Route::get('/ulasan', [ReviewController::class, 'index'])->name('ulasan.index');
    Route::put('/ulasan/{id}', [ReviewController::class, 'update'])->name('ulasan.update');
    Route::delete('/ulasan/{id}', [ReviewController::class, 'destroy'])->name('ulasan.hapus');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/dibaca', [NotificationController::class, 'tandaiDibaca'])->name('notifikasi.dibaca');

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

    Route::get('/peminjam', [AdminPeminjamController::class, 'index'])->name('admin.peminjam.index');
    Route::get('/ulasan', [AdminReviewController::class, 'index'])->name('admin.reviews.index');
    Route::delete('/ulasan/{id}', [AdminReviewController::class, 'destroy'])->name('admin.reviews.destroy');

    // Kelola Buku: tab Nonaktif (buku yang dihapus dari katalog) + aktifkan kembali / hapus permanen
    Route::get('/buku/nonaktif', [BukuController::class, 'nonaktif'])->name('admin.buku.nonaktif');
    Route::put('/buku/{id}/aktifkan', [BukuController::class, 'aktifkan'])->name('admin.buku.aktifkan');
    Route::delete('/buku/{id}/permanen', [BukuController::class, 'hapusPermanen'])->name('admin.buku.hapus-permanen');

    // Kelola Buku: tab Kategori & Penulis (lihat, tambah, edit, hapus bila tidak dipakai buku)
    Route::get('/buku/kategori', [KategoriPenulisController::class, 'kategori'])->name('admin.buku.kategori.index');
    Route::post('/buku/kategori', [KategoriPenulisController::class, 'tambahKategori'])->name('admin.buku.kategori.store');
    Route::put('/buku/kategori/{id}', [KategoriPenulisController::class, 'updateKategori'])->name('admin.buku.kategori.update');
    Route::delete('/buku/kategori/{id}', [KategoriPenulisController::class, 'hapusKategori'])->name('admin.buku.kategori.destroy');
    Route::get('/buku/penulis', [KategoriPenulisController::class, 'penulis'])->name('admin.buku.penulis.index');
    Route::post('/buku/penulis', [KategoriPenulisController::class, 'tambahPenulis'])->name('admin.buku.penulis.store');
    Route::put('/buku/penulis/{id}', [KategoriPenulisController::class, 'updatePenulis'])->name('admin.buku.penulis.update');
    Route::delete('/buku/penulis/{id}', [KategoriPenulisController::class, 'hapusPenulis'])->name('admin.buku.penulis.destroy');

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
