@extends('layouts.admin')

@section('title', 'Tambah E-book')

@section('content')
    <a class="kembali" href="{{ route('admin.ebooks.index') }}">&larr; Kembali ke e-book</a>
    <div class="kepala"><h1>Tambah E-book</h1><p class="ket">Unggah dokumen PDF maksimal 20 MB.</p></div>
    <div class="kotak kotak-form">
        <form method="POST" action="{{ route('admin.ebooks.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="isian"><label for="title">Judul</label><input id="title" name="title" value="{{ old('title') }}" required>@error('title')<p class="pesan-salah">{{ $message }}</p>@enderror</div>
            <div class="dua-kolom">
                <div class="isian"><label for="category_id">Kategori</label><select id="category_id" name="category_id"><option value="">Tanpa kategori</option>@foreach($kategori as $item)<option value="{{ $item->category_id }}">{{ $item->category_name }}</option>@endforeach</select></div>
                <div class="isian"><label for="author_id">Penulis</label><select id="author_id" name="author_id"><option value="">Tanpa penulis</option>@foreach($penulis as $item)<option value="{{ $item->author_id }}">{{ $item->author_name }}</option>@endforeach</select></div>
            </div>
            <div class="dua-kolom"><div class="isian"><label for="publisher">Penerbit</label><input id="publisher" name="publisher" value="{{ old('publisher') }}"></div><div class="isian"><label for="publication_year">Tahun</label><input id="publication_year" type="number" name="publication_year" value="{{ old('publication_year') }}"></div></div>
            <div class="isian"><label for="description">Deskripsi</label><textarea id="description" name="description" rows="4">{{ old('description') }}</textarea></div>
            <div class="isian"><label for="pdf_file">File PDF</label><input id="pdf_file" type="file" name="pdf_file" accept="application/pdf" required>@error('pdf_file')<p class="pesan-salah">{{ $message }}</p>@enderror</div>
            <button type="submit" class="tombol">Simpan E-book</button>
        </form>
    </div>
@endsection
