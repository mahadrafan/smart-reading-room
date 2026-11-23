<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfilController extends Controller
{
    // tampilkan halaman profil (data diri + form ganti password)
    public function show()
    {
        return view('user.profil');
    }

    // ubah data diri (name, email, phone, dan kelas)
    public function update(Request $request)
    {
        $user = Auth::user();

        // no. telepon + kode negara dirapikan seperti saat daftar
        [$kodeTelepon, $nomorTelepon] = User::rapikanTelepon($request->input('phone_code', User::KODE_INDONESIA), $request->phone);
        $request->merge(['phone_code' => $kodeTelepon, 'phone' => $nomorTelepon]);

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'class' => ['required', 'string', 'max:20'],
        ] + User::aturanTelepon($kodeTelepon), User::pesanTelepon() + [
            'required' => ':attribute wajib diisi.',
            'email'    => 'Format email belum benar.',
            'max'      => ':attribute maksimal :max karakter.',
            'unique'   => ':attribute sudah dipakai akun lain.',
        ], [
            'name'      => 'Nama lengkap',
            'email'     => 'Email',
            'class'     => 'Kelas',
            'phone'     => 'No. telepon',
        ]);

        unset($data['phone_code']);
        $data['phone'] = User::gabungTelepon($kodeTelepon, $nomorTelepon);
        $user->update($data);

        return redirect()->route('profil')->with('status', 'Data diri berhasil diperbarui.');
    }

    // ganti password
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        // pesan salah disimpan di kantong "password" agar tampil di form Ganti password, bukan di form Data diri
        $request->validateWithBag('password', [
            'password_lama' => ['required'],
            'password_baru' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password_lama.required' => 'Password lama wajib diisi.',
            'password_baru.required' => 'Password baru wajib diisi.',
            'password_baru.min'      => 'Password baru minimal 8 karakter.',
            'password_baru.confirmed' => 'Password baru dan ulangi password baru harus sama.',
        ]);

        if (! Hash::check($request->password_lama, $user->password)) {
            return back()->withErrors([
                'password_lama' => 'Password lama tidak sesuai.',
            ], 'password');
        }

        $user->update([
            'password' => Hash::make($request->password_baru),
        ]);

        return redirect()->route('profil')->with('status', 'Password berhasil diganti.');
    }
}
