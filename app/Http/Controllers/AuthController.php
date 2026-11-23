<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // cookie berisi NIS / ID admin terakhir yang login dengan "Ingat saya", agar form login terisi lagi setelah keluar.
    // password TIDAK disimpan aplikasi
    const COOKIE_NIS = 'nis_diingat';
    const MENIT_NIS_DIINGAT = 60 * 24 * 400;

    // TAMPILAN

    // satu halaman login untuk admin dan peminjam
    public function formLogin(Request $request)
    {
        // "Ganti akun": lupakan NIS / ID admin yang diingat (mis. di komputer bersama)
        if ($request->boolean('ganti')) {
            return redirect()->route('login')->withoutCookie(self::COOKIE_NIS);
        }

        return view('auth.login', ['nisDiingat' => $request->cookie(self::COOKIE_NIS)]);
    }

    // form daftar: peminjam hanya siswa
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
            'nim_nip'  => 'NIS',
            'email'    => 'Email',
            'password' => 'Password baru',
        ]);

        $user = User::where('nim_nip', $request->nim_nip)
                    ->where('email', $request->email)
                    ->first();

        if (!$user) {
            return back()
                ->withInput($request->only('nim_nip', 'email'))
                ->with('error', 'Kombinasi NIS dan email tidak cocok dengan data terdaftar.');
        }

        // akun admin hanya boleh diatur ulang manual di database, tidak lewat aplikasi
        if ($user->role === 'Admin') {
            return back()
                ->withInput($request->only('nim_nip', 'email'))
                ->with('error', 'Password akun admin tidak bisa diatur ulang dari halaman ini. Hubungi pengelola database.');
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
            'nim_nip'  => 'NIS / ID admin',
            'password' => 'Password',
        ]);

        // cek nim_nip dan password saja, role TIDAK ikut dicek di sini
        $data = [
            'nim_nip'  => $request->nim_nip,
            'password' => $request->password,
        ];

        if (Auth::attempt($data, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // ingat NIS / ID admin untuk form login berikutnya hanya bila "Ingat saya" dicentang
            $cookieNis = $request->boolean('remember')
                ? Cookie::make(self::COOKIE_NIS, Auth::user()->nim_nip, self::MENIT_NIS_DIINGAT)
                : Cookie::forget(self::COOKIE_NIS);

            // arahkan sesuai role yang ada di database, atau ke halaman yang tadi dituju sebelum diminta login
            $tujuan = Auth::user()->role == 'Admin' ? 'admin.dashboard' : 'dashboard';

            return redirect()->intended(route($tujuan))->withCookie($cookieNis);
        }

        return back()
            ->withInput($request->only('nim_nip', 'remember'))
            ->with('error', 'NIS / ID admin atau password salah. Periksa lagi, lalu coba masuk kembali.');
    }

    // ---------------------------------------------------------
    // PROSES DAFTAR -> CREATE (INSERT) ke tabel users
    // ---------------------------------------------------------

    public function register(Request $request)
    {
        // no. telepon dirapikan dulu (0 di depan / kode negara yang ikut diketik dibuang); kode negara bawaan +62
        [$kodeTelepon, $nomorTelepon] = User::rapikanTelepon($request->input('phone_code', User::KODE_INDONESIA), $request->phone);
        $request->merge(['phone_code' => $kodeTelepon, 'phone' => $nomorTelepon]);

        // peminjam hanya siswa: wajib mengisi NIS dan kelas
        $request->validate([
            'name'          => 'required|max:100',
            'email'         => 'required|email|max:100|unique:users,email',
            'nim_nip'       => 'required|max:20|unique:users,nim_nip',
            'class'         => 'required|max:20',
            'password'      => 'required|min:8|confirmed',
        ] + User::aturanTelepon($kodeTelepon), User::pesanTelepon() + [
            'required'      => ':attribute wajib diisi.',
            'email'         => 'Format email belum benar.',
            'max'           => ':attribute maksimal :max karakter.',
            'unique'        => ':attribute sudah terdaftar.',
            'min'           => ':attribute minimal :min karakter.',
            'confirmed'     => 'Password dan ulangi password harus sama.',
        ], [
            'name'      => 'Nama lengkap',
            'email'     => 'Email',
            'nim_nip'   => 'NIS',
            'class'     => 'Kelas',
            'phone_code' => 'Kode negara',
            'phone'     => 'No. telepon',
            'password'  => 'Password',
        ]);

        // role dan jenis peminjam tidak diambil dari form: selalu Peminjam dan Siswa
        User::create([
            'role'          => 'Peminjam',
            'borrower_type' => User::SISWA,
            'nim_nip'       => $request->nim_nip,
            'name'          => $request->name,
            'class'         => $request->class,
            'email'         => $request->email,
            'phone'         => User::gabungTelepon($kodeTelepon, $nomorTelepon),
            'password'      => Hash::make($request->password),
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
