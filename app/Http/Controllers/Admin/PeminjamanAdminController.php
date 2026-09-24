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
    // ---------------------------------------------------------
    // READ: Request Masuk (pengajuan yang masih Menunggu)
    // ---------------------------------------------------------
    public function index()
    {
        Loan::prosesTenggatPengambilan();

        $daftar = Loan::where('status', 'Menunggu')
            ->with(['user', 'book'])
            ->orderBy('loan_id')
            ->get();

        return view('admin.peminjaman.index', ['daftar' => $daftar]);
    }

    // UPDATE: setujui / konfirmasi pengajuan
    public function setujui($id)
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

        return redirect()->route('admin.peminjaman.index')->with('status', 'Peminjaman berhasil dikonfirmasi. Menunggu pengguna mengambil buku.');
    }

    // UPDATE: tolak pengajuan (status menjadi Gagal)
    public function tolak($id)
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

        return redirect()->route('admin.peminjaman.index')->with('status', 'Peminjaman ditolak (status menjadi Gagal).');
    }

    // ---------------------------------------------------------
    // READ: Riwayat (Dikonfirmasi / Dipinjam / Gagal / Dikembalikan)
    // ---------------------------------------------------------
    public function riwayat(Request $request)
    {
        Loan::prosesTenggatPengambilan();

        $query = Loan::whereIn('status', ['Dikonfirmasi', 'Dipinjam', 'Gagal', 'Dikembalikan'])
            ->with(['user', 'book', 'approver']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $daftar = $query->orderBy('loan_id', 'desc')->get();

        return view('admin.peminjaman.riwayat', [
            'daftar' => $daftar,
            'status' => $request->status,
        ]);
    }

    // UPDATE: tandai buku telah diambil manual oleh user ke perpus (Dikonfirmasi -> Dipinjam)
    public function dipinjam($id)
    {
        Loan::prosesTenggatPengambilan();

        $pinjam = Loan::where('status', 'Dikonfirmasi')->findOrFail($id);

        if ($pinjam->is_expired_pengambilan) {
            return back()->with('error', 'Tenggat 2 hari pengambilan buku telah lewat.');
        }

        DB::transaction(function () use ($pinjam) {
            $pinjam->status = 'Dipinjam';
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

        return redirect()->route('admin.peminjaman.riwayat')->with('status', 'Status peminjaman diperbarui menjadi Dipinjam.');
    }

    // UPDATE: tandai buku sudah dikembalikan oleh user ke perpus (Dipinjam -> Dikembalikan)
    public function kembalikan($id)
    {
        Loan::prosesTenggatPengambilan();

        $pinjam = Loan::where('status', 'Dipinjam')->findOrFail($id);

        DB::transaction(function () use ($pinjam) {
            $pinjam->status      = 'Dikembalikan';
            $pinjam->return_date = now()->format('Y-m-d');
            $pinjam->save();

            $pinjam->book->increment('available_stock');

            AdminLog::catat('Kembalikan', 'loans', $pinjam->loan_id,
                'Menandai pengembalian "' . $pinjam->book->title . '" oleh ' . $pinjam->user->name);
        });

        app(LoanNotificationService::class)->send(
            $pinjam->fresh()->load(['user', 'book']),
            'Dikembalikan',
            'Buku sudah dikembalikan',
            'Pengembalian buku "' . $pinjam->book->title . '" sudah tercatat. Terima kasih.'
        );

        return redirect()->route('admin.peminjaman.riwayat')->with('status', 'Buku ditandai sudah dikembalikan.');
    }

    public function ubahStatusDenda($id)
    {
        $pinjam = Loan::with(['user', 'book'])->findOrFail($id);

        abort_if($pinjam->jumlah_denda < 1, 422, 'Peminjaman ini tidak memiliki denda.');

        $menjadiLunas = $pinjam->fine_paid_at === null;
        $pinjam->update([
            'fine_paid_at' => $menjadiLunas ? now() : null,
            'fine_paid_by' => $menjadiLunas ? Auth::id() : null,
        ]);

        AdminLog::catat(
            $menjadiLunas ? 'Denda Lunas' : 'Denda Belum Lunas',
            'loans',
            $pinjam->loan_id,
            'Mengubah status denda peminjaman "' . ($pinjam->book->title ?? '-') . '" oleh ' .
                ($pinjam->user->name ?? '-') . ' menjadi ' . ($menjadiLunas ? 'Lunas' : 'Belum Lunas') . '.'
        );

        return back()->with('status', 'Status denda berhasil diubah menjadi ' . ($menjadiLunas ? 'Lunas.' : 'Belum Lunas.'));
    }

    // DELETE: hapus riwayat (berstatus Dikembalikan atau Gagal)
    public function destroy($id)
    {
        Loan::prosesTenggatPengambilan();

        $pinjam = Loan::whereIn('status', ['Dikembalikan', 'Gagal'])->findOrFail($id);

        $judul = $pinjam->book->title ?? '-';
        $nama  = $pinjam->user->name ?? '-';

        AdminLog::catat('Hapus', 'loans', $pinjam->loan_id,
            'Menghapus riwayat peminjaman "' . $judul . '" oleh ' . $nama);

        UserNotification::where('loan_id', $pinjam->loan_id)->update(['loan_id' => null]);
        $pinjam->delete();

        return redirect()->route('admin.peminjaman.riwayat')->with('status', 'Riwayat berhasil dihapus.');
    }
}
