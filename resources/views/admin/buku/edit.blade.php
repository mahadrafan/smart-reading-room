@extends('layouts.admin')

@section('title', 'Edit Buku')

@section('content')
    <a href="{{ route('admin.buku.index') }}" class="kembali">&larr; Kembali ke daftar buku</a>
    <div class="kepala"><h1>Edit Buku</h1></div>

    <div class="kotak kotak-form">
        <form method="POST" action="{{ route('admin.buku.update', $buku->book_id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.buku._form', ['buku' => $buku])
            <div class="form-aksi">
                <button type="submit" class="tombol">Simpan Perubahan</button>
                <a href="{{ route('admin.buku.index') }}" class="tombol tombol-luar">Batal</a>
            </div>
        </form>
    </div>
@endsection
