@extends('layouts.user')

@section('title', $buku->title)

@section('content')
    <a href="{{ route('dashboard') }}" class="kembali">&larr; Kembali ke katalog</a>

    <div class="detail-buku">
        <div class="detail-sampul">
            @include('user._sampul', ['b' => $buku])
        </div>

        <div class="detail-info">
            @if ($buku->category)
                <span class="label-kat">{{ $buku->category->category_name }}</span>
            @endif
            <h1>{{ $buku->title }}</h1>
            <p class="penulis">{{ $buku->author ? $buku->author->author_name : 'Penulis belum diisi' }}</p>

            <table class="tabel-info">
                <tr><th>ID buku</th><td>{{ $buku->book_id }}</td></tr>
                <tr><th>Penerbit</th><td>{{ $buku->publisher ?: '-' }}</td></tr>
                <tr><th>Tahun terbit</th><td>{{ $buku->publication_year ?: '-' }}</td></tr>
                <tr><th>Lokasi rak</th><td>{{ $buku->location ?: '-' }}</td></tr>
                <tr><th>Stok tersedia</th><td>{{ $buku->available_stock }} dari {{ $buku->stock }}</td></tr>
                <tr>
                    <th>Status</th>
                    <td>
                        @if ($buku->available_stock > 0)
                            <span class="stok ada">Tersedia</span>
                        @else
                            <span class="stok habis">Sedang habis</span>
                        @endif
                    </td>
                </tr>
                <tr><th>Sudah dipinjam</th><td>{{ $buku->jumlah_dipinjam }} kali</td></tr>
            </table>

            <h2 class="judul-kecil">Sinopsis</h2>
            <p class="sinopsis">{{ $buku->description ?: 'Belum ada deskripsi untuk buku ini.' }}</p>

            <h2 class="judul-kecil" style="margin-top: 24px;">Informasi Peminjam Saat Ini</h2>
            @if ($peminjamAktif->count() > 0)
                <div class="kotak" style="padding: 12px 16px; margin-bottom: 20px;">
                    <table class="tabel" style="margin: 0;">
                        <thead>
                            <tr>
                                <th>Peminjam</th>
                                <th>Kelas</th>
                                <th>Tenggat Peminjaman</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($peminjamAktif as $pa)
                                <tr>
                                    <td>
                                        <strong>{{ $pa->user ? $pa->user->name : '-' }}</strong>
                                    </td>
                                    <td>
                                        {{ ($pa->user && $pa->user->class) ? $pa->user->class : '-' }}
                                    </td>
                                    <td>
                                        @if ($pa->status == 'Dipinjam')
                                            <span>Batas kembali: <strong>{{ $pa->due_date ? $pa->due_date->format('d/m/Y') : '-' }}</strong></span>
                                        @elseif ($pa->status == 'Dikonfirmasi')
                                            <span style="color: #1d4ed8;">Tenggat ambil: <strong>{{ $pa->tenggat_pengambilan ? $pa->tenggat_pengambilan->format('d/m/Y H:i') : '-' }}</strong></span>
                                        @else
                                            <span>-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="pesan-info" style="margin-bottom: 20px;">Saat ini tidak ada pengguna yang sedang meminjam buku ini.</p>
            @endif

            <div class="tombol-baris">
                @if ($sudahAjukan)
                    <p class="catatan">Kamu sudah punya pengajuan atau peminjaman aktif untuk buku ini.
                        <a href="{{ route('peminjaman.index', ['filter' => 'aktif']) }}">Lihat peminjamanku</a></p>
                @elseif ($buku->available_stock > 0)
                    <a href="{{ route('peminjaman.buat', ['buku' => $buku->book_id]) }}" class="tombol">Pinjam Buku</a>
                @else
                    <span class="tombol nonaktif">Stok habis</span>
                @endif
            </div>
        </div>
    </div>

    <section class="bagian-ulasan">
        <h2 class="judul-bagian">Ulasan Pembaca</h2>
        <p class="ket">Rating rata-rata: <strong>{{ $rataRating ?: '-' }} / 5</strong> dari {{ $buku->reviews->count() }} ulasan.</p>

        <div class="dua-kotak" style="margin-top:16px;">
            <div class="kotak">
                <h3 style="margin-top:0;">{{ $ulasanSaya ? 'Perbarui ulasanmu' : 'Tulis ulasan' }}</h3>
                <form method="POST" action="{{ route('ulasan.simpan', $buku->book_id) }}">
                    @csrf
                    <div class="isian">
                        <label for="rating">Rating</label>
                        <select id="rating" name="rating" required>
                            <option value="">Pilih rating</option>
                            @for ($nilai = 5; $nilai >= 1; $nilai--)
                                <option value="{{ $nilai }}" {{ old('rating', $ulasanSaya->rating ?? '') == $nilai ? 'selected' : '' }}>{{ str_repeat('★', $nilai) }} ({{ $nilai }})</option>
                            @endfor
                        </select>
                        @error('rating')<p class="pesan-salah">{{ $message }}</p>@enderror
                    </div>
                    <div class="isian">
                        <label for="review_text">Komentar</label>
                        <textarea id="review_text" name="review_text" rows="4" required>{{ old('review_text', $ulasanSaya->review_text ?? '') }}</textarea>
                        @error('review_text')<p class="pesan-salah">{{ $message }}</p>@enderror
                    </div>
                    <button class="tombol" type="submit">Simpan Ulasan</button>
                </form>
            </div>

            <div>
                @forelse ($buku->reviews->sortByDesc('review_id') as $ulasan)
                    <article class="kotak ulasan-item">
                        <strong>{{ $ulasan->user->name ?? 'Pengguna' }}</strong>
                        <span class="rating-bintang">{{ str_repeat('★', $ulasan->rating) }}{{ str_repeat('☆', 5 - $ulasan->rating) }}</span>
                        <p>{{ $ulasan->review_text }}</p>
                        <small class="pesan-info">{{ $ulasan->created_at?->format('d/m/Y H:i') }}</small>
                    </article>
                @empty
                    <div class="kotak"><p class="pesan-info">Belum ada ulasan untuk buku ini.</p></div>
                @endforelse
            </div>
        </div>
    </section>

    @if ($lainnya->count() > 0)
        <h2 class="judul-bagian">Buku lain di kategori ini</h2>
        <div class="rak">
            @foreach ($lainnya as $b)
                @include('user._kartu', ['b' => $b])
            @endforeach
        </div>
    @endif
@endsection
