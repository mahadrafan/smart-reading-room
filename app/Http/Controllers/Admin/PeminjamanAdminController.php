<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Loan;
use App\Models\UserNotification;
use App\Models\Book;
use App\Services\LoanNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PeminjamanAdminController extends Controller
{
    const PER_HALAMAN = 10;

    // status yang tampil di halaman Riwayat (urutan = urutan tombol filter)
    const STATUS_RIWAYAT = ['Dikonfirmasi', 'Dipinjam', 'Gagal', 'Dikembalikan'];

    // ---------------------------------------------------------
    // READ: Request Masuk (pengajuan yang masih Menunggu) + cari peminjam & filter buku, 10 per halaman
    // ---------------------------------------------------------
    public function index(Request $request)
    {
        Loan::prosesTenggatPengambilan();

        $cari = trim((string) $request->q);

        // buku yang sedang punya pengajuan menunggu, untuk pilihan filter
        $daftarBuku = Book::whereHas('loans', fn ($q) => $q->where('status', 'Menunggu'))
            ->withCount(['loans as jumlah_menunggu' => fn ($q) => $q->where('status', 'Menunggu')])
            ->orderBy('title')
            ->get(['book_id', 'title']);
        $bukuDipilih = $request->filled('buku') ? $daftarBuku->firstWhere('book_id', (int) $request->buku) : null;

        $daftar = Loan::where('status', 'Menunggu')
            ->when($bukuDipilih, fn ($q) => $q->where('book_id', $bukuDipilih->book_id))
            ->when($cari !== '', fn ($q) => $q->whereHas('user', function ($u) use ($cari) {
                $u->where('name', 'like', '%' . $cari . '%')->orWhere('nim_nip', 'like', '%' . $cari . '%');
            }))
            ->with(['user', 'book'])
            ->orderBy('loan_id')
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();

        // halaman di luar jangkauan (mis. setelah memproses pengajuan terakhir di halaman terakhir)
        if ($daftar->isEmpty() && $daftar->currentPage() > 1) {
            return redirect()->to($daftar->url($daftar->lastPage()));
        }

        return view('admin.peminjaman.index', [
            'daftar'       => $daftar,
            'cari'         => $cari,
            'daftarBuku'   => $daftarBuku,
            'bukuDipilih'  => $bukuDipilih,
            'jumlahTotal'  => Loan::where('status', 'Menunggu')->count(),
            'filter'       => array_filter(['q' => $cari, 'buku' => $bukuDipilih?->book_id]),
        ]);
    }

    // kembali ke Request Masuk / Riwayat dengan pencarian, filter, dan halaman yang sama
    private function keRequestMasuk(Request $request)
    {
        return redirect()->route('admin.peminjaman.index', $request->only(['q', 'buku', 'page']));
    }

    private function keRiwayat(Request $request)
    {
        return redirect()->route('admin.peminjaman.riwayat', $request->only(['status', 'page']));
    }

    // UPDATE: setujui / konfirmasi pengajuan
    public function setujui(Request $request, $id)
    {
        Loan::prosesTenggatPengambilan();

        $pinjam = DB::transaction(function () use ($id) {
            $pinjam = Loan::where('status', 'Menunggu')->lockForUpdate()->findOrFail($id);
            $buku = Book::whereKey($pinjam->book_id)->lockForUpdate()->firstOrFail();
            if ($buku->available_stock < 1) {
                return null;
            }

            $pinjam->status      = 'Dikonfirmasi';
            $pinjam->approved_by = Auth::id();
            $pinjam->approved_at = now();
            $pinjam->save();

            $buku->decrement('available_stock');

            AdminLog::catat('ACC', 'loans', $pinjam->loan_id,
                'Mengonfirmasi peminjaman "' . $buku->title . '" oleh ' . $pinjam->user->name);

            return $pinjam->load(['user', 'book']);
        });

        if (! $pinjam) {
            return back()->with('error', 'Stok buku sedang habis, peminjaman tidak bisa dikonfirmasi.');
        }

        app(LoanNotificationService::class)->send(
            $pinjam,
            'Dikonfirmasi',
            'Peminjaman sudah dikonfirmasi',
            'Peminjaman buku "' . $pinjam->book->title . '" sudah dikonfirmasi. Ambil buku paling lambat ' . $pinjam->tenggat_pengambilan->format('d/m/Y H:i') . '.'
        );

        return $this->keRequestMasuk($request)->with('status', 'Peminjaman berhasil dikonfirmasi. Menunggu pengguna mengambil buku.');
    }

    // UPDATE: tolak pengajuan (status menjadi Gagal)
    public function tolak(Request $request, $id)
    {
        Loan::prosesTenggatPengambilan();

        $pinjam = Loan::where('status', 'Menunggu')->findOrFail($id);

        $pinjam->status      = 'Gagal';
        $pinjam->approved_by = Auth::id();
        $pinjam->approved_at = now();
        $pinjam->save();

        app(LoanNotificationService::class)->send(
            $pinjam->load(['user', 'book']),
            'Gagal',
            'Pengajuan peminjaman ditolak',
            'Pengajuan peminjaman buku "' . $pinjam->book->title . '" tidak dapat disetujui.'
        );

        AdminLog::catat('Tolak', 'loans', $pinjam->loan_id,
            'Menolak peminjaman "' . $pinjam->book->title . '" oleh ' . $pinjam->user->name . ' (status Gagal)');

        return $this->keRequestMasuk($request)->with('status', 'Peminjaman ditolak (status menjadi Gagal).');
    }

    // ---------------------------------------------------------
    // READ: Riwayat (Dikonfirmasi / Dipinjam / Gagal / Dikembalikan), 10 per halaman
    // ---------------------------------------------------------
    public function riwayat(Request $request)
    {
        Loan::prosesTenggatPengambilan();

        // status di luar daftar diabaikan (dianggap "Semua")
        $status = in_array($request->status, self::STATUS_RIWAYAT, true) ? $request->status : null;

        $daftar = Loan::whereIn('status', self::STATUS_RIWAYAT)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['user', 'book', 'approver'])
            ->orderBy('loan_id', 'desc')
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();

        if ($daftar->isEmpty() && $daftar->currentPage() > 1) {
            return redirect()->to($daftar->url($daftar->lastPage()));
        }

        return view('admin.peminjaman.riwayat', [
            'daftar' => $daftar,
            'status' => $status,
        ]);
    }

    // UPDATE: tandai buku telah diambil manual oleh user ke perpus (Dikonfirmasi -> Dipinjam)
    public function dipinjam(Request $request, $id)
    {
        Loan::prosesTenggatPengambilan();

        $pinjam = Loan::where('status', 'Dikonfirmasi')->findOrFail($id);

        if ($pinjam->is_expired_pengambilan) {
            return back()->with('error', 'Tenggat 2 hari pengambilan buku telah lewat.');
        }

        DB::transaction(function () use ($pinjam) {
            // tanggal pinjam = hari buku diambil; batas kembali digeser agar durasi pilihan user tetap utuh
            $durasi = $pinjam->durasi_pinjam;
            $pinjam->status    = 'Dipinjam';
            $pinjam->loan_date = today();
            $pinjam->due_date  = today()->addDays($durasi);
            $pinjam->save();

            AdminLog::catat('Dipinjam', 'loans', $pinjam->loan_id,
                'Menandai buku "' . $pinjam->book->title . '" telah diambil manual oleh ' . $pinjam->user->name);
        });

        app(LoanNotificationService::class)->send(
            $pinjam->fresh()->load(['user', 'book']),
            'Dipinjam',
            'Buku sudah dipinjam',
            'Buku "' . $pinjam->book->title . '" tercatat sudah diambil. Batas pengembalian: ' . $pinjam->due_date->format('d/m/Y') . '.'
        );

        return $this->keRiwayat($request)->with('status', 'Status peminjaman diperbarui menjadi Dipinjam.');
    }

    // UPDATE: tandai buku sudah dikembalikan oleh user ke perpus (Dipinjam -> Dikembalikan)
    // hanya untuk buku yang tidak terlambat; buku terlambat dikembalikan sekaligus melunasi denda (ubahStatusDenda)
    public function kembalikan(Request $request, $id)
    {
        Loan::prosesTenggatPengambilan();

        $pinjam = Loan::where('status', 'Dipinjam')->findOrFail($id);

        if ($pinjam->jumlah_denda > 0) {
            return back()->with('error', 'Buku ini terlambat ' . $pinjam->hari_terlambat . ' hari dengan denda Rp'
                . number_format($pinjam->jumlah_denda, 0, ',', '.') . '. Pengembalian harus bersamaan dengan pelunasan denda, '
                . 'gunakan tombol "Kembalikan & Lunasi Denda".');
        }

        $selesai = DB::transaction(function () use ($pinjam) {
            // kunci baris & cek ulang status, agar dua klik bersamaan tidak menambah stok dua kali
            $terkunci = Loan::whereKey($pinjam->loan_id)->lockForUpdate()->first();
            if (! $terkunci || $terkunci->status !== 'Dipinjam') {
                return false;
            }

            $pinjam->status      = 'Dikembalikan';
            $pinjam->return_date = now()->format('Y-m-d');
            $pinjam->save();

            $pinjam->book->increment('available_stock');

            AdminLog::catat('Kembalikan', 'loans', $pinjam->loan_id,
                'Menandai pengembalian "' . $pinjam->book->title . '" oleh ' . $pinjam->user->name);

            return true;
        });

        if (! $selesai) {
            return $this->keRiwayat($request)->with('error', 'Peminjaman ini sudah diproses sebelumnya.');
        }

        app(LoanNotificationService::class)->send(
            $pinjam->fresh()->load(['user', 'book']),
            'Dikembalikan',
            'Buku sudah dikembalikan',
            'Pengembalian buku "' . $pinjam->book->title . '" sudah tercatat. Terima kasih.'
        );

        return $this->keRiwayat($request)->with('status', 'Buku ditandai sudah dikembalikan.');
    }

    // UPDATE: kembalikan buku terlambat sekaligus melunasi dendanya (pembayaran & pengembalian selalu bersamaan).
    // Lunas bersifat final. Riwayat lama yang sudah Dikembalikan tapi belum lunas juga bisa dilunasi di sini.
    public function ubahStatusDenda($id)
    {
        $pinjam = Loan::with(['user', 'book'])->findOrFail($id);

        abort_if($pinjam->jumlah_denda < 1, 422, 'Peminjaman ini tidak memiliki denda.');

        $sekalianKembali = DB::transaction(function () use ($pinjam) {
            // kunci baris agar dua klik bersamaan tidak memproses (dan menambah stok) dua kali
            $terkunci = Loan::whereKey($pinjam->loan_id)->lockForUpdate()->first();
            if ($terkunci->fine_paid_at !== null) {
                return null;
            }

            $sekalianKembali = $terkunci->status === 'Dipinjam';

            $pinjam->fine_paid_at = now();
            $pinjam->fine_paid_by = Auth::id();

            if ($sekalianKembali) {
                $pinjam->status      = 'Dikembalikan';
                $pinjam->return_date = now()->format('Y-m-d');
                $pinjam->book->increment('available_stock');
            }

            $pinjam->save();

            AdminLog::catat('Denda Lunas', 'loans', $pinjam->loan_id,
                'Mengubah status denda peminjaman "' . ($pinjam->book->title ?? '-') . '" oleh ' .
                    ($pinjam->user->name ?? '-') . ' menjadi Lunas' .
                    ($sekalianKembali ? ' dan menandai buku sudah dikembalikan.' : '.'));

            return $sekalianKembali;
        });

        if ($sekalianKembali === null) {
            return back()->with('error', 'Denda ini sudah lunas dan statusnya tidak bisa diubah lagi.');
        }

        if ($sekalianKembali) {
            app(LoanNotificationService::class)->send(
                $pinjam->fresh()->load(['user', 'book']),
                'Dikembalikan',
                'Buku sudah dikembalikan',
                'Denda Rp' . number_format($pinjam->jumlah_denda, 0, ',', '.') . ' untuk buku "' . $pinjam->book->title .
                    '" sudah lunas dan pengembalian buku sudah tercatat. Terima kasih.'
            );

            return back()->with('status', 'Buku dikembalikan dan denda Rp' . number_format($pinjam->jumlah_denda, 0, ',', '.') . ' lunas.');
        }

        return back()->with('status', 'Status denda berhasil diubah menjadi Lunas.');
    }

    // DELETE: hapus riwayat (berstatus Dikembalikan atau Gagal)
    public function destroy(Request $request, $id)
    {
        Loan::prosesTenggatPengambilan();

        $pinjam = Loan::whereIn('status', ['Dikembalikan', 'Gagal'])->findOrFail($id);

        // riwayat yang dendanya belum lunas tidak boleh hilang
        if ($pinjam->jumlah_denda > 0 && ! $pinjam->denda_lunas) {
            return back()->with('error', 'Riwayat ini tidak bisa dihapus karena dendanya (Rp'
                . number_format($pinjam->jumlah_denda, 0, ',', '.') . ') belum lunas.');
        }

        $judul = $pinjam->book->title ?? '-';
        $nama  = $pinjam->user->name ?? '-';

        AdminLog::catat('Hapus', 'loans', $pinjam->loan_id,
            'Menghapus riwayat peminjaman "' . $judul . '" oleh ' . $nama);

        UserNotification::where('loan_id', $pinjam->loan_id)->update(['loan_id' => null]);
        $pinjam->delete();

        return $this->keRiwayat($request)->with('status', 'Riwayat berhasil dihapus.');
    }
}
