<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // cookie berisi NIS/NIP terakhir yang login dengan "Ingat saya", agar form login terisi lagi setelah keluar.
    // password TIDAK disimpan aplikasi; pengisian password diserahkan ke password manager browser.
    const COOKIE_NIS = 'nis_diingat';
    const MENIT_NIS_DIINGAT = 60 * 24 * 400;

    // ---------------------------------------------------------
    // TAMPILAN
    // ---------------------------------------------------------

    // satu halaman login untuk admin dan peminjam
    public function formLogin(Request $request)
    {
        // "Ganti akun": lupakan NIS/NIP yang diingat (mis. di komputer bersama)
        if ($request->boolean('ganti')) {
            return redirect()->route('login')->withoutCookie(self::COOKIE_NIS);
        }

        return view('auth.login', ['nisDiingat' => $request->cookie(self::COOKIE_NIS)]);
    }

    // dua versi form daftar: siswa (bawaan) dan tenaga pendidik (?sebagai=pendidik)
    public function formRegister(Request $request)
    {
        $jenis = old('borrower_type')
            ?? ($request->query('sebagai') === 'pendidik' ? User::TENAGA_PENDIDIK : User::SISWA);

        return view('auth.register', ['jenis' => $jenis === User::TENAGA_PENDIDIK ? User::TENAGA_PENDIDIK : User::SISWA]);
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

            // ingat NIS/NIP untuk form login berikutnya hanya bila "Ingat saya" dicentang
            $cookieNis = $request->boolean('remember')
                ? Cookie::make(self::COOKIE_NIS, Auth::user()->nim_nip, self::MENIT_NIS_DIINGAT)
                : Cookie::forget(self::COOKIE_NIS);

            // arahkan sesuai role yang ada di database, atau ke halaman yang tadi dituju sebelum diminta login
            $tujuan = Auth::user()->role == 'Admin' ? 'admin.dashboard' : 'dashboard';

            return redirect()->intended(route($tujuan))->withCookie($cookieNis);
        }

        return back()
            ->withInput($request->only('nim_nip', 'remember'))
            ->with('error', 'NIS/NIP atau password salah. Periksa lagi, lalu coba masuk kembali.');
    }

    // ---------------------------------------------------------
    // PROSES DAFTAR -> CREATE (INSERT) ke tabel users
    // ---------------------------------------------------------

    public function register(Request $request)
    {
        $pendidik = $request->borrower_type === User::TENAGA_PENDIDIK;

        // no. telepon dirapikan dulu (0 di depan / kode negara yang ikut diketik dibuang); kode negara bawaan +62
        [$kodeTelepon, $nomorTelepon] = User::rapikanTelepon($request->input('phone_code', User::KODE_INDONESIA), $request->phone);
        $request->merge(['phone_code' => $kodeTelepon, 'phone' => $nomorTelepon]);

        // siswa wajib mengisi kelas, tenaga pendidik wajib mengisi jabatan/mapel
        $request->validate([
            'borrower_type' => 'required|in:' . User::SISWA . ',' . User::TENAGA_PENDIDIK,
            'name'          => 'required|max:100',
            'email'         => 'required|email|max:100|unique:users,email',
            'nim_nip'       => 'required|max:20|unique:users,nim_nip',
            'class'         => 'required_if:borrower_type,' . User::SISWA . '|nullable|max:20',
            'job_title'     => 'required_if:borrower_type,' . User::TENAGA_PENDIDIK . '|nullable|max:50',
            'password'      => 'required|min:8|confirmed',
        ] + User::aturanTelepon($kodeTelepon), User::pesanTelepon() + [
            'required'      => ':attribute wajib diisi.',
            'required_if'   => ':attribute wajib diisi.',
            'borrower_type.*' => 'Pilih daftar sebagai siswa atau tenaga pendidik.',
            'email'         => 'Format email belum benar.',
            'max'           => ':attribute maksimal :max karakter.',
            'unique'        => ':attribute sudah terdaftar.',
            'min'           => ':attribute minimal :min karakter.',
            'confirmed'     => 'Password dan ulangi password harus sama.',
        ], [
            'name'      => 'Nama lengkap',
            'email'     => 'Email',
            'nim_nip'   => $pendidik ? 'NIP' : 'NIS',
            'class'     => 'Kelas',
            'job_title' => 'Jabatan / mapel',
            'phone_code' => 'Kode negara',
            'phone'     => 'No. telepon',
            'password'  => 'Password',
        ]);

        // role selalu Peminjam, tidak diambil dari form; hanya isian milik jenis yang dipilih yang disimpan
        User::create([
            'role'          => 'Peminjam',
            'borrower_type' => $request->borrower_type,
            'nim_nip'       => $request->nim_nip,
            'name'          => $request->name,
            'class'         => $pendidik ? null : $request->class,
            'job_title'     => $pendidik ? $request->job_title : null,
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
