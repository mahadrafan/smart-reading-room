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
            <p class="penulis">{{ $buku->nama_penulis ?? 'Penulis belum diisi' }}</p>

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
                                <th>Kelas / jabatan</th>
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
                                        {{ ($pa->user && $pa->user->keterangan) ? $pa->user->keterangan : '-' }}
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
                        <a href="{{ route('peminjaman.index') }}">Lihat peminjamanku</a></p>
                @elseif ($bolehPinjamLagi)
                    <span class="tombol nonaktif">Bisa dipinjam lagi {{ $bolehPinjamLagi->format('d M Y') }}</span>
                    <p class="catatan">Kamu baru saja mengembalikan buku ini. Agar pembaca lain juga mendapat kesempatan,
                        buku yang sama bisa kamu pinjam lagi mulai {{ $bolehPinjamLagi->format('d M Y') }}.</p>
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

        <div class="dua-kotak" style="margin-top:16px;">
            <div class="kotak form-ulasan">
                @if ($ulasanSaya)
                    {{-- satu user hanya satu ulasan per buku: perubahan dilakukan di halaman Review & Ulasan --}}
                    <h3 style="margin-top:0;">Ulasanmu</h3>
                    <div class="bintang-ulasan" role="img" aria-label="Rating {{ $ulasanSaya->rating }} dari 5" style="padding-right:0;">
                        @for ($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= $ulasanSaya->rating ? 'isi' : '' }}" aria-hidden="true">★</span>
                        @endfor
                    </div>
                    <p class="teks-ulasan">“{{ $ulasanSaya->review_text }}”</p>
                    <div class="tanggal-ulasan">{{ $ulasanSaya->created_at?->format('d M Y') }}</div>
                    <p class="catatan">Kamu sudah memberi ulasan untuk buku ini. Setiap pembaca hanya bisa memberi satu ulasan per buku, tapi ulasanmu masih bisa diubah.</p>
                    <a href="{{ route('ulasan.index', ['ulasan' => $ulasanSaya->review_id, 'edit' => 1]) }}#ulasan-{{ $ulasanSaya->review_id }}" class="tombol">Edit Ulasan</a>
                @else
                <h3 style="margin-top:0;">Tulis ulasan</h3>
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
                @endif
            </div>

            @php
                // tampilan utama hanya 5 ulasan teratas (rating tertinggi, lalu terbaru); sisanya di halaman Review & Ulasan
                $jumlahUlasan = $buku->reviews->count();
                $daftarUlasan = $buku->reviews
                    ->sortBy([['rating', 'desc'], ['review_id', 'desc']])
                    ->take(5)
                    ->values();
            @endphp

            {{-- kartu ulasan: avatar pengulas, slider ulasan, dan rata-rata rating --}}
            <div class="ulasan-widget">
                <div class="ulasan-kepala">
                    @if ($daftarUlasan->count() > 0)
                        <div class="avatar-tumpuk" aria-hidden="true">
                            @foreach ($daftarUlasan->take(4) as $ulasan)
                                <span style="background: {{ $ulasan->user->warna_avatar ?? '#8b5e34' }};">{{ $ulasan->user->inisial ?? 'P' }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div>
                        <h3>Apa kata pembaca</h3>
                        <p>{{ $jumlahUlasan }} ulasan pembaca</p>
                    </div>
                    @if ($jumlahUlasan > 0)
                        <a href="{{ route('ulasan.index', ['buku' => $buku->book_id]) }}" class="rak-tautan lihat-semua-ulasan">Lihat semua ulasan →</a>
                    @endif
                </div>

                @if ($daftarUlasan->count() > 0)
                    <div class="slider-ulasan" @if ($daftarUlasan->count() > 1) data-slider-ulasan @endif>
                        @if ($daftarUlasan->count() > 1)
                            <div class="slider-navigasi">
                                <button type="button" class="slider-tombol" data-arah="-1" aria-label="Ulasan sebelumnya">&lsaquo;</button>
                                <button type="button" class="slider-tombol" data-arah="1" aria-label="Ulasan berikutnya">&rsaquo;</button>
                            </div>
                        @endif

                        <div class="slider-jalur" tabindex="0" aria-label="Daftar ulasan, geser untuk melihat ulasan lainnya">
                            @foreach ($daftarUlasan as $ulasan)
                                <article class="slide-ulasan" aria-label="Ulasan {{ $loop->iteration }} dari {{ $loop->count }}">
                                    <div class="bintang-ulasan" aria-label="Rating {{ $ulasan->rating }} dari 5">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <span class="{{ $i <= $ulasan->rating ? 'isi' : '' }}">★</span>
                                        @endfor
                                    </div>
                                    <div class="nama-pengulas">{{ $ulasan->user->name ?? 'Pengguna' }}</div>
                                    <p class="teks-ulasan">“{{ $ulasan->review_text ?: 'Tanpa komentar.' }}”</p>
                                    <div class="tanggal-ulasan">{{ $ulasan->created_at?->format('d M Y') }}</div>
                                </article>
                            @endforeach
                        </div>

                        @if ($daftarUlasan->count() > 1)
                            <div class="slider-penanda" aria-hidden="true">
                                @foreach ($daftarUlasan as $ulasan)
                                    <span class="{{ $loop->first ? 'aktif' : '' }}"></span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="slider-ulasan slider-kosong">
                        <p>Belum ada ulasan untuk buku ini. Jadilah pembaca pertama yang memberi ulasan!</p>
                    </div>
                @endif

                <div class="kotak-rating">
                    <div class="bintang-rata" aria-label="Rating rata-rata {{ $rataRating ?: 0 }} dari 5">
                        <span class="bintang-dasar">★★★★★</span>
                        <span class="bintang-isi" style="width: {{ ($rataRating ?: 0) / 5 * 100 }}%;">★★★★★</span>
                    </div>
                    <div class="angka-rating">{{ $rataRating ? number_format($rataRating, 1) : '-' }}</div>
                    <div class="label-rating">{{ $daftarUlasan->count() > 0 ? 'Rata-rata rating' : 'Belum ada rating' }}</div>
                </div>
            </div>
        </div>
    </section>

    @if ($lainnya->count() > 0)
        <h2 class="judul-bagian">Buku lain di kategori ini</h2>
        <div class="rak">
            @foreach ($lainnya as $b)
                {{-- kartu buku lain di kategori yang sama --}}
                <div class="kartu-buku">
                    <a href="{{ route('buku.detail', $b->book_id) }}" class="kartu-buku-gambar">
                        @include('user._sampul', ['b' => $b])
                        @if ($b->category)
                            <span class="label-kat label-kat-overlay">{{ $b->category->category_name }}</span>
                        @endif
                    </a>

                    <div class="info-buku">
                        <a href="{{ route('buku.detail', $b->book_id) }}" class="tautan-judul">
                            <h3>{{ $b->title }}</h3>
                        </a>
                        <p class="penulis">{{ $b->nama_penulis ?? 'Penulis belum diisi' }}</p>

                        @if ($b->description)
                            <p class="sinopsis-ringkas">{{ Str::limit($b->description, 90) }}</p>
                        @endif

                        <div class="baris-info">
                            <span>{{ $b->jumlah_dipinjam ?? 0 }}x dipinjam</span>
                            @if ($b->available_stock > 0)
                                <span class="stok ada">Tersedia {{ $b->available_stock }}</span>
                            @else
                                <span class="stok habis">Habis</span>
                            @endif
                        </div>

                        @if (isset($jedaPinjam[$b->book_id]))
                            {{-- baru dikembalikan user ini: jeda pinjam ulang --}}
                            <span class="tombol tombol-penuh nonaktif" title="Buku yang baru kamu kembalikan bisa dipinjam lagi mulai {{ $jedaPinjam[$b->book_id]->format('d M Y') }}">
                                Bisa dipinjam {{ $jedaPinjam[$b->book_id]->format('d M') }}
                            </span>
                        @elseif ($b->available_stock > 0)
                            <a href="{{ route('peminjaman.buat', ['buku' => $b->book_id]) }}" class="tombol tombol-penuh">Pinjam Buku</a>
                        @else
                            <span class="tombol tombol-penuh nonaktif">Stok habis</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection

@section('scripts')
<script>
    // slider ulasan: geser lewat tombol, swipe/touchpad (scroll-snap), atau tombol panah keyboard
    document.querySelectorAll('[data-slider-ulasan]').forEach(function (slider) {
        var jalur = slider.querySelector('.slider-jalur');
        var tombol = slider.querySelectorAll('.slider-tombol');
        var penanda = slider.querySelectorAll('.slider-penanda span');
        var jumlah = jalur.children.length;

        var aktif = 0;

        function posisi() {
            return Math.round(jalur.scrollLeft / jalur.clientWidth);
        }

        // perbarui tombol & titik penanda sesuai ulasan yang sedang tampil
        function perbarui(i) {
            aktif = Math.min(jumlah - 1, Math.max(0, i));
            tombol[0].disabled = aktif <= 0;
            tombol[1].disabled = aktif >= jumlah - 1;
            penanda.forEach(function (titik, n) { titik.classList.toggle('aktif', n === aktif); });
        }

        function geser(arah) {
            // status langsung diperbarui, tidak menunggu animasi scroll selesai
            perbarui(aktif + arah);
            jalur.scrollTo({ left: aktif * jalur.clientWidth, behavior: 'smooth' });
        }

        tombol.forEach(function (t) {
            t.addEventListener('click', function () { geser(parseInt(t.dataset.arah, 10)); });
        });
        jalur.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') { e.preventDefault(); geser(-1); }
            if (e.key === 'ArrowRight') { e.preventDefault(); geser(1); }
        });

        // geser manual (swipe / touchpad): baca posisi setelah scroll berhenti
        var jeda;
        jalur.addEventListener('scroll', function () {
            clearTimeout(jeda);
            jeda = setTimeout(function () { perbarui(posisi()); }, 120);
        });
        // saat ukuran layar berubah, pastikan ulasan yang aktif tetap pas di kotak
        window.addEventListener('resize', function () {
            jalur.scrollTo({ left: aktif * jalur.clientWidth, behavior: 'instant' });
        });
        perbarui(0);
    });
</script>
@endsection
