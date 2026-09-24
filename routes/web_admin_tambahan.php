// ============================================================
// TAMBAHKAN blok ini di dalam grup middleware admin yang sudah
// ada di routes/web.php (yang berisi CekRole::class . ':Admin'),
// sebelum baris penutup });
// ============================================================

use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\PenulisController;

Route::resource('kategori', KategoriController::class)
    ->names('admin.kategori')
    ->parameters(['kategori' => 'id']);

Route::resource('penulis', PenulisController::class)
    ->names('admin.penulis')
    ->parameters(['penulis' => 'id']);
