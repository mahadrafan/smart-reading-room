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

        return view('admin.dashboard', [
            'jumlahBuku'     => Book::aktif()->count(),
            'jumlahKategori' => Category::count(),
            'jumlahPenulis'  => Author::count(),
            'jumlahPeminjam' => User::where('role', 'Peminjam')->count(),
            'jumlahMenunggu' => Loan::where('status', 'Menunggu')->count(),
            'jumlahTerlambat' => Loan::where('status', 'Dipinjam')->whereDate('due_date', '<', today())->count(),
            'terlambat' => Loan::where('status', 'Dipinjam')
                ->whereDate('due_date', '<', today())
                ->with(['user', 'book'])
                ->orderBy('due_date')
                ->get(),
        ]);
    }
}
