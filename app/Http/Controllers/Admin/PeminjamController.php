<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class PeminjamController extends Controller
{
    public function index()
    {
        $peminjam = User::where('role', 'Peminjam')
            ->withCount([
                'loans as pinjaman_aktif' => fn ($query) => $query->whereIn('status', ['Menunggu', 'Dikonfirmasi', 'Dipinjam']),
                'loans as pinjaman_terlambat' => fn ($query) => $query->where('status', 'Dipinjam')->whereDate('due_date', '<', today()),
            ])
            ->orderBy('name')
            ->get();

        return view('admin.peminjam.index', compact('peminjam'));
    }
}
