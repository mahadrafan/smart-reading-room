<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ---------------------------------------------------------
    // TAMPILAN
    // ---------------------------------------------------------

    // satu halaman logihgfghfadmin dan peminjam
    public function formLogin()
    {
        return view('auth.login');
    }

    public function formRegister()
    {
        return view('auth.register');
    }

    public function formLupaPassword()
    {
        return view('auth.forgot-password');
    }

    public function prosesLupaPassword(Request $request)
    {
        $request->validate([
            'nim_nip'  => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], [
            'required'  => ':attribute wajib diisi.',
            'email'     => 'Format email belum benar.',
            'min'       => ':attribute minimal :min karakter.',
            'confirmed' => 'Password baru dan ulangi password harus sama.',
        ], [
            'nim_nip'  => 'NIS/NIP',
            'email'    => 'Email',
            'password' => 'Password baru',
        ]);

        $user = User::where('nim_nip', $request->nim_nip)
                    ->where('email', $request->email)
                    ->first();

        if (!$user) {
            return back()
                ->withInput($request->only('nim_nip', 'email'))
                ->with('error', 'Kombinasi NIS/NIP dan Email tidak cocok dengan data terdaftar.');
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('login')
            ->with('status', 'Password berhasil diganti. Silakan masuk dengan password baru.');
    }

    // ---------------------------------------------------------
    // PROSES LOGIN -> READ (SELECT) ke tabel users
    // Role TIDAK dipilih di form. Setelah nim_nip dan password
    // cocok, sistem membaca role dari database lalu mengarahkan
    // ke landing page yang sesuai.
    // ---------------------------------------------------------

    public function login(Request $request)
    {
        $request->validate([
            'nim_nip'  => 'required',
            'password' => 'required',
        ], [
            'required' => ':attribute wajib diisi.',
        ], [
            'nim_nip'  => 'NIS/NIP',
            'password' => 'Password',
        ]);

        // cek nim_nip dan password saja, role TIDAK ikut dicek di sini
        $data = [
            'nim_nip'  => $request->nim_nip,
            'password' => $request->password,
        ];

        if (Auth::attempt($data, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // arahkan sesuai role yang ada di database
            if (Auth::user()->role == 'Admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('dashboard');
        }

        return back()
            ->withInput($request->only('nim_nip'))
            ->with('error', 'NIS/NIP atau password salah. Periksa lagi, lalu coba masuk kembali.');
    }

    // ---------------------------------------------------------
    // PROSES DAFTAR -> CREATE (INSERT) ke tabel users
    // ---------------------------------------------------------

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|max:100',
            'email'    => 'required|email|max:100|unique:users,email',
            'nim_nip'  => 'required|max:20|unique:users,nim_nip',
            'class'    => 'required|max:20',
            'phone'    => 'required|max:20',
            'password' => 'required|min:8|confirmed',
        ], [
            'required'  => ':attribute wajib diisi.',
            'email'     => 'Format email belum benar.',
            'max'       => ':attribute maksimal :max karakter.',
            'unique'    => ':attribute sudah terdaftar.',
            'min'       => ':attribute minimal :min karakter.',
            'confirmed' => 'Password dan ulangi password harus sama.',
        ], [
            'name'     => 'Nama lengkap',
            'email'    => 'Email',
            'nim_nip'  => 'NIS/NIP',
            'class'    => 'Kelas',
            'phone'    => 'No. telepon',
            'password' => 'Password',
        ]);

        // role selalu Peminjam, tidak diambil dari form
        User::create([
            'role'     => 'Peminjam',
            'nim_nip'  => $request->nim_nip,
            'name'     => $request->name,
            'class'    => $request->class,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('login')
            ->with('status', 'Akun berhasil dibuat. Silakan masuk.');
    }

    // ---------------------------------------------------------
    // LOGOUT -> selalu kembali ke satu halaman login yang sama
    // ---------------------------------------------------------

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
