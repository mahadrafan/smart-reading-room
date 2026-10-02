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

        <div class="dua-kotak" style="margin-top:16px;">
            <div class="kotak form-ulasan">
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

            @php
                $daftarUlasan = $buku->reviews->sortByDesc('review_id')->values();
                $warnaAvatar = ['#8671c9', '#2f6f73', '#b5574b', '#a07a2c', '#1b2340', '#5b7b3a'];
                $inisialNama = function ($nama) {
                    $kata = preg_split('/\s+/', trim($nama ?: 'P'));
                    return mb_strtoupper(mb_substr($kata[0], 0, 1) . (isset($kata[1]) ? mb_substr($kata[1], 0, 1) : ''));
                };
            @endphp

            {{-- kartu ulasan: avatar pengulas, slider ulasan, dan rata-rata rating --}}
            <div class="ulasan-widget">
                <div class="ulasan-kepala">
                    @if ($daftarUlasan->count() > 0)
                        <div class="avatar-tumpuk" aria-hidden="true">
                            @foreach ($daftarUlasan->take(4) as $ulasan)
                                <span style="background: {{ $warnaAvatar[$ulasan->user_id % count($warnaAvatar)] }};">{{ $inisialNama($ulasan->user->name ?? null) }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div>
                        <h3>Apa kata pembaca</h3>
                        <p>{{ $daftarUlasan->count() }} ulasan pembaca</p>
                    </div>
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
                @include('user._kartu', ['b' => $b])
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
