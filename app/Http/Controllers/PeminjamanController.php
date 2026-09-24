<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\UserNotification;
use App\Services\LoanNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $query = Loan::where('user_id', Auth::id())
            ->with(['book.author', 'approver']);

        if ($filter == 'aktif') {
            $query->whereIn('status', ['Menunggu', 'Dikonfirmasi', 'Dipinjam']);
        } elseif ($filter == 'riwayat') {
            $query->whereIn('status', ['Gagal', 'Dikembalikan']);
        }

        $daftar = $query->orderBy('loan_id', 'desc')->get();

        return view('user.peminjaman-saya', [
            'daftar' => $daftar,
            'filter' => $filter,
        ]);
    }

    // ---------------------------------------------------------
    // form peminjaman
    // ---------------------------------------------------------
    public function create(Request $request)
    {
        Loan::prosesTenggatPengambilan();

        // hanya buku aktif yang stoknya masih ada
        $daftarBuku = Book::aktif()
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
            'loan_date'     => 'required|date|after_or_equal:today',
            'durasi'        => 'required|in:1,3,5,7,custom',
            'durasi_custom' => 'required_if:durasi,custom|nullable|integer|min:1|max:30',
        ], [
            'required'             => ':attribute wajib diisi.',
            'exists'               => ':attribute tidak ditemukan.',
            'date'                 => 'Format :attribute belum benar.',
            'after_or_equal'       => ':attribute tidak boleh sebelum hari ini.',
            'in'                   => ':attribute tidak valid.',
            'integer'              => ':attribute harus berupa angka.',
            'min'                  => ':attribute minimal :min hari.',
            'max'                  => ':attribute maksimal :max hari.',
            'durasi_custom.required_if' => 'Isi jumlah hari untuk durasi custom.',
        ], [
            'book_id'       => 'Nama buku',
            'loan_date'     => 'Tanggal peminjaman',
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

        // hitung batas pengembalian
        if ($request->durasi == 'custom') {
            $lama = (int) $request->durasi_custom;
        } else {
            $lama = (int) $request->durasi;
        }
        $tglPinjam = Carbon::parse($request->loan_date);
        $tglKembali = $tglPinjam->copy()->addDays($lama);

        // simpan ke tabel loans (request_date terisi otomatis oleh database)
        $loan = Loan::create([
            'user_id'   => $idUser,
            'book_id'   => $buku->book_id,
            'loan_date' => $tglPinjam->format('Y-m-d'),
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
