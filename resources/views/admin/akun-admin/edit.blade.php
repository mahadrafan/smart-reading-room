@extends('layouts.admin')

@php($akunSendiri = $akun->user_id == Auth::id())

@section('title', $akunSendiri ? 'Akun Saya' : 'Edit Admin')

@section('content')
    <a href="{{ route('admin.akun.index') }}" class="kembali">&larr; Kembali ke daftar admin</a>
    <div class="kepala">
        <h1>{{ $akunSendiri ? 'Akun Saya' : 'Edit Admin' }}</h1>
        <p class="ket">Terdaftar sejak {{ $akun->created_at ?? '-' }}</p>
    </div>

    <div class="dua-kotak">
        <div class="kotak">
            <h2 class="judul-kotak">Data akun</h2>
            <form method="POST" action="{{ route('admin.akun.update', $akun->user_id) }}">
                @csrf
                @method('PUT')
                @include('admin.akun-admin._data', ['akun' => $akun])
                <div class="form-aksi">
                    <button type="submit" class="tombol">Simpan Perubahan</button>
                </div>
            </form>
        </div>

        <div class="kotak">
            <h2 class="judul-kotak">{{ $akunSendiri ? 'Ganti password' : 'Reset password' }}</h2>
            @unless ($akunSendiri)
                <p class="pesan-info" style="margin:0 0 16px;">
                    Gunakan bila admin ini lupa password atau sudah tidak bertugas. Sampaikan password baru kepada yang bersangkutan.
                </p>
            @endunless
            <form method="POST" action="{{ route('admin.akun.password', $akun->user_id) }}">
                @csrf
                @method('PUT')

                @if ($akunSendiri)
                    <div class="isian">
                        <label for="password_lama">Password lama</label>
                        <input type="password" id="password_lama" name="password_lama" autocomplete="current-password"
                               class="@error('password_lama', 'password') salah @enderror" required>
                        @error('password_lama', 'password')<p class="pesan-salah">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div class="isian">
                    <label for="password_baru">Password baru (minimal 8 karakter)</label>
                    <input type="password" id="password_baru" name="password_baru" autocomplete="new-password"
                           class="@error('password_baru', 'password') salah @enderror" required>
                    @error('password_baru', 'password')<p class="pesan-salah">{{ $message }}</p>@enderror
                </div>

                <div class="isian">
                    <label for="password_baru_confirmation">Ulangi password baru</label>
                    <input type="password" id="password_baru_confirmation" name="password_baru_confirmation" autocomplete="new-password" required>
                </div>

                <div class="form-aksi">
                    <button type="submit" class="tombol">{{ $akunSendiri ? 'Ganti Password' : 'Reset Password' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
