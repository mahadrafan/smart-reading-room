@extends('layouts.admin')

@section('title', 'Tambah Kategori')

@section('content')
    <a href="{{ route('admin.kategori.index') }}" class="kembali">&larr; Kembali ke daftar kategori</a>

    <div class="kepala">
        <h1>Tambah Kategori</h1>
    </div>

    <div class="kotak kotak-form">
        <form method="POST" action="{{ route('admin.kategori.store') }}">
            @csrf

            <div class="isian">
                <label for="category_name">Nama kategori</label>
                <input type="text" id="category_name" name="category_name"
                       value="{{ old('category_name') }}"
                       class="@error('category_name') salah @enderror" required autofocus>
                @error('category_name')
                    <p class="pesan-salah">{{ $message }}</p>
                @enderror
            </div>

            <div class="isian">
                <label for="description">Deskripsi (opsional)</label>
                <textarea id="description" name="description" rows="3"
                          class="@error('description') salah @enderror">{{ old('description') }}</textarea>
                @error('description')
                    <p class="pesan-salah">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-aksi">
                <button type="submit" class="tombol">Simpan</button>
                <a href="{{ route('admin.kategori.index') }}" class="tombol tombol-luar">Batal</a>
            </div>
        </form>
    </div>
@endsection
