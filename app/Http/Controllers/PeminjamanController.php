<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PeminjamanController extends Controller
{
    // batas jumlah pengajuan + peminjaman aktif per user (boleh diubah)
    const BATAS_AKTIF = 3;

    // durasi custom maksimal (hari)
    const MAKS_DURASI = 14;

    // ---------------------------------------------------------
    // READ: daftar peminjaman milik user yang login
    // ---------------------------------------------------------
    public function index(Request $request)
    {
        Loan::prosesTenggatPengambilan();

        $filter = $request->filter;

        // semua peminjaman user: dipakai kartu ringkasan & angka di tab filter, jadi tidak ikut terfilter
        $semua = Loan::where('user_id', Auth::id())
            ->with(['book.authors', 'book.category', 'approver'])
            ->orderBy('loan_id', 'desc')
            ->get();

        // aturan tiap tab filter
        $aturanFilter = [
            'aktif'     => fn ($p) => $p->status == 'Dipinjam',
            'menunggu'  => fn ($p) => in_array($p->status, ['Menunggu', 'Dikonfirmasi']),
            'selesai'   => fn ($p) => $p->status == 'Dikembalikan',
            'terlambat' => fn ($p) => $p->jumlah_denda > 0 && ! $p->denda_lunas,   // punya denda yang belum lunas
            'gagal'     => fn ($p) => $p->status == 'Gagal',
        ];

        // filter yang tidak dikenal dianggap "Semua"
        if (! isset($aturanFilter[$filter])) {
            $filter = null;
        }

        // daftar untuk tabel sesuai tab filter yang dipilih
        $daftar = $filter ? $semua->filter($aturanFilter[$filter]) : $semua;

        // jumlah data di setiap tab filter
        $jumlahFilter = collect($aturanFilter)->map(fn ($aturan) => $semua->filter($aturan)->count());

        return view('user.peminjaman-saya', [
            'semua'        => $semua,
            'daftar'       => $daftar->values(),
            'filter'       => $filter,
            'jumlahFilter' => $jumlahFilter,
        ]);
    }

    // ---------------------------------------------------------
    // form peminjaman
    // ---------------------------------------------------------
    public function create(Request $request)
    {
        Loan::prosesTenggatPengambilan();

        // hanya buku aktif yang stoknya masih ada (penulis & kategori ikut dimuat untuk pencarian dan panel sampul)
        $daftarBuku = Book::aktif()
            ->with(['authors', 'category'])
            ->where('available_stock', '>', 0)
            ->orderBy('title')
            ->get();

        // tombol Batal kembali ke detail buku asal, hanya bila buku itu memang ada di katalog
        $bukuAsal = ctype_digit((string) $request->buku) ? Book::aktif()->find($request->buku) : null;

        return view('user.peminjaman-form', [
            'daftarBuku'  => $daftarBuku,
            'bukuDipilih' => $request->buku,
            'urlBatal'    => $bukuAsal ? route('buku.detail', $bukuAsal->book_id) : route('dashboard'),
            // buku yang baru dikembalikan user ini: tampil di daftar tapi belum bisa dipilih
            'jedaPinjam'  => Loan::jedaPinjamUlang(Auth::id()),
            'maksDurasi'  => self::MAKS_DURASI,
        ]);
    }

    // ---------------------------------------------------------
    // CREATE: simpan pengajuan ke tabel loans (status Menunggu)
    // ---------------------------------------------------------
    public function store(Request $request)
    {
        Loan::prosesTenggatPengambilan();

        $request->validate([
            'book_id'       => 'required|exists:books,book_id',
            'durasi'        => 'required|in:1,3,5,7,custom',
            // jumlah hari custom hanya diperiksa (dan dipakai) bila durasi yang dipilih memang custom
            'durasi_custom' => 'exclude_unless:durasi,custom|required|integer|min:1|max:' . self::MAKS_DURASI,
        ], [
            'required'             => ':attribute wajib diisi.',
            'exists'               => ':attribute tidak ditemukan.',
            'in'                   => ':attribute tidak valid.',
            'integer'              => ':attribute harus berupa angka.',
            'min'                  => ':attribute minimal :min hari.',
            'max'                  => ':attribute maksimal :max hari.',
            'durasi_custom.required' => 'Isi jumlah hari untuk durasi custom.',
        ], [
            'book_id'       => 'Nama buku',
            'durasi'        => 'Durasi',
            'durasi_custom' => 'Durasi custom',
        ]);

        // cek buku: harus aktif dan stoknya masih ada
        $buku = Book::aktif()->find($request->book_id);
        if (!$buku) {
            return back()->withInput()->withErrors(['book_id' => 'Buku ini tidak ada di katalog.']);
        }
        if ($buku->available_stock < 1) {
            return back()->withInput()->withErrors(['book_id' => 'Stok buku ini sedang habis.']);
        }

        $idUser = Auth::id();

        // tidak boleh mengajukan buku yang sama kalau masih aktif
        $sudahAda = Loan::where('user_id', $idUser)
            ->where('book_id', $buku->book_id)
            ->whereIn('status', ['Menunggu', 'Dikonfirmasi', 'Dipinjam'])
            ->exists();
        if ($sudahAda) {
            return back()->withInput()->withErrors([
                'book_id' => 'Kamu masih punya pengajuan atau peminjaman aktif untuk buku ini.',
            ]);
        }

        // jeda pinjam ulang: buku yang baru dikembalikan belum bisa dipinjam lagi oleh user yang sama
        $bolehMulai = Loan::jedaPinjamUlang($idUser, $buku->book_id)->first();
        if ($bolehMulai) {
            return back()->withInput()->withErrors([
                'book_id' => 'Kamu baru saja mengembalikan buku ini. Agar pembaca lain juga mendapat kesempatan, '
                    . 'buku yang sama baru bisa kamu pinjam lagi mulai ' . $bolehMulai->format('d M Y') . '.',
            ]);
        }

        // batas jumlah peminjaman aktif
        $jumlahAktif = Loan::where('user_id', $idUser)
            ->whereIn('status', ['Menunggu', 'Dikonfirmasi', 'Dipinjam'])
            ->count();
        if ($jumlahAktif >= self::BATAS_AKTIF) {
            return back()->withInput()->withErrors([
                'book_id' => 'Batas peminjaman aktif adalah ' . self::BATAS_AKTIF . ' buku. Kembalikan atau batalkan salah satu dulu.',
            ]);
        }

        // hitung perkiraan batas pengembalian dari hari pengajuan (hari ini).
        // batas sebenarnya digeser saat admin mengubah status menjadi Dipinjam.
        if ($request->durasi == 'custom') {
            $lama = (int) $request->durasi_custom;
        } else {
            $lama = (int) $request->durasi;
        }
        $tglKembali = today()->addDays($lama);

        // simpan ke tabel loans (request_date terisi otomatis oleh database).
        // loan_date dibiarkan kosong: tanggal pinjam baru diisi saat buku diambil.
        $loan = Loan::create([
            'user_id'   => $idUser,
            'book_id'   => $buku->book_id,
            'loan_date' => null,
            'due_date'  => $tglKembali->format('Y-m-d'),
            'status'    => 'Menunggu',
        ]);

        UserNotification::kirim(
            $loan,
            'Pengajuan',
            'Pengajuan peminjaman buku "' . $buku->title . '" sudah diterima dan sedang menunggu konfirmasi admin.'
        );

        // semua admin diberi tahu lewat pop up bahwa ada pengajuan yang perlu diproses
        $peminjam = Auth::user();
        UserNotification::kirimKeAdmin(
            $loan,
            'Pengajuan',
            'Pengajuan baru dari ' . $peminjam->name . ' (' . $peminjam->nim_nip . '): buku "' . $buku->title
                . '" selama ' . $lama . ' hari (' . $loan->fresh()->kode . ').'
        );

        return redirect()->route('peminjaman.index')
            ->with('status', 'Permintaan terkirim. Tunggu konfirmasi admin.');
    }

    // ---------------------------------------------------------
    // READ: detail satu peminjaman (hanya milik sendiri)
    // ---------------------------------------------------------
    public function show($id)
    {
        Loan::prosesTenggatPengambilan();

        $pinjam = Loan::where('user_id', Auth::id())
            ->with(['book.authors', 'book.category', 'approver'])
            ->findOrFail($id);

        return view('user.peminjaman-detail', ['pinjam' => $pinjam]);
    }

    // ---------------------------------------------------------
    // DELETE: batalkan pengajuan (hanya yang masih Menunggu)
    // ---------------------------------------------------------
    public function destroy($id)
    {
        Loan::prosesTenggatPengambilan();

        $pinjam = Loan::where('user_id', Auth::id())
            ->where('status', 'Menunggu')
            ->findOrFail($id);

        // pengajuan batal: pop up "Pengajuan baru" di halaman admin tidak perlu muncul lagi
        UserNotification::tandaiSelesai($pinjam->loan_id, ['Pengajuan']);
        UserNotification::where('loan_id', $pinjam->loan_id)->update(['loan_id' => null]);
        $pinjam->delete();

        return redirect()->route('peminjaman.index')
            ->with('status', 'Pengajuan dibatalkan.');
    }
}
