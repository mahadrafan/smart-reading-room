@extends('layouts.user')

@section('title', 'E-book')

@section('content')
    <div class="kepala">
        <h1>Koleksi E-book</h1>
        <p class="ket">Baca koleksi digital dan unduh PDF untuk belajar.</p>
    </div>

    <form class="cari" method="GET" action="{{ route('ebooks.index') }}">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul atau penulis">
        <button type="submit">Cari</button>
    </form>

    <div class="rak">
        @forelse ($ebooks as $ebook)
            <article class="kartu-buku">
                <div class="sampul" style="background:#0d1240"><span class="inisial">PDF</span><span class="judul-sampul">{{ $ebook->title }}</span></div>
                <div class="info-buku">
                    @if ($ebook->category)<span class="label-kat">{{ $ebook->category->category_name }}</span>@endif
                    <h3>{{ $ebook->title }}</h3>
                    <p class="penulis">{{ $ebook->author->author_name ?? 'Penulis belum diisi' }}</p>
                    <p class="sinopsis-ringkas">{{ $ebook->description ?: 'Belum ada deskripsi.' }}</p>
                    @if ($ebook->file_url)
                        <a class="tombol tombol-penuh" href="{{ route('ebooks.download', $ebook->ebook_id) }}">Unduh PDF</a>
                    @else
                        <span class="tombol tombol-penuh nonaktif">File belum tersedia</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="kosong">Belum ada e-book yang tersedia.</div>
        @endforelse
    </div>
@endsection
