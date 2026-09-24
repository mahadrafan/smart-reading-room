@extends('layouts.admin')

@section('title', 'Tambah Buku')

@section('content')
    <a href="{{ route('admin.buku.index') }}" class="kembali">&larr; Kembali ke daftar buku</a>
    <div class="kepala"><h1>Tambah Buku</h1></div>

    <div class="kotak kotak-form">
        <form method="POST" action="{{ route('admin.buku.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.buku._form', ['buku' => null])
            <div class="form-aksi">
                <button type="submit" class="tombol">Simpan</button>
                <a href="{{ route('admin.buku.index') }}" class="tombol tombol-luar">Batal</a>
            </div>
        </form>
    </div>
@endsection
