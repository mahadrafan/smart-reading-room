<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Review;
use App\Models\User;
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
        $this->get('/ebooks')->assertOk();
        $this->get('/notifikasi')->assertOk();
        $this->get('/profil')->assertOk();
        $this->get('/admin/dashboard')->assertRedirect(route('dashboard'));

        $this->actingAs($admin);
        $this->get('/admin/dashboard')->assertOk();
        $this->get('/admin/buku')->assertOk();
        $this->get('/admin/buku/create')->assertOk();
        $this->get('/admin/kategori-penulis')->assertOk();
        $this->get('/admin/peminjam')->assertOk();
        $this->get('/admin/ulasan')->assertOk();
        $this->get('/admin/ebooks')->assertOk();
        $this->get('/admin/ebooks/tambah')->assertOk();
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

        $this->post('/admin/kategori-penulis/kategori', [
            'category_name' => 'Sains',
            'description' => 'Buku sains',
        ])->assertRedirect(route('admin.kp.index'));
        $category = Category::where('category_name', 'Sains')->firstOrFail();

        $this->post('/admin/kategori-penulis/penulis', [
            'author_name' => 'Penulis Uji',
            'biography' => 'Biografi pengujian.',
        ])->assertRedirect(route('admin.kp.index'));
        $author = Author::where('author_name', 'Penulis Uji')->firstOrFail();

        $this->put('/admin/kategori-penulis/kategori/' . $category->category_id, [
            'category_name' => 'Sains Terapan',
            'description' => 'Diperbarui',
        ])->assertRedirect(route('admin.kp.index'));
        $this->put('/admin/kategori-penulis/penulis/' . $author->author_id, [
            'author_name' => 'Penulis Diperbarui',
            'biography' => 'Diperbarui.',
        ])->assertRedirect(route('admin.kp.index'));

        $this->post('/admin/buku', [
            'category_id' => $category->category_id,
            'author_id' => $author->author_id,
            'title' => 'Buku Uji Fitur',
            'publisher' => 'Penerbit Uji',
            'publication_year' => 2026,
            'description' => 'Deskripsi buku uji.',
            'location' => 'Rak U-01',
            'stock' => 4,
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 300, 450),
        ])->assertRedirect(route('admin.buku.index'));

        $book = Book::where('title', 'Buku Uji Fitur')->firstOrFail();
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
        $this->assertDatabaseCount('admin_logs', 7);
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
        $this->get('/buku/' . $book->book_id)->assertOk()->assertSee('Sangat membantu');

        $this->post('/buku/' . $book->book_id . '/ulasan', [
            'rating' => 4,
            'review_text' => 'Komentar diperbarui.',
        ]);
        $this->assertDatabaseCount('reviews', 1);
        $this->assertSame(4, $review->fresh()->rating);

        $this->actingAs($admin)->get('/admin/ulasan')->assertOk()->assertSee('Komentar diperbarui.');
        $this->delete('/admin/ulasan/' . $review->review_id)->assertRedirect(route('admin.reviews.index'));
        $this->assertDatabaseMissing('reviews', ['review_id' => $review->review_id]);
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

    public function test_admin_can_upload_user_can_download_and_admin_can_delete_ebook(): void
    {
        Storage::fake('local');
        $admin = $this->makeUser('Admin', 'ADM-TEST');
        $borrower = $this->makeUser('Peminjam', 'SISWA-TEST');
        $category = Category::create(['category_name' => 'Digital']);
        $author = Author::create(['author_name' => 'Penulis Digital']);

        $this->actingAs($admin)->post('/admin/ebooks', [
            'category_id' => $category->category_id,
            'author_id' => $author->author_id,
            'title' => 'Panduan Digital',
            'publisher' => 'Penerbit Digital',
            'publication_year' => 2026,
            'description' => 'E-book pengujian.',
            'pdf_file' => UploadedFile::fake()->create('panduan.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('admin.ebooks.index'));

        $ebook = \App\Models\Ebook::firstOrFail();
        Storage::disk('local')->assertExists($ebook->file_url);

        $this->actingAs($borrower)->get('/ebooks')->assertOk()->assertSee('Panduan Digital');
        $this->get('/ebooks/' . $ebook->ebook_id . '/download')->assertOk()->assertDownload('Panduan Digital.pdf');

        $this->actingAs($admin)->delete('/admin/ebooks/' . $ebook->ebook_id)
            ->assertRedirect(route('admin.ebooks.index'));
        Storage::disk('local')->assertMissing($ebook->file_url);
        $this->assertDatabaseMissing('ebooks', ['ebook_id' => $ebook->ebook_id]);
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
