@extends('layouts.admin')

@section('title', 'Tambah Penulis')

@section('content')
    <a href="{{ route('admin.penulis.index') }}" class="kembali">&larr; Kembali ke daftar penulis</a>

    <div class="kepala">
        <h1>Tambah Penulis</h1>
    </div>

    <div class="kotak kotak-form">
        <form method="POST" action="{{ route('admin.penulis.store') }}">
            @csrf

            <div class="isian">
                <label for="author_name">Nama penulis</label>
                <input type="text" id="author_name" name="author_name"
                       value="{{ old('author_name') }}"
                       class="@error('author_name') salah @enderror" required autofocus>
                @error('author_name')
                    <p class="pesan-salah">{{ $message }}</p>
                @enderror
            </div>

            <div class="isian">
                <label for="biography">Biografi (opsional)</label>
                <textarea id="biography" name="biography" rows="4"
                          class="@error('biography') salah @enderror">{{ old('biography') }}</textarea>
                @error('biography')
                    <p class="pesan-salah">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-aksi">
                <button type="submit" class="tombol">Simpan</button>
                <a href="{{ route('admin.penulis.index') }}" class="tombol tombol-luar">Batal</a>
            </div>
        </form>
    </div>
@endsection
