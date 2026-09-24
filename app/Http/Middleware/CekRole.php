<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CekRole
{
    // dipakai di route: CekRole::class . ':Admin'  atau  ':Peminjam'
    public function handle(Request $request, Closure $next, $role)
    {
        // belum login -> ke satu halaman login yang sama untuk semua
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // sudah login tapi rolenya salah -> jangan tolak mentah-mentah,
        // arahkan ke landing page yang memang sesuai rolenya
        if (Auth::user()->role != $role) {
            if (Auth::user()->role == 'Admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
