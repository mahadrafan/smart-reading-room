<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\UserNotification;
use App\Services\LoanNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PeminjamanController extends Controller
{
    // batas jumlah pengajuan + peminjaman aktif per user (boleh diubah)
    const BATAS_AKTIF = 3;

    // ---------------------------------------------------------
    // READ: daftar peminjaman milik user yang login
    // ---------------------------------------------------------
    public function index(Request $request)
    {
        Loan::prosesTenggatPengambilan();

        $filter = $request->filter;

        // semua peminjaman user: dipakai kartu ringkasan & angka di tab filter, jadi tidak ikut terfilter
        $semua = Loan::where('user_id', Auth::id())
            ->with(['book.author', 'book.category', 'approver'])
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

        // hanya buku aktif yang stoknya masih ada (penulis ikut dimuat untuk pencarian buku di form)
        $daftarBuku = Book::aktif()
            ->with('author')
            ->where('available_stock', '>', 0)
            ->orderBy('title')
            ->get();

        return view('user.peminjaman-form', [
            'daftarBuku'  => $daftarBuku,
            'bukuDipilih' => $request->buku,
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
            'durasi_custom' => 'required_if:durasi,custom|nullable|integer|min:1|max:30',
        ], [
            'required'             => ':attribute wajib diisi.',
            'exists'               => ':attribute tidak ditemukan.',
            'in'                   => ':attribute tidak valid.',
            'integer'              => ':attribute harus berupa angka.',
            'min'                  => ':attribute minimal :min hari.',
            'max'                  => ':attribute maksimal :max hari.',
            'durasi_custom.required_if' => 'Isi jumlah hari untuk durasi custom.',
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

        app(LoanNotificationService::class)->send(
            $loan->load(['user', 'book']),
            'Pengajuan',
            'Pengajuan peminjaman diterima',
            'Pengajuan peminjaman buku "' . $buku->title . '" sudah diterima dan sedang menunggu konfirmasi admin.'
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
            ->with(['book.author', 'book.category', 'approver'])
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

        UserNotification::where('loan_id', $pinjam->loan_id)->update(['loan_id' => null]);
        $pinjam->delete();

        return redirect()->route('peminjaman.index')
            ->with('status', 'Pengajuan dibatalkan.');
    }
}
