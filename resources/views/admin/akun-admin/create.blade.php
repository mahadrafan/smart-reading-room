@extends('layouts.admin')

@section('title', 'Tambah Admin')

@section('content')
    <a href="{{ route('admin.akun.index') }}" class="kembali">&larr; Kembali ke daftar admin</a>
    <div class="kepala">
        <h1>Tambah Admin</h1>
        <p class="ket">Akun baru langsung bisa masuk ke panel admin dengan NIP/ID dan password di bawah.</p>
    </div>

    <div class="kotak kotak-form">
        <form method="POST" action="{{ route('admin.akun.store') }}">
            @csrf
            @include('admin.akun-admin._data', ['akun' => null])

            <div class="isian">
                <label for="password">Password (minimal 8 karakter)</label>
                <input type="password" id="password" name="password" autocomplete="new-password"
                       class="@error('password') salah @enderror" required>
                @error('password')<p class="pesan-salah">{{ $message }}</p>@enderror
            </div>

            <div class="isian">
                <label for="password_confirmation">Ulangi password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
            </div>

            <div class="form-aksi">
                <button type="submit" class="tombol">Simpan</button>
                <a href="{{ route('admin.akun.index') }}" class="tombol tombol-luar">Batal</a>
            </div>
        </form>
    </div>
@endsection
