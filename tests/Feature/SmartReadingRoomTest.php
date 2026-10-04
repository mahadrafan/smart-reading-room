<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Review;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SmartReadingRoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_login_accounts_match_readme_and_reseeding_preserves_passwords(): void
    {
        $this->seed();

        $admin = User::where('nim_nip', 'ADM001')->firstOrFail();
        $borrower = User::where('nim_nip', '2024001')->firstOrFail();

        $this->assertTrue(Hash::check('admin12345', $admin->password));
        $this->assertTrue(Hash::check('peminjam123', $borrower->password));

        $admin->update(['password' => Hash::make('password-baru')]);
        $this->seed();

        $this->assertDatabaseCount('users', 2);
        $this->assertTrue(Hash::check('password-baru', $admin->fresh()->password));
    }

    public function test_public_authentication_pages_registration_login_and_password_reset(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/forgot-password')->assertOk();

        $this->post('/register', [
            'name' => 'Siswa Uji',
            'email' => 'siswa@example.test',
            'nim_nip' => '2026999',
            'class' => 'XII IPA 1',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        $user = User::where('nim_nip', '2026999')->firstOrFail();
        $this->assertSame('Peminjam', $user->role);

        $this->post('/login', [
            'nim_nip' => '2026999',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->post('/logout')->assertRedirect(route('login'));

        $this->post('/forgot-password', [
            'nim_nip' => '2026999',
            'email' => 'siswa@example.test',
            'password' => 'password456',
            'password_confirmation' => 'password456',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('password456', $user->fresh()->password));
    }

    public function test_role_guards_and_all_main_pages(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin);

        $this->actingAs($borrower);
        $this->get('/dashboard')->assertOk();
        $this->get('/dashboard?q=Buku')->assertOk();
        $this->get('/dashboard?kategori=' . $book->category_id)->assertOk();
        $this->get('/buku/' . $book->book_id)->assertOk();
        $this->get('/peminjaman')->assertOk();
        $this->get('/peminjaman/buat')->assertOk();
        $this->get('/notifikasi')->assertOk();
        $this->get('/profil')->assertOk();
        $this->get('/admin/dashboard')->assertRedirect(route('dashboard'));

        $this->actingAs($admin);
        $this->get('/admin/dashboard')->assertOk();
        $this->get('/admin/buku')->assertOk();
        $this->get('/admin/buku/create')->assertOk();
        $this->get('/admin/peminjam')->assertOk();
        $this->get('/admin/ulasan')->assertOk();
        $this->get('/admin/peminjaman')->assertOk();
        $this->get('/admin/riwayat')->assertOk();
        $this->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    }

    public function test_borrower_can_request_cancel_and_update_profile(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin);

        $this->actingAs($borrower)->post('/peminjaman', [
            'book_id' => $book->book_id,
            'loan_date' => today()->format('Y-m-d'),
            'durasi' => '7',
        ])->assertRedirect(route('peminjaman.index'));

        $loan = Loan::firstOrFail();
        $this->assertSame('Menunggu', $loan->status);
        $this->assertSame(today()->addDays(7)->format('Y-m-d'), $loan->due_date->format('Y-m-d'));

        $this->post('/peminjaman', [
            'book_id' => $book->book_id,
            'loan_date' => today()->format('Y-m-d'),
            'durasi' => '3',
        ])->assertSessionHasErrors('book_id');

        $this->put('/profil', [
            'name' => 'Nama Diperbarui',
            'email' => 'baru@example.test',
            'class' => 'XII IPA 2',
            'phone' => '089999999999',
        ])->assertRedirect(route('profil'));

        $this->assertDatabaseHas('users', [
            'user_id' => $borrower->user_id,
            'name' => 'Nama Diperbarui',
            'email' => 'baru@example.test',
        ]);

        $this->put('/profil/password', [
            'password_lama' => 'salah',
            'password_baru' => 'password456',
            'password_baru_confirmation' => 'password456',
        ])->assertSessionHasErrors(['password_lama'], null, 'password');

        $this->put('/profil/password', [
            'password_lama' => 'password123',
            'password_baru' => 'password456',
            'password_baru_confirmation' => 'password456',
        ])->assertRedirect(route('profil'));
        $this->assertTrue(Hash::check('password456', $borrower->fresh()->password));

        $this->delete('/peminjaman/' . $loan->loan_id)->assertRedirect(route('peminjaman.index'));
        $this->assertDatabaseMissing('loans', ['loan_id' => $loan->loan_id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $borrower->user_id, 'loan_id' => null]);
    }

    public function test_admin_can_manage_categories_authors_and_books(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $this->actingAs($admin);
        Storage::fake('public');

        // kategori & penulis baru dibuat langsung dari form Kelola Buku
        $this->post('/admin/buku', [
            'category_id' => 'new',
            'new_category_name' => 'Sains',
            'author_id' => 'new',
            'new_author_name' => 'Penulis Uji',
            'title' => 'Buku Uji Fitur',
            'publisher' => 'Penerbit Uji',
            'publication_year' => 2026,
            'description' => 'Deskripsi buku uji.',
            'location' => 'Rak U-01',
            'stock' => 4,
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 300, 450),
        ])->assertRedirect(route('admin.buku.index'));

        $category = Category::where('category_name', 'Sains')->firstOrFail();
        $author = Author::where('author_name', 'Penulis Uji')->firstOrFail();
        $book = Book::where('title', 'Buku Uji Fitur')->firstOrFail();
        $this->assertSame($category->category_id, $book->category_id);
        $this->assertSame($author->author_id, $book->author_id);
        $this->assertSame(4, $book->available_stock);
        Storage::disk('public')->assertExists($book->cover_image);

        $this->put('/admin/buku/' . $book->book_id, [
            'category_id' => $category->category_id,
            'author_id' => $author->author_id,
            'title' => 'Buku Uji Diperbarui',
            'publisher' => 'Penerbit Uji',
            'publication_year' => 2026,
            'description' => 'Deskripsi baru.',
            'location' => 'Rak U-02',
            'stock' => 5,
        ])->assertRedirect(route('admin.buku.index'));

        $this->delete('/admin/buku/' . $book->book_id)->assertRedirect(route('admin.buku.index'));
        $this->assertDatabaseHas('books', ['book_id' => $book->book_id, 'is_active' => 0]);
        $this->assertDatabaseCount('admin_logs', 3);
    }

    public function test_admin_can_edit_and_delete_unused_categories_and_authors_from_kelola_buku(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin);   // memakai "Kategori Uji" & "Penulis Uji"
        $kategoriKosong = Category::create(['category_name' => 'Pendidikan']);
        $penulisKosong = Author::create(['author_name' => 'Tim Lama']);
        $kategoriNonaktif = Category::create(['category_name' => 'Arsip']);
        $bukuNonaktif = $book->replicate()->fill(['title' => 'Buku Arsip', 'category_id' => $kategoriNonaktif->category_id, 'is_active' => 0]);
        $bukuNonaktif->save();

        // tab Kategori & Penulis di halaman Kelola Buku
        $this->actingAs($admin)->get('/admin/buku')->assertOk()
            ->assertSee(route('admin.buku.kategori.index'))->assertSee(route('admin.buku.penulis.index'));
        $this->get('/admin/buku/kategori')->assertOk()
            ->assertSee('Kategori Uji')->assertSee('Pendidikan')->assertSee('Tidak dipakai')
            ->assertSee('(1 nonaktif)')
            ->assertSee(route('admin.buku.kategori.destroy', $kategoriKosong->category_id))
            ->assertDontSee(route('admin.buku.kategori.destroy', $book->category_id));
        $this->get('/admin/buku/penulis?q=lama')->assertOk()
            ->assertSee('Tim Lama')->assertDontSee('Penulis Uji');

        // edit nama & deskripsi (nama tidak boleh kembar)
        $this->get('/admin/buku/kategori?edit=' . $kategoriKosong->category_id)->assertOk()
            ->assertSee('name="category_name"', false);
        $this->from('/admin/buku/kategori?edit=' . $kategoriKosong->category_id)
            ->put('/admin/buku/kategori/' . $kategoriKosong->category_id, ['id_edit' => $kategoriKosong->category_id, 'category_name' => 'Kategori Uji'])
            ->assertSessionHasErrors('category_name');
        $this->put('/admin/buku/kategori/' . $kategoriKosong->category_id, ['category_name' => 'Pendidikan & Sekolah', 'description' => 'Buku pelajaran.'])
            ->assertRedirect(route('admin.buku.kategori.index'));
        $this->assertDatabaseHas('categories', ['category_id' => $kategoriKosong->category_id, 'category_name' => 'Pendidikan & Sekolah', 'description' => 'Buku pelajaran.']);
        $this->put('/admin/buku/penulis/' . $book->author_id, ['author_name' => 'Penulis Uji Diperbarui', 'biography' => 'Bio baru.'])
            ->assertRedirect(route('admin.buku.penulis.index'));
        $this->assertSame('Penulis Uji Diperbarui', $book->fresh()->author->author_name);

        // yang masih dipakai buku (termasuk buku nonaktif) tidak bisa dihapus
        $this->delete('/admin/buku/kategori/' . $book->category_id)->assertSessionHas('error');
        $this->delete('/admin/buku/kategori/' . $kategoriNonaktif->category_id)->assertSessionHas('error');
        $this->delete('/admin/buku/penulis/' . $book->author_id)->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['category_id' => $kategoriNonaktif->category_id]);

        // yang tidak dipakai bisa dihapus
        $this->delete('/admin/buku/kategori/' . $kategoriKosong->category_id)->assertRedirect(route('admin.buku.kategori.index'));
        $this->delete('/admin/buku/penulis/' . $penulisKosong->author_id)->assertRedirect(route('admin.buku.penulis.index'));
        $this->assertDatabaseMissing('categories', ['category_id' => $kategoriKosong->category_id]);
        $this->assertDatabaseMissing('authors', ['author_id' => $penulisKosong->author_id]);

        // peminjam tidak bisa membuka halaman admin, dan kategori tanpa buku aktif tidak tampil di filter katalog
        $this->actingAs($borrower)->get('/admin/buku/kategori')->assertRedirect(route('dashboard'));
        $this->get('/dashboard')->assertOk()->assertSee('Kategori Uji')->assertDontSee('Arsip');
    }

    public function test_deleted_books_move_to_inactive_tab_and_can_be_reactivated(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin);
        $loan = $this->makeLoan($borrower, $book, ['status' => 'Dipinjam']);

        // masih dipinjam: tombol hapus terkunci dan server menolak
        $this->actingAs($admin)->get('/admin/buku')->assertOk()
            ->assertSee('Masih ada 1 peminjaman aktif')
            ->assertDontSee('action="' . route('admin.buku.destroy', $book->book_id) . '"', false);
        $this->delete('/admin/buku/' . $book->book_id)->assertSessionHas('error');
        $this->assertSame(1, (int) $book->fresh()->is_active);

        // setelah peminjaman selesai: buku bisa dihapus dan pindah ke tab Nonaktif
        $loan->update(['status' => 'Dikembalikan', 'return_date' => today()]);
        $this->delete('/admin/buku/' . $book->book_id)->assertRedirect(route('admin.buku.index'));
        $this->assertSame(0, (int) $book->fresh()->is_active);
        $this->get('/admin/buku')->assertOk()->assertDontSee('<td>Buku Uji</td>', false);
        $this->get('/admin/buku/nonaktif')->assertOk()
            ->assertSee('Buku Uji')->assertSee('1x dipinjam')->assertSee('Aktifkan kembali');
        $this->actingAs($borrower)->get('/buku/' . $book->book_id)->assertNotFound();

        // aktifkan kembali: tampil lagi di katalog
        $this->actingAs($admin)->put('/admin/buku/' . $book->book_id . '/aktifkan')
            ->assertRedirect(route('admin.buku.nonaktif'));
        $this->assertSame(1, (int) $book->fresh()->is_active);
        $this->assertDatabaseHas('admin_logs', ['action' => 'Aktifkan', 'table_name' => 'books', 'record_id' => $book->book_id]);
        $this->get('/admin/buku/nonaktif')->assertOk()->assertSee('Tidak ada buku nonaktif.');
        $this->put('/admin/buku/' . $book->book_id . '/aktifkan')->assertNotFound();
        $this->actingAs($borrower)->get('/buku/' . $book->book_id)->assertOk();
    }

    public function test_only_inactive_books_never_borrowed_or_reviewed_can_be_deleted_permanently(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('covers/salah-input.jpg', 'cover');

        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $dipinjam = $this->makeBook($admin);
        $this->makeLoan($borrower, $dipinjam, ['status' => 'Dikembalikan', 'return_date' => today()]);
        $diulas = $dipinjam->replicate()->fill(['title' => 'Buku Diulas']);
        $diulas->save();
        Review::create(['user_id' => $borrower->user_id, 'book_id' => $diulas->book_id, 'rating' => 4, 'review_text' => 'Bagus.']);
        $salahInput = $dipinjam->replicate()->fill(['title' => 'Buku Salah Input', 'cover_image' => 'covers/salah-input.jpg']);
        $salahInput->save();

        $permanen = fn (Book $b) => '/admin/buku/' . $b->book_id . '/permanen';

        // buku aktif harus dinonaktifkan dulu sebelum bisa dihapus permanen
        $this->actingAs($admin)->delete($permanen($salahInput))->assertNotFound();
        foreach ([$dipinjam, $diulas, $salahInput] as $b) {
            $this->delete('/admin/buku/' . $b->book_id)->assertRedirect(route('admin.buku.index'));
        }

        // hanya buku tanpa riwayat yang punya tombol hapus permanen
        $this->get('/admin/buku/nonaktif')->assertOk()
            ->assertSee('Belum pernah dipakai')
            ->assertSee('action="' . route('admin.buku.hapus-permanen', $salahInput->book_id) . '"', false)
            ->assertDontSee('action="' . route('admin.buku.hapus-permanen', $dipinjam->book_id) . '"', false)
            ->assertDontSee('action="' . route('admin.buku.hapus-permanen', $diulas->book_id) . '"', false);

        // buku yang pernah dipinjam / diulas ditolak server dan tetap tersimpan
        $this->delete($permanen($dipinjam))->assertSessionHas('error');
        $this->delete($permanen($diulas))->assertSessionHas('error');
        $this->assertDatabaseHas('books', ['book_id' => $dipinjam->book_id]);
        $this->assertDatabaseHas('books', ['book_id' => $diulas->book_id]);

        // buku tanpa riwayat terhapus dari database beserta file cover-nya
        $this->delete($permanen($salahInput))->assertRedirect(route('admin.buku.nonaktif'));
        $this->assertDatabaseMissing('books', ['book_id' => $salahInput->book_id]);
        Storage::disk('public')->assertMissing('covers/salah-input.jpg');
        $this->assertDatabaseHas('admin_logs', ['action' => 'Hapus Permanen', 'table_name' => 'books', 'record_id' => $salahInput->book_id]);
        $this->get('/admin/buku/nonaktif')->assertOk()
            ->assertSee('Buku &quot;Buku Salah Input&quot; dihapus permanen.', false)
            ->assertDontSee('action="' . route('admin.buku.aktifkan', $salahInput->book_id) . '"', false);
    }

    public function test_book_must_always_have_category_and_author(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $book = $this->makeBook($admin);
        $kategoriAwal = $book->category_id;
        $penulisAwal = $book->author_id;
        $dataBuku = ['title' => $book->title, 'stock' => 3];

        // tanpa kategori & penulis: ditolak, data buku tidak berubah
        $this->actingAs($admin)->put('/admin/buku/' . $book->book_id, $dataBuku + ['category_id' => '', 'author_id' => ''])
            ->assertSessionHasErrors(['category_id' => 'Kategori wajib dipilih.', 'author_id' => 'Penulis wajib dipilih.']);
        $this->assertSame($kategoriAwal, $book->fresh()->category_id);
        $this->assertSame($penulisAwal, $book->fresh()->author_id);

        $this->post('/admin/buku', ['title' => 'Buku Tanpa Kategori', 'stock' => 1])
            ->assertSessionHasErrors(['category_id', 'author_id']);
        $this->assertDatabaseMissing('books', ['title' => 'Buku Tanpa Kategori']);

        // "+ Tambah baru" membuat kategori & penulis baru
        $this->put('/admin/buku/' . $book->book_id, $dataBuku + [
            'category_id' => 'new', 'new_category_name' => 'Sejarah',
            'author_id' => 'new', 'new_author_name' => 'Penulis Baru',
        ])->assertRedirect(route('admin.buku.index'));
        $this->assertSame('Sejarah', $book->fresh()->category->category_name);
        $this->assertSame('Penulis Baru', $book->fresh()->author->author_name);

        // nama baru yang tertinggal tidak membuat kategori baru bila yang dipilih kategori lama
        $this->put('/admin/buku/' . $book->book_id, $dataBuku + [
            'category_id' => $kategoriAwal, 'new_category_name' => 'Tidak Boleh Dibuat',
            'author_id' => $penulisAwal,
        ])->assertRedirect(route('admin.buku.index'));
        $this->assertSame($kategoriAwal, $book->fresh()->category_id);
        $this->assertDatabaseMissing('categories', ['category_name' => 'Tidak Boleh Dibuat']);

        // di form edit, pilihan kosong tidak bisa dipilih lagi
        $this->get('/admin/buku/' . $book->book_id . '/edit')->assertOk()
            ->assertSee('<option value="" disabled >- Pilih kategori -</option>', false)
            ->assertSee('<option value="" disabled >- Pilih penulis -</option>', false);
    }

    public function test_loan_date_is_set_when_book_is_picked_up_not_when_requested(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin);

        // tanggal yang dikirim dari luar diabaikan: pengajuan selalu tercatat hari ini
        $this->actingAs($borrower)->post('/peminjaman', [
            'book_id' => $book->book_id,
            'loan_date' => today()->addDays(10)->format('Y-m-d'),
            'durasi' => '5',
        ])->assertRedirect(route('peminjaman.index'));

        $loan = Loan::firstOrFail();
        $this->assertNull($loan->loan_date);
        $this->assertSame(today()->addDays(5)->format('Y-m-d'), $loan->due_date->format('Y-m-d'));

        $this->get('/peminjaman/buat')->assertOk()
            ->assertSee('Tanggal pengajuan')
            ->assertSee(today()->format('d/m/Y'));

        $this->actingAs($admin)->put('/admin/peminjaman/' . $loan->loan_id . '/setujui');
        $this->assertNull($loan->fresh()->loan_date);
        $this->assertNull($loan->fresh()->tanggal_pinjam);

        // buku diambil sehari kemudian: tanggal pinjam = hari itu, batas kembali digeser 5 hari dari hari itu
        $this->travel(1)->days();
        $this->put('/admin/riwayat/' . $loan->loan_id . '/dipinjam');

        $loan->refresh();
        $this->assertSame('Dipinjam', $loan->status);
        $this->assertSame(today()->format('Y-m-d'), $loan->loan_date->format('Y-m-d'));
        $this->assertSame(today()->addDays(5)->format('Y-m-d'), $loan->due_date->format('Y-m-d'));
        $this->assertSame(today()->format('Y-m-d'), $loan->tanggal_pinjam->format('Y-m-d'));
    }

    public function test_loan_history_filters_group_loans_by_status_and_unpaid_fine(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin, 10);

        $menunggu = $this->makeLoan($borrower, $book, ['loan_date' => null]);
        $dikonfirmasi = $this->makeLoan($borrower, $book, ['loan_date' => null, 'status' => 'Dikonfirmasi', 'approved_at' => now()]);
        $dipinjam = $this->makeLoan($borrower, $book, ['status' => 'Dipinjam']);
        $telatBelumLunas = $this->makeLoan($borrower, $book, [
            'status' => 'Dipinjam', 'loan_date' => today()->subDays(10), 'due_date' => today()->subDays(3),
        ]);
        $kembaliTelatBelumLunas = $this->makeLoan($borrower, $book, [
            'status' => 'Dikembalikan', 'loan_date' => today()->subDays(20),
            'due_date' => today()->subDays(13), 'return_date' => today()->subDays(11),
        ]);
        $kembaliTelatLunas = $this->makeLoan($borrower, $book, [
            'status' => 'Dikembalikan', 'loan_date' => today()->subDays(20),
            'due_date' => today()->subDays(13), 'return_date' => today()->subDays(11), 'fine_paid_at' => now(),
        ]);
        $gagal = $this->makeLoan($borrower, $book, ['loan_date' => null, 'status' => 'Gagal']);

        $isi = function (?string $filter) use ($borrower) {
            $daftar = $this->actingAs($borrower)
                ->get(route('peminjaman.index', $filter ? ['filter' => $filter] : []))
                ->assertOk()->viewData('daftar');

            return $daftar->pluck('loan_id')->sort()->values()->all();
        };
        $id = fn (...$loans) => collect($loans)->pluck('loan_id')->sort()->values()->all();

        $this->assertSame($id($dipinjam, $telatBelumLunas), $isi('aktif'));
        $this->assertSame($id($menunggu, $dikonfirmasi), $isi('menunggu'));
        $this->assertSame($id($kembaliTelatBelumLunas, $kembaliTelatLunas), $isi('selesai'));
        $this->assertSame($id($telatBelumLunas, $kembaliTelatBelumLunas), $isi('terlambat'));
        $this->assertSame($id($gagal), $isi('gagal'));
        $this->assertCount(7, $isi(null));
        $this->assertCount(7, $isi('filter-tidak-dikenal'));

        $this->get(route('peminjaman.index'))->assertSee('Terlambat <strong>2</strong>', false)
            ->assertSee('Gagal <strong>1</strong>', false);
    }

    public function test_catalog_filter_supports_multiple_categories_sorting_and_availability(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $fiksi = Category::create(['category_name' => 'Fiksi']);
        $sains = Category::create(['category_name' => 'Sains']);
        $sejarah = Category::create(['category_name' => 'Sejarah']);
        $author = Author::create(['author_name' => 'Penulis Filter']);

        $buatBuku = fn (string $judul, Category $kat, int $stok = 3) => Book::create([
            'category_id' => $kat->category_id, 'author_id' => $author->author_id, 'title' => $judul,
            'stock' => 3, 'available_stock' => $stok, 'is_active' => true, 'created_by' => $admin->user_id,
        ]);
        $fiksiA = $buatBuku('A Fiksi Jarang', $fiksi);
        $fiksiB = $buatBuku('B Fiksi Laris', $fiksi);
        $sainsC = $buatBuku('C Sains Habis', $sains, 0);
        $buatBuku('D Sejarah', $sejarah);

        // B dipinjam 2x, A 1x -> urutan "paling sering dipinjam": B, A
        $this->makeLoan($borrower, $fiksiB, ['status' => 'Dikembalikan']);
        $this->makeLoan($borrower, $fiksiB, ['status' => 'Dikembalikan']);
        $this->makeLoan($borrower, $fiksiA, ['status' => 'Dikembalikan']);

        $judul = fn (array $param) => $this->actingAs($borrower)->get(route('dashboard', $param))
            ->assertOk()->viewData('buku')->pluck('title')->all();

        $this->assertSame(['B Fiksi Laris', 'A Fiksi Jarang'],
            $judul(['kategori' => [$fiksi->category_id], 'urut' => 'populer']));
        $this->assertSame(['A Fiksi Jarang', 'B Fiksi Laris', 'C Sains Habis'],
            $judul(['kategori' => [$fiksi->category_id, $sains->category_id]]));
        $this->assertSame(['A Fiksi Jarang', 'B Fiksi Laris'],
            $judul(['kategori' => [$fiksi->category_id, $sains->category_id], 'tersedia' => 1]));
        $this->assertSame(['C Sains Habis'], $judul(['kategori' => (string) $sains->category_id]));

        // judul hasil filter menggabungkan kategori dan urutan, keterangan buku mengikuti urutan
        $this->get(route('dashboard', ['kategori' => [$fiksi->category_id], 'urut' => 'populer']))
            ->assertSee('Fiksi · Paling sering dipinjam')
            ->assertSee('2x dipinjam');
    }

    public function test_top_review_shelf_orders_books_by_average_rating(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $category = Category::create(['category_name' => 'Kategori Review']);
        $author = Author::create(['author_name' => 'Penulis Review']);
        $buatBuku = fn (string $judul) => Book::create([
            'category_id' => $category->category_id, 'author_id' => $author->author_id, 'title' => $judul,
            'stock' => 2, 'available_stock' => 2, 'is_active' => true, 'created_by' => $admin->user_id,
        ]);
        $pembaca = collect(range(1, 3))->map(fn ($i) => $this->makeUser('Peminjam', 'PEMBACA-' . $i));
        $ulas = function (Book $buku, array $ratings) use ($pembaca) {
            foreach ($ratings as $i => $rating) {
                Review::create(['user_id' => $pembaca[$i]->user_id, 'book_id' => $buku->book_id, 'rating' => $rating, 'review_text' => 'Ulasan ' . $rating]);
            }
        };

        $sedang = $buatBuku('Rating Tiga');
        $bagus = $buatBuku('Rating Empat Setengah');
        $terbaik = $buatBuku('Rating Lima');
        $buatBuku('Belum Diulas');
        $ulas($sedang, [3]);
        $ulas($bagus, [5, 4]);
        $ulas($terbaik, [5]);

        $rak = $this->actingAs($borrower)->get(route('dashboard'))->assertOk();
        $this->assertSame(['Rating Lima', 'Rating Empat Setengah', 'Rating Tiga'],
            $rak->viewData('topReview')->pluck('title')->all());
        $rak->assertSee('Top Review')->assertDontSee('Banyak Ulasan')
            ->assertSee('4.5 · 2 ulasan')->assertSee('3.0 · 1 ulasan');

        // urutan "Top review" di panel filter memakai aturan yang sama; buku tanpa ulasan di paling bawah
        $this->assertSame(['Rating Lima', 'Rating Empat Setengah', 'Rating Tiga', 'Belum Diulas'],
            $this->get(route('dashboard', ['urut' => 'rating']))->viewData('buku')->pluck('title')->all());
    }

    public function test_complete_loan_approval_pickup_return_and_history_removal(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin, 2);
        $loan = $this->makeLoan($borrower, $book);

        $this->actingAs($admin)->put('/admin/peminjaman/' . $loan->loan_id . '/setujui')
            ->assertRedirect(route('admin.peminjaman.index'));
        $this->assertSame('Dikonfirmasi', $loan->fresh()->status);
        $this->assertSame(1, $book->fresh()->available_stock);

        $this->put('/admin/riwayat/' . $loan->loan_id . '/dipinjam')
            ->assertRedirect(route('admin.peminjaman.riwayat'));
        $this->assertSame('Dipinjam', $loan->fresh()->status);

        $this->put('/admin/riwayat/' . $loan->loan_id . '/kembalikan')
            ->assertRedirect(route('admin.peminjaman.riwayat'));
        $this->assertSame('Dikembalikan', $loan->fresh()->status);
        $this->assertSame(2, $book->fresh()->available_stock);

        $this->delete('/admin/riwayat/' . $loan->loan_id)
            ->assertRedirect(route('admin.peminjaman.riwayat'));
        $this->assertDatabaseMissing('loans', ['loan_id' => $loan->loan_id]);
        $this->assertDatabaseMissing('notifications', ['loan_id' => $loan->loan_id]);

        $rejected = $this->makeLoan($borrower, $book);
        $this->put('/admin/peminjaman/' . $rejected->loan_id . '/tolak')
            ->assertRedirect(route('admin.peminjaman.index'));
        $this->assertSame('Gagal', $rejected->fresh()->status);
        $this->assertSame(2, $book->fresh()->available_stock);
    }

    public function test_expired_confirmed_loan_is_failed_and_stock_is_restored(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin, 2);
        $book->update(['available_stock' => 1]);
        $loan = $this->makeLoan($borrower, $book, [
            'status' => 'Dikonfirmasi',
            'approved_by' => $admin->user_id,
            'approved_at' => now()->subDays(3),
        ]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        $this->assertSame('Gagal', $loan->fresh()->status);
        $this->assertSame(2, $book->fresh()->available_stock);
    }

    public function test_overdue_fine_contact_information_and_daily_email_reminder(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin, 2);
        $loan = $this->makeLoan($borrower, $book, [
            'status' => 'Dipinjam',
            'due_date' => today()->subDays(3),
            'approved_by' => $admin->user_id,
            'approved_at' => now()->subDays(10),
        ]);

        $this->assertSame(3, $loan->hari_terlambat);
        $this->assertSame(3000, $loan->jumlah_denda);

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee($borrower->email)
            ->assertSee($borrower->phone)
            ->assertSee('Rp3.000')
            ->assertSee('Belum Lunas');

        $this->get('/admin/peminjam')->assertOk()->assertSee($borrower->email);

        $this->artisan('loans:send-overdue-reminders')->assertExitCode(0);
        $this->assertDatabaseHas('notifications', [
            'loan_id' => $loan->loan_id,
            'type' => 'Terlambat',
            'status' => 'Terkirim',
        ]);
        $this->artisan('loans:send-overdue-reminders')->assertExitCode(0);
        $this->assertDatabaseCount('notifications', 1);

        // bayar denda = sekaligus mengembalikan buku
        $this->put('/admin/riwayat/' . $loan->loan_id . '/denda')
            ->assertRedirect()
            ->assertSessionHas('status', 'Denda lunas dan buku ditandai sudah dikembalikan.');
        $loan->refresh();
        $this->assertNotNull($loan->fine_paid_at);
        $this->assertSame($admin->user_id, $loan->fine_paid_by);
        $this->assertSame('Dikembalikan', $loan->status);
        $this->assertSame(today()->format('Y-m-d'), $loan->return_date->format('Y-m-d'));
        $this->assertSame(3000, $loan->jumlah_denda);
        $this->assertSame(3, $book->fresh()->available_stock);
        $this->assertDatabaseHas('notifications', ['loan_id' => $loan->loan_id, 'type' => 'Dikembalikan']);

        // yang sudah lunas hilang dari tabel peminjaman terlambat di dashboard
        $this->get('/admin/dashboard')->assertOk()
            ->assertDontSee($borrower->email)
            ->assertSee('Tidak ada peminjaman yang terlambat.');

        // status Lunas final: tidak bisa diubah kembali menjadi Belum Lunas, stok tidak bertambah lagi
        $dibayarPada = $loan->fine_paid_at->toDateTimeString();
        $this->put('/admin/riwayat/' . $loan->loan_id . '/denda')
            ->assertRedirect()
            ->assertSessionHas('error', 'Denda ini sudah lunas dan statusnya tidak bisa diubah lagi.');
        $loan->refresh();
        $this->assertSame($dibayarPada, $loan->fine_paid_at->toDateTimeString());
        $this->assertSame('Dikembalikan', $loan->status);
        $this->assertSame(3, $book->fresh()->available_stock);

        // di halaman riwayat admin, denda lunas hanya tampil sebagai label (tanpa tombol ubah)
        $this->get('/admin/riwayat')->assertOk()
            ->assertSee('<span class="badge b-setuju" style="margin-top:6px">Lunas</span>', false)
            ->assertDontSee('/admin/riwayat/' . $loan->loan_id . '/denda');
    }

    public function test_user_can_add_and_update_review_and_admin_can_delete_it(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin);

        $this->actingAs($borrower)->post('/buku/' . $book->book_id . '/ulasan', [
            'rating' => 5,
            'review_text' => 'Sangat membantu dan mudah dipahami.',
        ])->assertRedirect();

        $review = \App\Models\Review::firstOrFail();
        // sudah memberi ulasan: form tulis ulasan diganti tombol Edit Ulasan
        $this->get('/buku/' . $book->book_id)->assertOk()
            ->assertSee('Sangat membantu')
            ->assertSee('Edit Ulasan')
            ->assertDontSee('Tulis ulasan');

        // ulasan kedua untuk buku yang sama ditolak dan diarahkan ke halaman edit
        $this->post('/buku/' . $book->book_id . '/ulasan', [
            'rating' => 1,
            'review_text' => 'Ulasan kedua.',
        ])->assertRedirect(route('ulasan.index', ['ulasan' => $review->review_id, 'edit' => 1]) . '#ulasan-' . $review->review_id)
            ->assertSessionHasErrors('review_text');
        $this->assertDatabaseCount('reviews', 1);
        $this->assertSame(5, $review->fresh()->rating);

        $this->put('/ulasan/' . $review->review_id, [
            'ulasan_id' => $review->review_id,
            'rating' => 4,
            'review_text' => 'Komentar diperbarui.',
        ])->assertRedirect(route('ulasan.index', ['ulasan' => $review->review_id]) . '#ulasan-' . $review->review_id);
        $this->assertDatabaseCount('reviews', 1);
        $this->assertSame(4, $review->fresh()->rating);

        $this->actingAs($admin)->get('/admin/ulasan')->assertOk()->assertSee('Komentar diperbarui.');
        $this->delete('/admin/ulasan/' . $review->review_id)->assertRedirect(route('admin.reviews.index'));
        $this->assertDatabaseMissing('reviews', ['review_id' => $review->review_id]);
    }

    public function test_review_page_lists_all_reviews_and_only_owner_can_edit_or_delete(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $saya = $this->makeUser('Peminjam', 'SISWA-TEST');
        $lain = $this->makeUser('Peminjam', 'SISWA-LAIN');
        $bukuA = $this->makeBook($admin);
        $bukuB = $bukuA->replicate()->fill(['title' => 'Buku Kedua']);
        $bukuB->save();

        $milikSaya = Review::create(['user_id' => $saya->user_id, 'book_id' => $bukuA->book_id, 'rating' => 4, 'review_text' => 'Ulasan milik saya.']);
        $milikLain = Review::create(['user_id' => $lain->user_id, 'book_id' => $bukuB->book_id, 'rating' => 2, 'review_text' => 'Ulasan pembaca lain.']);

        // semua ulasan dari setiap buku tampil, dengan menu baru di sidebar
        $this->actingAs($saya)->get('/ulasan')->assertOk()
            ->assertSee('Review &amp; Ulasan', false)
            ->assertSee('Ulasan milik saya.')
            ->assertSee('Ulasan pembaca lain.')
            ->assertSee('Edit Ulasan')
            ->assertSee('Hapus Ulasan')
            ->assertDontSee('Respond')
            ->assertDontSee('Balas');

        // filter "Ulasan Saya", per buku, dan pencarian
        $this->get('/ulasan?saya=1')->assertOk()
            ->assertSee('Ulasan milik saya.')->assertDontSee('Ulasan pembaca lain.');
        $this->get('/ulasan?buku=' . $bukuB->book_id)->assertOk()
            ->assertSee('Ulasan pembaca lain.')->assertDontSee('Ulasan milik saya.')
            ->assertSee('Buku Kedua');
        $this->get('/ulasan?q=pembaca+lain')->assertOk()
            ->assertSee('Ulasan pembaca lain.')->assertDontSee('Ulasan milik saya.');

        // filter rating berupa kotak centang 5 bintang sampai 1 bintang, boleh lebih dari satu
        $this->get('/ulasan?bintang[]=2')->assertOk()
            ->assertSee('Ulasan pembaca lain.')->assertDontSee('Ulasan milik saya.')
            ->assertSee('name="bintang[]" value="2" class="centang-rating" checked', false)
            ->assertSee('name="bintang[]" value="4" class="centang-rating" >', false)
            ->assertDontSee('Rating terendah')
            ->assertDontSee('class="wajah', false);
        $this->get('/ulasan?bintang[]=4&bintang[]=2')->assertOk()
            ->assertSee('Ulasan pembaca lain.')->assertSee('Ulasan milik saya.')
            ->assertSee('4, 2 bintang');
        $this->get('/ulasan?bintang[]=3&bintang[]=1')->assertOk()
            ->assertSee('Tidak ada ulasan dengan rating 3, 1 bintang.');

        // ulasan milik orang lain hanya bisa dibaca
        $this->get('/ulasan?ulasan=' . $milikLain->review_id)->assertOk()
            ->assertSee('Ulasan pembaca lain hanya bisa dibaca.');
        $this->put('/ulasan/' . $milikLain->review_id, ['rating' => 5, 'review_text' => 'Diubah orang lain.'])->assertNotFound();
        $this->delete('/ulasan/' . $milikLain->review_id)->assertNotFound();
        $this->assertSame(2, $milikLain->fresh()->rating);

        // edit ulasan sendiri: validasi gagal membuka kembali form edit ulasan tersebut
        $this->from('/ulasan?ulasan=' . $milikSaya->review_id)
            ->put('/ulasan/' . $milikSaya->review_id, ['ulasan_id' => $milikSaya->review_id, 'rating' => 9, 'review_text' => ''])
            ->assertSessionHasErrors(['rating', 'review_text']);
        $this->get('/ulasan?ulasan=' . $milikSaya->review_id)->assertOk()
            ->assertSee('kartu-ulasan terpilih sedang-edit', false);

        $this->put('/ulasan/' . $milikSaya->review_id, [
            'ulasan_id' => $milikSaya->review_id,
            'rating' => 5,
            'review_text' => 'Ulasan sudah diedit.',
            'saya' => '1',
        ])->assertRedirect(route('ulasan.index', ['saya' => '1', 'ulasan' => $milikSaya->review_id]) . '#ulasan-' . $milikSaya->review_id);
        $this->assertDatabaseHas('reviews', ['review_id' => $milikSaya->review_id, 'rating' => 5, 'review_text' => 'Ulasan sudah diedit.']);

        // hapus ulasan sendiri, lalu boleh menulis ulasan baru lagi
        $this->delete('/ulasan/' . $milikSaya->review_id)->assertRedirect(route('ulasan.index'));
        $this->assertDatabaseMissing('reviews', ['review_id' => $milikSaya->review_id]);
        $this->get('/buku/' . $bukuA->book_id)->assertOk()->assertSee('Tulis ulasan');
    }

    public function test_review_page_shows_five_reviews_per_page(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $book = $this->makeBook($admin);
        $saya = null;

        // 7 ulasan: ulasan ke-1 paling lama, ulasan ke-7 paling baru
        foreach (range(1, 7) as $i) {
            $pembaca = $this->makeUser('Peminjam', 'SISWA-' . $i);
            $saya ??= $pembaca;
            Review::create([
                'user_id' => $pembaca->user_id,
                'book_id' => $book->book_id,
                'rating' => 5,
                'review_text' => 'Ulasan nomor ' . $i . '.',
                'created_at' => now()->subDays(10 - $i),
            ]);
        }

        // halaman 1: 5 ulasan terbaru + tombol ke halaman berikutnya
        $this->actingAs($saya)->get('/ulasan')->assertOk()
            ->assertSee('Ulasan nomor 7.')->assertSee('Ulasan nomor 3.')
            ->assertDontSee('Ulasan nomor 2.')
            ->assertSee('Halaman 1 / 2')
            ->assertSee('href="' . route('ulasan.index', ['halaman' => 2]) . '"', false);

        // halaman 2: sisa 2 ulasan
        $this->get('/ulasan?halaman=2')->assertOk()
            ->assertSee('Ulasan nomor 2.')->assertSee('Ulasan nomor 1.')
            ->assertDontSee('Ulasan nomor 3.')
            ->assertSee('Halaman 2 / 2');

        // detail buku: hanya 5 ulasan teratas + tautan ke Review & Ulasan khusus buku ini
        $this->get('/buku/' . $book->book_id)->assertOk()
            ->assertSee('7 ulasan pembaca')
            ->assertSee('Ulasan 5 dari 5')->assertDontSee('Ulasan 6 dari')
            ->assertSee('Ulasan nomor 7.')->assertDontSee('Ulasan nomor 2.')
            ->assertSee('href="' . route('ulasan.index', ['buku' => $book->book_id]) . '"', false)
            ->assertSee('Lihat semua ulasan');

        // nomor halaman di luar jangkauan kembali ke halaman terakhir
        $this->get('/ulasan?halaman=9')->assertOk()->assertSee('Halaman 2 / 2');

        // membuka ulasan tertentu (mis. dari tombol Edit Ulasan) langsung ke halaman tempat ulasan itu berada
        $ulasanLama = Review::where('user_id', $saya->user_id)->firstOrFail();
        $this->get('/ulasan?ulasan=' . $ulasanLama->review_id . '&edit=1')->assertOk()
            ->assertSee('Halaman 2 / 2')
            ->assertSee('kartu-ulasan terpilih sedang-edit', false);
    }

    public function test_returned_book_cannot_be_borrowed_again_by_same_user_until_two_days_later(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 3)->setTime(9, 0));

        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $saya = $this->makeUser('Peminjam', 'SISWA-TEST');
        $lain = $this->makeUser('Peminjam', 'SISWA-LAIN');
        $book = $this->makeBook($admin);

        // dikembalikan 3 Oktober
        $this->makeLoan($saya, $book, [
            'loan_date' => '2026-09-28',
            'due_date' => '2026-10-05',
            'return_date' => '2026-10-03',
            'status' => 'Dikembalikan',
        ]);
        $ajukan = fn () => $this->post('/peminjaman', ['book_id' => $book->book_id, 'durasi' => '7']);

        // 3 Oktober (hari pengembalian): ditolak, tombol di detail & katalog ikut terkunci
        $this->actingAs($saya)->get('/buku/' . $book->book_id)->assertOk()
            ->assertSee('Bisa dipinjam lagi 05 Oct 2026')
            ->assertDontSee('>Pinjam Buku</a>', false);
        $this->get('/dashboard?tampilan=semua')->assertOk()->assertSee('Bisa dipinjam 05 Oct');
        $this->get('/peminjaman/buat?buku=' . $book->book_id)->assertOk()
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('bisa dipinjam lagi mulai 05 Oct 2026');
        $ajukan()->assertSessionHasErrors(['book_id' => 'Kamu baru saja mengembalikan buku ini. Agar pembaca lain juga mendapat kesempatan, buku yang sama baru bisa kamu pinjam lagi mulai 05 Oct 2026.']);

        // pembaca lain tetap bisa meminjam buku yang sama
        $this->actingAs($lain)->post('/peminjaman', ['book_id' => $book->book_id, 'durasi' => '7'])
            ->assertRedirect(route('peminjaman.index'));

        // 4 Oktober: masih ditolak
        $this->travelTo(now()->setDate(2026, 10, 4)->setTime(23, 59));
        $this->actingAs($saya);
        $ajukan()->assertSessionHasErrors('book_id');
        $this->assertSame(1, Loan::where('user_id', $saya->user_id)->count());

        // 5 Oktober: boleh meminjam lagi
        $this->travelTo(now()->setDate(2026, 10, 5)->setTime(0, 1));
        $this->get('/buku/' . $book->book_id)->assertOk()->assertSee('>Pinjam Buku</a>', false);
        $ajukan()->assertRedirect(route('peminjaman.index'))->assertSessionHasNoErrors();
        $this->assertSame(2, Loan::where('user_id', $saya->user_id)->count());
    }

    public function test_out_of_stock_book_is_locked_until_stock_returns(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin, 0);

        $this->actingAs($borrower)->get('/buku/' . $book->book_id)
            ->assertOk()
            ->assertSee('Stok habis');

        $this->post('/peminjaman', [
            'book_id' => $book->book_id,
            'loan_date' => today()->format('Y-m-d'),
            'durasi' => '7',
        ])->assertSessionHasErrors('book_id');
        $this->assertDatabaseCount('loans', 0);

        $book->update(['stock' => 1, 'available_stock' => 1]);
        $this->get('/buku/' . $book->book_id)->assertOk()->assertSee('Pinjam Buku');
    }

    public function test_notifications_page_shows_all_types_and_filters_by_tab(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin);
        $loan = Loan::create([
            'user_id' => $borrower->user_id,
            'book_id' => $book->book_id,
            'request_date' => now(),
            'due_date' => today()->addDays(7),
            'status' => 'Menunggu',
        ]);

        UserNotification::create([
            'user_id' => $borrower->user_id, 'loan_id' => $loan->loan_id, 'type' => 'Pengajuan',
            'message' => 'Pengajuan peminjaman terkirim.', 'sent_at' => now(), 'status' => 'Terkirim',
        ]);
        UserNotification::create([
            'user_id' => $borrower->user_id, 'type' => 'Pengumuman',
            'message' => 'Perpustakaan tutup hari Minggu.', 'sent_at' => now(), 'status' => 'Terkirim',
        ]);

        // fitur e-book sudah dihapus: pengumuman tampil tanpa tautan ke halaman e-book
        $this->actingAs($borrower)->get('/notifikasi')->assertOk()
            ->assertSee('Pengajuan peminjaman terkirim.')
            ->assertSee('Perpustakaan tutup hari Minggu.')
            ->assertDontSee('E-book');

        $this->get('/notifikasi?tab=pengumuman')->assertOk()
            ->assertSee('Perpustakaan tutup hari Minggu.')
            ->assertDontSee('Pengajuan peminjaman terkirim.');

        $this->get('/notifikasi?tab=peminjaman')->assertOk()
            ->assertSee('Pengajuan peminjaman terkirim.')
            ->assertDontSee('Perpustakaan tutup hari Minggu.');
    }

    private function makeUser(string $role, string $nim): User
    {
        return User::create([
            'role' => $role,
            'nim_nip' => $nim,
            'name' => $role . ' Uji',
            'class' => $role === 'Peminjam' ? 'XII-A' : null,
            'email' => strtolower($nim) . '@example.test',
            'password' => Hash::make('password123'),
            'phone' => '081234567890',
        ]);
    }

    private function makeBook(User $admin, int $stock = 3): Book
    {
        $category = Category::create(['category_name' => 'Kategori Uji']);
        $author = Author::create(['author_name' => 'Penulis Uji']);

        return Book::create([
            'category_id' => $category->category_id,
            'author_id' => $author->author_id,
            'title' => 'Buku Uji',
            'publisher' => 'Penerbit Uji',
            'publication_year' => 2026,
            'location' => 'Rak U-01',
            'stock' => $stock,
            'available_stock' => $stock,
            'is_active' => true,
            'created_by' => $admin->user_id,
        ]);
    }

    private function makeLoan(User $borrower, Book $book, array $overrides = []): Loan
    {
        return Loan::create(array_merge([
            'user_id' => $borrower->user_id,
            'book_id' => $book->book_id,
            'loan_date' => today(),
            'due_date' => today()->addDays(7),
            'status' => 'Menunggu',
        ], $overrides));
    }
}
