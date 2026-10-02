<?php

namespace App\Http\Controllers;

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

    // ubah data diri (name, email, class, phone)
    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'class' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $user->update($data);

        return redirect()->route('profil')->with('status', 'Data diri berhasil diperbarui.');
    }

    // ganti password
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'password_lama' => ['required'],
            'password_baru' => ['required', 'string', 'min:8', 'confirmed'],
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
