@extends('layouts.admin')

@section('title', 'Edit Kategori')

@section('content')
    <a href="{{ route('admin.kategori.index') }}" class="kembali">&larr; Kembali ke daftar kategori</a>

    <div class="kepala">
        <h1>Edit Kategori</h1>
    </div>

    <div class="kotak kotak-form">
        <form method="POST" action="{{ route('admin.kategori.update', $kategori->category_id) }}">
            @csrf
            @method('PUT')

            <div class="isian">
                <label for="category_name">Nama kategori</label>
                <input type="text" id="category_name" name="category_name"
                       value="{{ old('category_name', $kategori->category_name) }}"
                       class="@error('category_name') salah @enderror" required autofocus>
                @error('category_name')
                    <p class="pesan-salah">{{ $message }}</p>
                @enderror
            </div>

            <div class="isian">
                <label for="description">Deskripsi (opsional)</label>
                <textarea id="description" name="description" rows="3"
                          class="@error('description') salah @enderror">{{ old('description', $kategori->description) }}</textarea>
                @error('description')
                    <p class="pesan-salah">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-aksi">
                <button type="submit" class="tombol">Simpan Perubahan</button>
                <a href="{{ route('admin.kategori.index') }}" class="tombol tombol-luar">Batal</a>
            </div>
        </form>
    </div>
@endsection
