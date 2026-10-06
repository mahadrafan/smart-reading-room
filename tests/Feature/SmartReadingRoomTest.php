<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Review;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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
            'penulis' => ['baru:Penulis Uji'],
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
        $this->assertSame([$author->author_id], $book->authors->pluck('author_id')->all());
        $this->assertSame(4, $book->available_stock);
        Storage::disk('public')->assertExists($book->cover_image);

        $this->put('/admin/buku/' . $book->book_id, [
            'category_id' => $category->category_id,
            'penulis' => [$author->author_id],
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
        $idPenulis = $book->authors->first()->author_id;
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
        $this->put('/admin/buku/penulis/' . $idPenulis, ['author_name' => 'Penulis Uji Diperbarui', 'biography' => 'Bio baru.'])
            ->assertRedirect(route('admin.buku.penulis.index'));
        $this->assertSame('Penulis Uji Diperbarui', $book->fresh()->nama_penulis);

        // yang masih dipakai buku (termasuk buku nonaktif) tidak bisa dihapus
        $this->delete('/admin/buku/kategori/' . $book->category_id)->assertSessionHas('error');
        $this->delete('/admin/buku/kategori/' . $kategoriNonaktif->category_id)->assertSessionHas('error');
        $this->delete('/admin/buku/penulis/' . $idPenulis)->assertSessionHas('error');
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

    public function test_book_stock_cannot_be_edited_below_copies_currently_borrowed(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $siswaA = $this->makeUser('Peminjam', 'SISWA-A');
        $siswaB = $this->makeUser('Peminjam', 'SISWA-B');
        $book = $this->makeBook($admin, 3);
        $dipinjam = $this->makeLoan($siswaA, $book, ['status' => 'Dipinjam']);
        $this->makeLoan($siswaB, $book, ['status' => 'Dikonfirmasi', 'approved_by' => $admin->user_id, 'approved_at' => now()]);
        $book->update(['available_stock' => 1]);
        $dataBuku = ['title' => $book->title, 'category_id' => $book->category_id, 'penulis' => $book->authors->pluck('author_id')->all()];

        // form edit memberi tahu batas minimalnya
        $this->actingAs($admin)->get('/admin/buku/' . $book->book_id . '/edit')->assertOk()
            ->assertSee('min="2"', false)
            ->assertSee('Sedang dipinjam / menunggu diambil: 2 eksemplar, jadi stok minimal 2.');

        // di bawah jumlah yang sedang dipinjam / menunggu diambil: ditolak, data tidak berubah
        $this->put('/admin/buku/' . $book->book_id, $dataBuku + ['stock' => 1])
            ->assertSessionHasErrors(['stock' => 'Jumlah stok minimal 2 karena 2 eksemplar sedang dipinjam atau menunggu diambil.']);
        $this->assertSame(3, (int) $book->fresh()->stock);
        $this->assertSame(1, (int) $book->fresh()->available_stock);

        // tepat di batas: boleh, stok tersedia jadi 0
        $this->put('/admin/buku/' . $book->book_id, $dataBuku + ['stock' => 2])->assertRedirect(route('admin.buku.index'));
        $this->assertSame(0, (int) $book->fresh()->available_stock);

        // stok ditambah: stok tersedia ikut bertambah, dan setelah buku kembali tidak melebihi stok
        $this->put('/admin/buku/' . $book->book_id, $dataBuku + ['stock' => 5])->assertRedirect(route('admin.buku.index'));
        $this->assertSame(3, (int) $book->fresh()->available_stock);
        $this->put('/admin/riwayat/' . $dipinjam->loan_id . '/kembalikan')->assertRedirect(route('admin.peminjaman.riwayat'));
        $this->assertSame(4, (int) $book->fresh()->available_stock);
        $this->assertSame(5, (int) $book->fresh()->stock);
    }

    public function test_book_must_always_have_category_and_author(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $book = $this->makeBook($admin);
        $kategoriAwal = $book->category_id;
        $penulisAwal = $book->authors->first()->author_id;
        $dataBuku = ['title' => $book->title, 'stock' => 3];
        $penulisBuku = fn () => $book->fresh()->authors->pluck('author_id')->all();

        // tanpa kategori & penulis: ditolak, data buku tidak berubah
        $this->actingAs($admin)->put('/admin/buku/' . $book->book_id, $dataBuku + ['category_id' => '', 'penulis' => []])
            ->assertSessionHasErrors(['category_id' => 'Kategori wajib dipilih.', 'penulis' => 'Penulis wajib dipilih.']);
        $this->assertSame($kategoriAwal, $book->fresh()->category_id);
        $this->assertSame([$penulisAwal], $penulisBuku());

        $this->post('/admin/buku', ['title' => 'Buku Tanpa Kategori', 'stock' => 1])
            ->assertSessionHasErrors(['category_id', 'penulis']);
        $this->assertDatabaseMissing('books', ['title' => 'Buku Tanpa Kategori']);

        // "+ Tambah baru" membuat kategori & penulis baru
        $this->put('/admin/buku/' . $book->book_id, $dataBuku + [
            'category_id' => 'new', 'new_category_name' => 'Sejarah',
            'penulis' => ['baru:Penulis Baru'],
        ])->assertRedirect(route('admin.buku.index'));
        $this->assertSame('Sejarah', $book->fresh()->category->category_name);
        $this->assertSame('Penulis Baru', $book->fresh()->nama_penulis);

        // nama baru yang tertinggal tidak membuat kategori baru bila yang dipilih kategori lama
        $this->put('/admin/buku/' . $book->book_id, $dataBuku + [
            'category_id' => $kategoriAwal, 'new_category_name' => 'Tidak Boleh Dibuat',
            'penulis' => [$penulisAwal],
        ])->assertRedirect(route('admin.buku.index'));
        $this->assertSame($kategoriAwal, $book->fresh()->category_id);
        $this->assertSame([$penulisAwal], $penulisBuku());
        $this->assertDatabaseMissing('categories', ['category_name' => 'Tidak Boleh Dibuat']);

        // di form edit, pilihan kategori kosong tidak bisa dipilih lagi; penulis buku sudah terpilih
        $this->get('/admin/buku/' . $book->book_id . '/edit')->assertOk()
            ->assertSee('<option value="" disabled >- Pilih kategori -</option>', false)
            ->assertSee('<option value="' . $penulisAwal . '" selected>', false)
            ->assertSee('data-pilih-penulis', false);
    }

    public function test_book_can_have_several_authors_in_chosen_order(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $kategori = Category::create(['category_name' => 'Jaringan']);
        $kurose = Author::create(['author_name' => 'Jim Kurose']);
        $tanenbaum = Author::create(['author_name' => 'Andrew Tanenbaum']);
        $dataBuku = ['category_id' => $kategori->category_id, 'title' => 'Computer Networking', 'stock' => 2];
        $penulisBuku = fn (Book $b) => $b->fresh()->authors->map(fn ($a) => [$a->author_name, $a->pivot->urutan])->all();

        // penulis lama + penulis baru, urutan sesuai urutan dipilih; nama baru yang sama persis memakai penulis lama
        $this->actingAs($admin)->post('/admin/buku', $dataBuku + [
            'penulis' => [$kurose->author_id, 'baru:Keith Ross', 'baru:Andrew Tanenbaum', (string) $kurose->author_id],
        ])->assertRedirect(route('admin.buku.index'));
        $book = Book::where('title', 'Computer Networking')->firstOrFail();
        $this->assertSame([['Jim Kurose', 1], ['Keith Ross', 2], ['Andrew Tanenbaum', 3]], $penulisBuku($book));
        $this->assertSame(1, Author::where('author_name', 'Andrew Tanenbaum')->count());
        $this->assertSame('Jim Kurose, Keith Ross, Andrew Tanenbaum', $book->fresh()->nama_penulis);
        $keith = Author::where('author_name', 'Keith Ross')->firstOrFail();

        // nama penulis tampil di hasil katalog, detail buku, dan daftar Kelola Buku
        $this->actingAs($borrower)->get(route('dashboard', ['q' => 'networking']))->assertOk()
            ->assertSee('Jim Kurose, Keith Ross, Andrew Tanenbaum');
        $this->get('/buku/' . $book->book_id)->assertOk()->assertSee('Jim Kurose, Keith Ross, Andrew Tanenbaum');
        // pencarian katalog menemukan buku lewat penulis mana pun
        $this->assertSame(['Computer Networking'],
            $this->get(route('dashboard', ['q' => 'keith']))->assertOk()->viewData('buku')->pluck('title')->all());
        $this->actingAs($admin)->get('/admin/buku')->assertOk()->assertSee('Jim Kurose, Keith Ross, Andrew Tanenbaum');

        // form edit memuat penulis berurutan
        $this->get('/admin/buku/' . $book->book_id . '/edit')->assertOk()
            ->assertSeeInOrder(['Jim Kurose', 'Keith Ross', 'Andrew Tanenbaum']);

        // edit: urutan diubah dan satu penulis dilepas; penulis yang dilepas tetap ada dan kini bisa dihapus
        $this->put('/admin/buku/' . $book->book_id, $dataBuku + ['penulis' => [$tanenbaum->author_id, $kurose->author_id]])
            ->assertRedirect(route('admin.buku.index'));
        $this->assertSame([['Andrew Tanenbaum', 1], ['Jim Kurose', 2]], $penulisBuku($book));
        $this->delete('/admin/buku/penulis/' . $kurose->author_id)->assertSessionHas('error');
        $this->delete('/admin/buku/penulis/' . $keith->author_id)->assertRedirect(route('admin.buku.penulis.index'));
        $this->assertDatabaseMissing('authors', ['author_id' => $keith->author_id]);

        // tanpa JavaScript: penulis dari daftar + satu nama penulis baru
        $this->put('/admin/buku/' . $book->book_id, $dataBuku + ['penulis' => [$kurose->author_id], 'penulis_baru' => 'Penulis Cadangan'])
            ->assertRedirect(route('admin.buku.index'));
        $this->assertSame([['Jim Kurose', 1], ['Penulis Cadangan', 2]], $penulisBuku($book));

        // isian tidak valid ditolak, penulis buku tidak berubah
        $salah = [
            [['999999'], 'Penulis tidak ditemukan.'],
            [['abc'], 'Penulis tidak valid.'],
            [['baru:' . str_repeat('a', 101)], 'Nama penulis baru maksimal 100 karakter.'],
            [array_map(fn ($i) => 'baru:Penulis ' . $i, range(1, 11)), 'Penulis maksimal 10 orang.'],
        ];
        foreach ($salah as [$penulis, $pesan]) {
            $this->put('/admin/buku/' . $book->book_id, $dataBuku + ['penulis' => $penulis])
                ->assertSessionHasErrors(['penulis' => $pesan]);
        }
        $this->assertSame([['Jim Kurose', 1], ['Penulis Cadangan', 2]], $penulisBuku($book));
        $this->assertDatabaseMissing('authors', ['author_name' => 'Penulis 1']);

        // validasi kolom lain gagal: penulis yang sudah dipilih (termasuk penulis baru) tetap ada di form
        $this->from('/admin/buku/create')->post('/admin/buku', ['category_id' => $kategori->category_id, 'title' => '', 'stock' => 1,
            'penulis' => [$kurose->author_id, 'baru:Penulis Kembali']])
            ->assertRedirect('/admin/buku/create')->assertSessionHasErrors('title');
        $this->assertDatabaseMissing('authors', ['author_name' => 'Penulis Kembali']);
        $this->get('/admin/buku/create')->assertOk()
            ->assertSee('baru:Penulis Kembali', false)
            ->assertSee('<option value="' . $kurose->author_id . '" selected>', false);

        // nama bergelar sudah memakai koma, jadi pemisah antar penulis memakai titik koma
        $bergelar = Book::create(['category_id' => $kategori->category_id, 'title' => 'Buku Bergelar', 'stock' => 1,
            'available_stock' => 1, 'is_active' => true, 'created_by' => $admin->user_id]);
        $bergelar->authors()->attach([
            Author::create(['author_name' => 'Sugeng Winardi, S.Kom., M.T.'])->author_id => ['urutan' => 1],
            Author::create(['author_name' => 'Aris Defiana, S.Kom'])->author_id => ['urutan' => 2],
        ]);
        $this->assertSame('Sugeng Winardi, S.Kom., M.T.; Aris Defiana, S.Kom', $bergelar->fresh()->nama_penulis);

        // hapus permanen buku: daftar penulisnya ikut terhapus, penulisnya tetap ada
        $this->delete('/admin/buku/' . $bergelar->book_id);
        $this->delete('/admin/buku/' . $bergelar->book_id . '/permanen')->assertRedirect(route('admin.buku.nonaktif'));
        $this->assertDatabaseMissing('book_authors', ['book_id' => $bergelar->book_id]);
        $this->assertDatabaseHas('authors', ['author_name' => 'Aris Defiana, S.Kom']);
    }

    public function test_book_authors_migration_moves_existing_author_and_can_be_rolled_back(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $book = $this->makeBook($admin);
        $penulis = $book->authors->first()->author_id;
        $migration = require database_path('migrations/2026_10_07_100000_create_book_authors_table.php');

        // rollback: penulis pertama kembali ke books.author_id, tabel book_authors hilang
        $migration->down();
        $this->assertFalse(Schema::hasTable('book_authors'));
        $this->assertSame($penulis, (int) DB::table('books')->where('book_id', $book->book_id)->value('author_id'));

        // migrate lagi: author_id dipindah ke book_authors (urutan 1) lalu kolomnya dihapus
        $migration->up();
        $this->assertFalse(Schema::hasColumn('books', 'author_id'));
        $this->assertDatabaseHas('book_authors', ['book_id' => $book->book_id, 'author_id' => $penulis, 'urutan' => 1]);
    }

    public function test_custom_loan_duration_is_limited_to_14_days(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin, 3);

        $this->actingAs($borrower)->get('/peminjaman/buat')->assertOk()
            ->assertSee('Jumlah hari (1 sampai 14)')
            ->assertSee('max="14"', false);

        // lebih dari 14 hari ditolak
        foreach ([15, 30] as $hari) {
            $this->from('/peminjaman/buat')
                ->post('/peminjaman', ['book_id' => $book->book_id, 'durasi' => 'custom', 'durasi_custom' => $hari])
                ->assertRedirect('/peminjaman/buat')
                ->assertSessionHasErrors(['durasi_custom' => 'Durasi custom maksimal 14 hari.']);
        }
        $this->assertDatabaseCount('loans', 0);

        // tepat 14 hari diterima
        $this->post('/peminjaman', ['book_id' => $book->book_id, 'durasi' => 'custom', 'durasi_custom' => 14])
            ->assertRedirect(route('peminjaman.index'));
        $this->assertSame(today()->addDays(14)->format('Y-m-d'),
            Loan::where('user_id', $borrower->user_id)->firstOrFail()->due_date->format('Y-m-d'));
    }

    public function test_loan_form_shows_selected_book_cover_panel(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin);
        $book->update(['location' => 'Rak U-01']);

        // tanpa buku terpilih: panel sampul menampilkan petunjuk; hanya ada tombol Kirim Permintaan & Batal
        $this->actingAs($borrower)->get('/peminjaman/buat')->assertOk()
            ->assertSee('Sampul buku akan tampil di sini setelah kamu memilih buku.')
            ->assertSee('Kirim Permintaan')
            ->assertDontSee('Pinjam Lagi')
            ->assertSee('href="' . route('dashboard') . '" class="tombol-pil-batal"', false);

        // dari tombol "Pinjam Buku": panel langsung berisi buku itu, Batal kembali ke detail buku
        $this->get('/peminjaman/buat?buku=' . $book->book_id)->assertOk()
            ->assertSeeInOrder(['id="panel-judul"', 'Buku Uji', 'Penulis Uji', '3 dari 3', 'Rak U-01'], false)
            ->assertSee('data-kategori="Kategori Uji"', false)
            ->assertSee('href="' . route('buku.detail', $book->book_id) . '" class="tombol-pil-batal"', false);

        // ?buku= yang tidak ada / bukan angka: Batal kembali ke katalog, bukan ke halaman 404
        foreach (['999999', 'abc'] as $nilai) {
            $this->get('/peminjaman/buat?buku=' . $nilai)->assertOk()
                ->assertSee('href="' . route('dashboard') . '" class="tombol-pil-batal"', false);
        }

        // Kirim Permintaan menuju daftar peminjaman
        $this->post('/peminjaman', ['book_id' => $book->book_id, 'durasi' => '3'])
            ->assertRedirect(route('peminjaman.index'));
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

        $buatBuku = fn (string $judul, Category $kat, int $stok = 3) => Book::create([
            'category_id' => $kat->category_id, 'title' => $judul,
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
        $buatBuku = fn (string $judul) => Book::create([
            'category_id' => $category->category_id, 'title' => $judul,
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

    public function test_notifications_page_shows_auto_failed_loan_right_away(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin, 2);
        $loan = $this->makeLoan($borrower, $book, [
            'status' => 'Dikonfirmasi',
            'approved_by' => $admin->user_id,
            'approved_at' => now()->subDays(3),
        ]);

        // halaman pertama yang dibuka peminjam adalah Notifikasi: pembatalan otomatis langsung tampil
        $this->actingAs($borrower)->get('/notifikasi')->assertOk()
            ->assertSee('dibatalkan otomatis karena melewati tenggat pengambilan');
        $this->assertSame('Gagal', $loan->fresh()->status);
    }

    public function test_overdue_fine_and_contact_information_for_manual_reminder(): void
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
            ->assertSee('Kembalikan & Lunasi');

        $this->get('/admin/peminjam')->assertOk()->assertSee($borrower->email);

        // buku terlambat tidak bisa dikembalikan tanpa melunasi denda: hanya ada satu tombol gabungan
        $this->get('/admin/riwayat')->assertOk()
            ->assertSee('Kembalikan & Lunasi Denda (Rp3.000)')
            ->assertDontSee('action="' . route('admin.peminjaman.kembalikan', $loan->loan_id) . '"', false);
        $this->put('/admin/riwayat/' . $loan->loan_id . '/kembalikan')->assertSessionHas('error');
        $this->assertSame('Dipinjam', $loan->fresh()->status);
        $this->assertSame(2, $book->fresh()->available_stock);

        // peminjam diberi tahu bahwa denda dibayar saat mengembalikan buku
        $this->actingAs($borrower)->get('/peminjaman/' . $loan->loan_id)->assertOk()
            ->assertSee('Denda dibayar langsung saat mengembalikan buku ke perpustakaan');
        $this->actingAs($admin);

        // pengingat keterlambatan dilakukan manual oleh admin lewat nomor telepon, tidak ada pengingat otomatis
        $this->assertDatabaseCount('notifications', 0);

        // bayar denda = sekaligus mengembalikan buku
        $this->put('/admin/riwayat/' . $loan->loan_id . '/denda')
            ->assertRedirect()
            ->assertSessionHas('status', 'Buku dikembalikan dan denda Rp3.000 lunas.');
        $loan->refresh();
        $this->assertNotNull($loan->fine_paid_at);
        $this->assertSame($admin->user_id, $loan->fine_paid_by);
        $this->assertSame('Dikembalikan', $loan->status);
        $this->assertSame(today()->format('Y-m-d'), $loan->return_date->format('Y-m-d'));
        $this->assertSame(3000, $loan->jumlah_denda);
        $this->assertSame(3, $book->fresh()->available_stock);
        $this->assertDatabaseHas('notifications', ['loan_id' => $loan->loan_id, 'type' => 'Dikembalikan']);

        // fine_paid_by adalah foreign key ke users: id user yang tidak ada ditolak,
        // dan admin yang tercatat mengonfirmasi pembayaran tidak bisa dihapus
        $this->assertThrows(fn () => DB::table('loans')->where('loan_id', $loan->loan_id)->update(['fine_paid_by' => 999999]), QueryException::class);
        $this->assertThrows(fn () => DB::table('users')->where('user_id', $admin->user_id)->delete(), QueryException::class);
        $this->assertSame($admin->user_id, $loan->fresh()->fine_paid_by);

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

        // data lama: sudah dikembalikan tapi denda belum lunas -> riwayat tidak bisa dihapus sampai dilunasi
        $lama = $this->makeLoan($borrower, $book, [
            'status' => 'Dikembalikan', 'loan_date' => today()->subDays(10),
            'due_date' => today()->subDays(5), 'return_date' => today()->subDays(3),
        ]);
        $this->get('/admin/riwayat')->assertOk()
            ->assertSee('Tandai Lunas')
            ->assertDontSee('action="' . route('admin.peminjaman.destroy', $lama->loan_id) . '"', false);
        $this->delete('/admin/riwayat/' . $lama->loan_id)->assertSessionHas('error');
        $this->assertDatabaseHas('loans', ['loan_id' => $lama->loan_id]);

        $this->put('/admin/riwayat/' . $lama->loan_id . '/denda')
            ->assertSessionHas('status', 'Status denda berhasil diubah menjadi Lunas.');
        $this->assertSame(3, $book->fresh()->available_stock);
        $this->delete('/admin/riwayat/' . $lama->loan_id)->assertRedirect(route('admin.peminjaman.riwayat'));
        $this->assertDatabaseMissing('loans', ['loan_id' => $lama->loan_id]);
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
        $this->assertDatabaseHas('admin_logs', [
            'admin_id' => $admin->user_id, 'action' => 'Hapus', 'table_name' => 'reviews', 'record_id' => $review->review_id,
            'description' => 'Menghapus ulasan 4 bintang oleh Peminjam Uji untuk buku "Buku Uji"',
        ]);
    }

    public function test_admin_borrower_list_is_searchable_and_paginated_by_ten(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        // 12 peminjam: P01..P12, nama "Siswa 01".."Siswa 12", kelas XI-A (ganjil) / XII-B (genap)
        for ($i = 1; $i <= 12; $i++) {
            $no = str_pad($i, 2, '0', STR_PAD_LEFT);
            $u = $this->makeUser('Peminjam', 'P' . $no);
            $u->update(['name' => 'Siswa ' . $no, 'class' => $i % 2 ? 'XI-A' : 'XII-B']);
        }

        // 10 per halaman, urut nama; halaman 2 berisi sisanya
        $this->actingAs($admin)->get('/admin/peminjam')->assertOk()
            ->assertSee('Cari NIS/NIP, nama, atau kelas')
            ->assertSee('Menampilkan 1–10 dari', false)
            ->assertSee('Siswa 10')->assertDontSee('Siswa 11')
            ->assertSee('Halaman 1 dari 2');
        $this->get('/admin/peminjam?page=2')->assertOk()
            ->assertSee('Siswa 11')->assertSee('Siswa 12')->assertDontSee('Siswa 01')
            ->assertSee('Menampilkan 11–12 dari', false);
        $this->get('/admin/peminjam?page=9')->assertRedirect('/admin/peminjam?page=2');

        // pencarian NIS/NIP, nama, atau kelas
        $this->get('/admin/peminjam?q=P07')->assertOk()->assertSee('Siswa 07')->assertDontSee('Siswa 08');
        $this->get('/admin/peminjam?q=siswa 12')->assertOk()->assertSee('P12')->assertDontSee('P11');
        $this->get('/admin/peminjam?q=XII-B')->assertOk()
            ->assertSee('Siswa 02')->assertDontSee('Siswa 01')
            ->assertSee('dari <strong>6</strong>', false)->assertSee('peminjam yang cocok (total 12)');
        $this->get('/admin/peminjam?q=tidakada')->assertOk()
            ->assertSee('Tidak ada peminjam dengan NIS/NIP, nama, atau kelas yang cocok dengan “tidakada”.', false);
    }

    public function test_incoming_requests_are_searchable_filterable_and_paginated_by_ten(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $bukuA = $this->makeBook($admin, 20);
        $bukuB = $bukuA->replicate()->fill(['title' => 'Buku Kimia']);
        $bukuB->save();

        // 12 pengajuan menunggu: Pemohon 01..12; 01-03 meminjam Buku Kimia, sisanya Buku Uji
        for ($i = 1; $i <= 12; $i++) {
            $no = str_pad($i, 2, '0', STR_PAD_LEFT);
            $u = $this->makeUser('Peminjam', 'R' . $no);
            $u->update(['name' => 'Pemohon ' . $no]);
            $this->makeLoan($u, $i <= 3 ? $bukuB : $bukuA, ['loan_date' => null]);
        }

        // 10 per halaman, dengan pencarian & filter buku
        $this->actingAs($admin)->get('/admin/peminjaman')->assertOk()
            ->assertSee('Cari nama peminjam atau NIS...')
            ->assertSee('data-meta="3 pengajuan"', false)
            ->assertSee('Menampilkan 1–10 dari', false)
            ->assertSee('Pemohon 10')->assertDontSee('Pemohon 11')
            ->assertSee('Halaman 1 dari 2');
        $this->get('/admin/peminjaman?page=2')->assertOk()->assertSee('Pemohon 12')->assertDontSee('Pemohon 01');

        $this->get('/admin/peminjaman?q=pemohon 05')->assertOk()->assertSee('Pemohon 05')->assertDontSee('Pemohon 06');
        $this->get('/admin/peminjaman?q=R07')->assertOk()->assertSee('Pemohon 07')->assertDontSee('Pemohon 08');
        $this->get('/admin/peminjaman?buku=' . $bukuB->book_id)->assertOk()
            ->assertSee('Pemohon 03')->assertDontSee('Pemohon 04')
            ->assertSee('dari <strong>3</strong>', false);
        $this->get('/admin/peminjaman?buku=' . $bukuB->book_id . '&q=Pemohon 09')->assertOk()
            ->assertSee('Tidak ada pengajuan untuk "Buku Kimia" dari peminjam yang cocok dengan “Pemohon 09”.');

        // konfirmasi / tolak dari hasil filter: kembali ke filter & halaman yang sama
        $loan = Loan::where('book_id', $bukuB->book_id)->first();
        $this->put('/admin/peminjaman/' . $loan->loan_id . '/setujui', ['buku' => $bukuB->book_id, 'page' => 1])
            ->assertRedirect(route('admin.peminjaman.index', ['buku' => $bukuB->book_id, 'page' => 1]));
        $this->assertSame('Dikonfirmasi', $loan->fresh()->status);
        $lain = Loan::where('book_id', $bukuB->book_id)->where('status', 'Menunggu')->first();
        $this->put('/admin/peminjaman/' . $lain->loan_id . '/tolak', ['q' => 'Pemohon', 'buku' => $bukuB->book_id])
            ->assertRedirect(route('admin.peminjaman.index', ['q' => 'Pemohon', 'buku' => $bukuB->book_id]));
    }

    public function test_admin_history_is_paginated_by_ten_and_actions_keep_status_filter(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $book = $this->makeBook($admin, 30);
        for ($i = 1; $i <= 12; $i++) {
            $no = str_pad($i, 2, '0', STR_PAD_LEFT);
            $u = $this->makeUser('Peminjam', 'H' . $no);
            $u->update(['name' => 'Riwayat ' . $no]);
            $this->makeLoan($u, $book, ['status' => $i <= 11 ? 'Dipinjam' : 'Gagal', 'due_date' => today()->addDays(3)]);
        }

        // urut terbaru dulu: halaman 1 berisi Riwayat 12..03, halaman 2 berisi 02..01
        $this->actingAs($admin)->get('/admin/riwayat')->assertOk()
            ->assertSee('Menampilkan 1–10 dari', false)
            ->assertSee('Riwayat 12')->assertSee('Riwayat 03')->assertDontSee('Riwayat 02')
            ->assertSee('Halaman 1 dari 2');
        $this->get('/admin/riwayat?page=2')->assertOk()->assertSee('Riwayat 01')->assertDontSee('Riwayat 03');

        // filter status tetap terbawa saat pindah halaman; status tak dikenal = semua
        $this->get('/admin/riwayat?status=Dipinjam')->assertOk()
            ->assertSee('dari <strong>11</strong> peminjaman berstatus Dipinjam', false)
            ->assertSee('status=Dipinjam&amp;page=2', false);
        $this->get('/admin/riwayat?status=Ngawur')->assertOk()->assertSee('dari <strong>12</strong> peminjaman', false);

        // aksi dari halaman 2 filter Dipinjam: kembali ke tempat yang sama
        $loan = Loan::whereHas('user', fn ($q) => $q->where('name', 'Riwayat 01'))->first();
        $this->put('/admin/riwayat/' . $loan->loan_id . '/kembalikan', ['status' => 'Dipinjam', 'page' => 2])
            ->assertRedirect(route('admin.peminjaman.riwayat', ['status' => 'Dipinjam', 'page' => 2]));
        $this->assertSame('Dikembalikan', $loan->fresh()->status);

        // halaman 2 kini kosong (sisa 10 Dipinjam): dialihkan ke halaman terakhir
        $this->get('/admin/riwayat?status=Dipinjam&page=2')
            ->assertRedirect('/admin/riwayat?status=Dipinjam&page=1');
    }

    public function test_admin_can_search_and_filter_reviews_by_book_and_rating(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $andi = $this->makeUser('Peminjam', 'SISWA-A');
        $andi->update(['name' => 'Andi Pembaca']);
        $sari = $this->makeUser('Peminjam', 'SISWA-B');
        $sari->update(['name' => 'Sari Pembaca']);
        $bukuA = $this->makeBook($admin);
        $bukuB = $bukuA->replicate()->fill(['title' => 'Buku Arsip', 'is_active' => 0]);
        $bukuB->save();

        Review::create(['user_id' => $andi->user_id, 'book_id' => $bukuA->book_id, 'rating' => 5, 'review_text' => 'Sangat bermanfaat.']);
        Review::create(['user_id' => $sari->user_id, 'book_id' => $bukuA->book_id, 'rating' => 2, 'review_text' => 'Kurang lengkap.']);
        $rendah = Review::create(['user_id' => $andi->user_id, 'book_id' => $bukuB->book_id, 'rating' => 1, 'review_text' => 'Halamannya rusak.']);

        // tanpa filter: semua ulasan, termasuk buku nonaktif (ditandai), dengan pencarian & filter
        $this->actingAs($admin)->get('/admin/ulasan')->assertOk()
            ->assertSee('Cari ulasan, pengguna, atau buku...')
            ->assertSee('Semua rating')
            ->assertSee('Menampilkan 1–3 dari', false)
            ->assertSee('Sangat bermanfaat.')->assertSee('Kurang lengkap.')->assertSee('Halamannya rusak.')
            ->assertSee('<span class="label-nonaktif">nonaktif</span>', false)
            ->assertSee('data-meta="1 ulasan · nonaktif"', false);

        // pencarian: isi komentar, nama pengguna, atau judul buku
        $this->get('/admin/ulasan?q=rusak')->assertOk()
            ->assertSee('Halamannya rusak.')->assertDontSee('Sangat bermanfaat.');
        $this->get('/admin/ulasan?q=Sari')->assertOk()
            ->assertSee('Kurang lengkap.')->assertDontSee('Halamannya rusak.');
        $this->get('/admin/ulasan?q=Arsip')->assertOk()
            ->assertSee('Halamannya rusak.')->assertDontSee('Kurang lengkap.');

        // filter buku + rating (boleh lebih dari satu); jumlah per rating mengikuti buku terpilih
        $this->get('/admin/ulasan?buku=' . $bukuA->book_id)->assertOk()
            ->assertSee('Sangat bermanfaat.')->assertSee('Kurang lengkap.')->assertDontSee('Halamannya rusak.');
        $this->get('/admin/ulasan?bintang[]=1&bintang[]=2')->assertOk()
            ->assertSee('2, 1 bintang')->assertSee('Kurang lengkap.')->assertSee('Halamannya rusak.')
            ->assertDontSee('Sangat bermanfaat.');
        $this->get('/admin/ulasan?buku=' . $bukuA->book_id . '&bintang[]=1')->assertOk()
            ->assertSee('Tidak ada ulasan dengan rating 1 bintang untuk "Buku Uji".')
            ->assertSee('Hapus filter');

        // hapus dari hasil filter: kembali ke daftar dengan filter yang sama
        $this->delete('/admin/ulasan/' . $rendah->review_id, ['q' => 'rusak', 'bintang' => [1], 'page' => 1])
            ->assertRedirect(route('admin.reviews.index', ['q' => 'rusak', 'bintang' => [1], 'page' => 1]));
        $this->assertDatabaseMissing('reviews', ['review_id' => $rendah->review_id]);
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

    public function test_notifications_page_shows_all_types_and_filters_by_type(): void
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
            'user_id' => $borrower->user_id, 'loan_id' => $loan->loan_id, 'type' => 'Dikonfirmasi',
            'message' => 'Peminjaman sudah dikonfirmasi.', 'sent_at' => now(), 'status' => 'Terkirim',
        ]);
        UserNotification::create([
            'user_id' => $borrower->user_id, 'type' => 'Pengumuman',
            'message' => 'Perpustakaan tutup hari Minggu.', 'sent_at' => now(), 'status' => 'Terkirim',
        ]);

        // fitur e-book sudah dihapus: pengumuman tampil tanpa tautan ke halaman e-book
        $this->actingAs($borrower)->get('/notifikasi')->assertOk()
            ->assertSee('Pengajuan peminjaman terkirim.')
            ->assertSee('Peminjaman sudah dikonfirmasi.')
            ->assertSee('Perpustakaan tutup hari Minggu.')
            ->assertDontSee('E-book');

        // filter per jenis: hanya notifikasi jenis itu yang tampil
        $this->get('/notifikasi?jenis=pengajuan')->assertOk()
            ->assertSee('Pengajuan peminjaman terkirim.')
            ->assertDontSee('Peminjaman sudah dikonfirmasi.')
            ->assertDontSee('Perpustakaan tutup hari Minggu.');

        $this->get('/notifikasi?jenis=dikonfirmasi')->assertOk()
            ->assertSee('Peminjaman sudah dikonfirmasi.')
            ->assertDontSee('Pengajuan peminjaman terkirim.');

        $this->get('/notifikasi?jenis=pengumuman')->assertOk()
            ->assertSee('Perpustakaan tutup hari Minggu.')
            ->assertDontSee('Pengajuan peminjaman terkirim.');

        // jenis yang belum punya notifikasi: pesan kosong khusus jenis itu
        $this->get('/notifikasi?jenis=gagal')->assertOk()
            ->assertSee('Belum ada notifikasi Gagal.')
            ->assertDontSee('Pengajuan peminjaman terkirim.');

        // filter yang tidak dikenal dianggap Semua
        $this->get('/notifikasi?jenis=ngawur')->assertOk()
            ->assertSee('Pengajuan peminjaman terkirim.')
            ->assertSee('Perpustakaan tutup hari Minggu.');

        // lima filter utama selalu ada; jenis lain hanya bila ada isinya; angka sama di filter mana pun
        foreach (['', '?jenis=pengajuan', '?jenis=gagal', '?jenis=ngawur'] as $filter) {
            $this->get('/notifikasi' . $filter)->assertOk()
                ->assertSeeInOrder(['Semua (3)', 'Pengajuan (1)', 'Dikonfirmasi (1)', 'Dipinjam (0)',
                    'Dikembalikan (0)', 'Gagal (0)', 'Pengumuman (1)'])
                ->assertDontSee('Terlambat (');
        }
    }

    public function test_notifications_page_has_no_fake_controls(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin);
        $loan = $this->makeLoan($borrower, $book, ['status' => 'Dipinjam', 'due_date' => today()->subDays(2)]);
        UserNotification::create([
            'user_id' => $borrower->user_id, 'loan_id' => $loan->loan_id, 'type' => 'Terlambat',
            'message' => 'Buku sudah melewati batas pengembalian.', 'sent_at' => now(), 'status' => 'Terkirim',
        ]);

        // tidak ada tombol perpanjang (fiturnya tidak ada), cukup tautan ke detail peminjaman
        // notifikasi Terlambat dari data lama tetap punya filter sendiri
        foreach (['', '?jenis=terlambat'] as $filter) {
            $this->actingAs($borrower)->get('/notifikasi' . $filter)->assertOk()
                ->assertSee('Buku sudah melewati batas pengembalian.')
                ->assertSee('Lihat Peminjaman')
                ->assertSee('Terlambat (1)')
                ->assertDontSee('Perpanjang')
                ->assertDontSee('type="checkbox"', false)
                // notifikasi hanya di dalam web: tidak ada lagi info salinan ke email
                ->assertDontSee('Salinan ke email')
                ->assertDontSee('siswa-test@example.test');
        }
    }

    public function test_new_notification_popup_appears_until_marked_as_read(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $other = $this->makeUser('Peminjam', 'SISWA-LAIN');
        $book = $this->makeBook($admin, 3);

        // pengajuan dikirim peminjam sendiri: tersimpan sebagai sudah dibaca, tidak ada pop up
        $this->actingAs($borrower)->post('/peminjaman', ['book_id' => $book->book_id, 'durasi' => 3]);
        $loan = Loan::where('user_id', $borrower->user_id)->firstOrFail();
        $this->assertDatabaseHas('notifications', ['loan_id' => $loan->loan_id, 'type' => 'Pengajuan', 'status' => 'Dibaca']);
        $this->get('/dashboard')->assertOk()->assertDontSee('data-popup-notif', false)->assertDontSee('menu-angka');

        // admin mengonfirmasi saat peminjam tidak membuka web
        $this->actingAs($admin)->put('/admin/peminjaman/' . $loan->loan_id . '/setujui');
        $baru = UserNotification::where('loan_id', $loan->loan_id)->where('type', 'Dikonfirmasi')->firstOrFail();
        $this->assertSame('Belum Dibaca', $baru->status);

        // saat peminjam kembali, pop up muncul di halaman mana pun + angka di menu Notifikasi
        foreach (['/dashboard', '/profil', '/peminjaman'] as $halaman) {
            $this->actingAs($borrower)->get($halaman)->assertOk()
                ->assertSee('data-popup-notif', false)
                ->assertSee('Ada 1 notifikasi baru')
                ->assertSee($baru->message)
                ->assertSee('<span class="menu-angka"', false)
                ->assertSee('name="sampai" value="' . $baru->notification_id . '"', false);
        }

        // notifikasi peminjam lain tidak ikut tampil
        $this->actingAs($other)->get('/dashboard')->assertOk()->assertDontSee('data-popup-notif', false);

        // notifikasi yang masuk setelah pop up tampil tidak ikut ditandai dibaca
        $loan->update(['status' => 'Dikonfirmasi']);
        $this->actingAs($admin)->put('/admin/riwayat/' . $loan->loan_id . '/dipinjam');
        $lebihBaru = UserNotification::where('loan_id', $loan->loan_id)->where('type', 'Dipinjam')->firstOrFail();

        // tombol Mengerti (tanpa JavaScript): kembali ke halaman sebelumnya
        $this->actingAs($borrower)->from('/profil')
            ->post('/notifikasi/dibaca', ['sampai' => $baru->notification_id])
            ->assertRedirect('/profil');
        $this->assertSame('Dibaca', $baru->fresh()->status);
        $this->assertSame('Belum Dibaca', $lebihBaru->fresh()->status);
        $this->get('/profil')->assertOk()->assertSee('Ada 1 notifikasi baru')->assertSee($lebihBaru->message);

        // ditutup lewat JavaScript: jawaban tanpa isi, lalu pop up tidak muncul lagi
        $this->postJson('/notifikasi/dibaca', ['sampai' => $lebihBaru->notification_id])->assertNoContent();
        $this->get('/dashboard')->assertOk()->assertDontSee('data-popup-notif', false)->assertDontSee('menu-angka');
    }

    public function test_notification_popup_limits_list_and_notifications_page_marks_shown_as_read(): void
    {
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $book = $this->makeBook($admin, 3);
        $loan = $this->makeLoan($borrower, $book);

        foreach (range(1, 6) as $i) {
            UserNotification::kirim($loan, 'Dikonfirmasi', 'Konfirmasi ke-' . $i);
        }
        UserNotification::kirim($loan, 'Gagal', 'Peminjaman gagal.');

        // pop up hanya memuat 5 terbaru, sisanya disebut jumlahnya
        $this->actingAs($borrower)->get('/dashboard')->assertOk()
            ->assertSee('Ada 7 notifikasi baru')
            ->assertSee('Peminjaman gagal.')
            ->assertSee('Konfirmasi ke-3')
            ->assertDontSee('Konfirmasi ke-2')
            ->assertDontSee('Konfirmasi ke-1')
            ->assertSee('dan 2 notifikasi lainnya');

        // filter Gagal: hanya yang tampil ditandai "Baru" lalu dibaca; tidak ada pop up di halaman Notifikasi
        $this->get('/notifikasi?jenis=gagal')->assertOk()
            ->assertSee('notif-badge baru', false)
            ->assertDontSee('data-popup-notif', false);
        $this->assertSame(6, UserNotification::where('user_id', $borrower->user_id)->belumDibaca()->count());

        // halaman Notifikasi tanpa filter: semua yang tampil dibaca
        $this->get('/notifikasi')->assertOk()->assertSee('notif-badge baru', false);
        $this->assertSame(0, UserNotification::where('user_id', $borrower->user_id)->belumDibaca()->count());
        $this->get('/notifikasi')->assertOk()->assertDontSee('notif-badge baru', false);
        $this->get('/dashboard')->assertOk()->assertDontSee('data-popup-notif', false);
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

        $book = Book::create([
            'category_id' => $category->category_id,
            'title' => 'Buku Uji',
            'publisher' => 'Penerbit Uji',
            'publication_year' => 2026,
            'location' => 'Rak U-01',
            'stock' => $stock,
            'available_stock' => $stock,
            'is_active' => true,
            'created_by' => $admin->user_id,
        ]);
        $book->authors()->attach($author->author_id, ['urutan' => 1]);

        return $book;
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
