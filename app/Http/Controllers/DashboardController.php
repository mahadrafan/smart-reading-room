<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;

class DashboardController extends Controller
{
    // dashboard admin: ringkasan angka
    public function admin()
    {
        Loan::prosesTenggatPengambilan();

        // peminjaman terlambat yang dendanya belum lunas:
        // masih dipinjam & lewat tenggat, atau sudah dikembalikan terlambat tapi belum bayar
        $terlambat = Loan::whereNull('fine_paid_at')
            ->where(function ($q) {
                $q->where(function ($dipinjam) {
                    $dipinjam->where('status', 'Dipinjam')->whereDate('due_date', '<', today());
                })->orWhere(function ($kembali) {
                    $kembali->where('status', 'Dikembalikan')->whereColumn('return_date', '>', 'due_date');
                });
            })
            ->with(['user', 'book'])
            ->orderBy('due_date')
            ->get();

        return view('admin.dashboard', [
            'jumlahBuku'     => Book::aktif()->count(),
            'jumlahKategori' => Category::count(),
            'jumlahPenulis'  => Author::count(),
            'jumlahPeminjam' => User::where('role', 'Peminjam')->count(),
            'jumlahMenunggu' => Loan::where('status', 'Menunggu')->count(),
            'jumlahTerlambat' => $terlambat->count(),
            'terlambat' => $terlambat,
        ]);
    }
}
