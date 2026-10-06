<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Reading Room - Perpustakaan Sekolah</title>
    <meta name="description" content="Cari buku perpustakaan sekolah, cek ketersediaannya, lalu ajukan peminjaman secara online.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
</head>
<body>
@php
    // sampul polos (tanpa file gambar): warna mengikuti kategori, sama seperti di portal peminjam
    $warnaSampul = fn ($b) => ['#8b5e34', '#3b2a1e', '#2f6f73', '#b5574b', '#5b7b3a', '#a07a2c'][($b->category_id ?? 0) % 6];
    $angka = fn ($n) => number_format($n, 0, ',', '.');
@endphp

    {{-- ================= NAVIGASI ================= --}}
    <header class="nav">
        <div class="wadah nav-isi">
            <a href="{{ route('beranda') }}" class="logo">
                <span class="logo-tanda" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v16H6.5a.5.5 0 0 0 0 1H20v3H6.5A2.5 2.5 0 0 1 4 19.5v-15Z"/></svg>
                </span>
                Smart Reading Room
            </a>
            <nav class="nav-tautan" aria-label="Bagian halaman">
                <a href="#koleksi">Koleksi</a>
                <a href="#favorit">Tentang</a>
                <a href="#cara">Cara Meminjam</a>
            </nav>
            <div class="nav-aksi">
                <a href="{{ route('login') }}" class="tombol tombol-garis">Masuk</a>
                <a href="{{ route('register') }}" class="tombol">Daftar</a>
            </div>
        </div>
    </header>

    <main>
        {{-- ================= HERO ================= --}}
        <section class="hero">
            <div class="wadah hero-isi">
                <div class="hero-teks">
                    <p class="label-kecil">Perpustakaan sekolah, kini online</p>
                    <h1>Pinjam buku perpustakaan tanpa harus antre</h1>
                    <p class="hero-ket">
                        Cari buku, cek ketersediaannya, lalu ajukan peminjaman dari mana saja.
                        Saat sudah dikonfirmasi, tinggal ambil bukunya di perpustakaan.
                    </p>

                    <p class="hero-catatan">
                        Belum punya akun? <a href="{{ route('register') }}">Daftar sebagai siswa atau tenaga pendidik</a>
                    </p>
                </div>

                {{-- tumpukan 3 buku terpopuler: klik buku di belakang untuk memindahkannya ke depan,
                     klik buku paling depan untuk membuka detailnya (diminta masuk dulu) --}}
                @if ($sorotan->isNotEmpty())
                    @php $utama = $sorotan->first(); @endphp
                    <div class="hero-buku" data-tumpukan>
                        <div class="tumpukan">
                            @foreach ($sorotan as $b)
                                <a href="{{ route('buku.detail', $b->book_id) }}" class="tumpukan-buku"
                                   data-posisi="{{ $loop->index }}" data-peringkat="{{ $loop->iteration }}"
                                   data-judul="{{ $b->title }}" data-penulis="{{ $b->nama_penulis ?? 'Penulis belum diisi' }}"
                                   aria-label="{{ $loop->first ? 'Lihat detail ' . $b->title : 'Tampilkan ' . $b->title . ' di depan' }}">
                                    <img src="{{ $b->cover_url }}" alt="Sampul {{ $b->title }}" @unless ($loop->first) loading="lazy" @endunless>
                                </a>
                            @endforeach
                        </div>
                        <div class="hero-buku-ket" aria-live="polite">
                            <span class="chip-kecil">Paling sering dipinjam <span data-ket-peringkat>#1</span></span>
                            <h2 data-ket-judul>{{ $utama->title }}</h2>
                            <p data-ket-penulis>{{ $utama->nama_penulis ?? 'Penulis belum diisi' }}</p>
                        </div>
                    </div>
                @endif
            </div>
            <a href="#koleksi" class="gulir" aria-label="Lihat koleksi">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
            </a>
        </section>

        {{-- ================= PALING SERING DIPINJAM ================= --}}
        @if ($populer->isNotEmpty())
            <section class="bagian" id="koleksi">
                <div class="wadah">
                    <h2 class="judul-bagian">Paling Sering Dipinjam</h2>
                    <p class="ket-bagian">Buku yang paling banyak dipinjam teman-temanmu. Masuk untuk melihat detail dan meminjamnya.</p>

                    {{-- 5 buku per halaman; halaman berikutnya dibuka dengan tombol > (tanpa JavaScript: halaman pertama saja) --}}
                    <div data-halaman-populer>
                    @foreach ($populer as $halaman)
                    <div class="rak-kartu" data-halaman="{{ $loop->index }}" @unless ($loop->first) hidden @endunless
                         aria-label="Halaman {{ $loop->iteration }} dari {{ $populer->count() }}">
                        @foreach ($halaman as $b)
                            <a href="{{ route('buku.detail', $b->book_id) }}" class="kartu">
                                <span class="kartu-sampul">
                                    @if ($b->cover_url)
                                        <img src="{{ $b->cover_url }}" alt="Sampul {{ $b->title }}" loading="lazy">
                                    @else
                                        <span class="sampul-polos" style="background: {{ $warnaSampul($b) }};">{{ Str::limit($b->title, 40) }}</span>
                                    @endif
                                    <span class="lencana {{ $b->available_stock > 0 ? '' : 'habis' }}">
                                        {{ $b->available_stock > 0 ? 'Tersedia ' . $b->available_stock : 'Habis' }}
                                    </span>
                                </span>
                                <span class="kartu-judul">{{ $b->title }}</span>
                                <span class="kartu-baris">
                                    <span class="kartu-penulis">{{ $b->nama_penulis ?? 'Penulis belum diisi' }}</span>
                                    <span class="kartu-angka">{{ $b->jumlah_dipinjam }}x</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                    @endforeach

                    <div class="bagian-bawah">
                        @if ($populer->count() > 1)
                            <div class="geser">
                                {{-- penanda halaman: ruas yang terisi = halaman yang sedang tampil --}}
                                <span class="penanda" aria-hidden="true">
                                    @foreach ($populer as $halaman)
                                        <span class="{{ $loop->first ? 'aktif' : '' }}"></span>
                                    @endforeach
                                </span>
                                <button type="button" class="tombol-geser" data-geser="-1" aria-label="5 buku sebelumnya" disabled>
                                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m15 6-6 6 6 6"/></svg>
                                </button>
                                <button type="button" class="tombol-geser" data-geser="1" aria-label="5 buku berikutnya">
                                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
                                </button>
                            </div>
                        @else
                            <span></span>
                        @endif
                        <a href="{{ route('dashboard', ['tampilan' => 'semua']) }}" class="tautan-panah">
                            Lihat semua koleksi
                            <svg viewBox="0 0 40 12" width="40" height="12" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M1 6h37m-5-5 5 5-5 5"/></svg>
                        </a>
                    </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- ================= TEMUKAN BUKU FAVORIT ================= --}}
        <section class="bagian bagian-krem" id="favorit">
            <div class="wadah favorit">
                @if ($terbaru->isNotEmpty())
                    <div class="favorit-sampul" aria-hidden="true">
                        @foreach ($terbaru as $b)
                            <img src="{{ $b->cover_url }}" alt="" loading="lazy">
                        @endforeach
                    </div>
                @endif

                <div class="favorit-teks">
                    <h2 class="judul-bagian rata-kiri">Temukan Buku Favoritmu di Sini</h2>
                    <p class="ket-bagian rata-kiri">
                        Dari novel, buku pelajaran, sampai buku komputer dan ekonomi, semua koleksi perpustakaan
                        bisa kamu jelajahi dalam satu tempat. Lihat stok yang tersedia, baca ulasan pembaca lain,
                        dan simpan riwayat bacaanmu sendiri.
                    </p>

                    <dl class="statistik">
                        <div>
                            <dt>Judul buku</dt>
                            <dd>{{ $angka($statistik['buku']) }}</dd>
                        </div>
                        <div>
                            <dt>Kategori</dt>
                            <dd>{{ $angka($statistik['kategori']) }}</dd>
                        </div>
                        <div>
                            <dt>Anggota</dt>
                            <dd>{{ $angka($statistik['anggota']) }}</dd>
                        </div>
                    </dl>

                    <a href="{{ route('login') }}" class="tombol tombol-garis tombol-panah">
                        Mulai menjelajah
                        <svg viewBox="0 0 24 12" width="22" height="12" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M1 6h21m-5-5 5 5-5 5"/></svg>
                    </a>
                </div>
            </div>
        </section>

        {{-- ================= CARA MEMINJAM ================= --}}
        <section class="bagian" id="cara">
            <div class="wadah">
                <h2 class="judul-bagian">Cara Meminjam</h2>
                <p class="ket-bagian">Empat langkah dari mencari buku sampai mengembalikannya.</p>

                <ol class="langkah">
                    <li>
                        <span class="langkah-nomor">01</span>
                        <h3>Daftar &amp; masuk</h3>
                        <p>Siswa mendaftar dengan NIS dan kelas, tenaga pendidik dengan NIP dan jabatan.</p>
                    </li>
                    <li>
                        <span class="langkah-nomor">02</span>
                        <h3>Ajukan peminjaman</h3>
                        <p>Pilih buku dan lama pinjam 1&ndash;{{ $aturan['maksDurasi'] }} hari. Maksimal {{ $aturan['batasAktif'] }} pinjaman aktif sekaligus.</p>
                    </li>
                    <li>
                        <span class="langkah-nomor">03</span>
                        <h3>Ambil di perpustakaan</h3>
                        <p>Setelah dikonfirmasi admin, ambil bukunya paling lambat 2 hari agar pengajuan tidak batal.</p>
                    </li>
                    <li>
                        <span class="langkah-nomor">04</span>
                        <h3>Kembalikan tepat waktu</h3>
                        <p>Pantau batas kembali di akunmu. Keterlambatan dikenai denda Rp{{ $angka($aturan['dendaPerHari']) }} per hari.</p>
                    </li>
                </ol>
            </div>
        </section>

        {{-- ================= AJAKAN ================= --}}
        <section class="ajakan">
            <div class="wadah ajakan-isi">
                <div>
                    <h2>Siap mulai membaca?</h2>
                    <p>Buat akun dalam satu menit, lalu ajukan peminjaman buku pertamamu.</p>
                </div>
                <div class="ajakan-aksi">
                    <a href="{{ route('register') }}" class="tombol tombol-terang">Daftar sekarang</a>
                    <a href="{{ route('login') }}" class="tombol tombol-garis-terang">Saya sudah punya akun</a>
                </div>
            </div>
        </section>
    </main>

    {{-- ================= FOOTER ================= --}}
    <footer class="kaki">
        <div class="wadah kaki-isi">
            <div>
                <a href="{{ route('beranda') }}" class="logo">
                    <span class="logo-tanda" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v16H6.5a.5.5 0 0 0 0 1H20v3H6.5A2.5 2.5 0 0 1 4 19.5v-15Z"/></svg>
                    </span>
                    Smart Reading Room
                </a>
                <p class="kaki-ket">Sistem peminjaman buku perpustakaan sekolah.</p>
            </div>
            <p class="kaki-bawah">&copy; {{ date('Y') }} Smart Reading Room</p>
        </div>
    </footer>

    <script>
    // tumpukan buku di hero: buku yang diklik pindah ke depan, keterangan di bawahnya ikut berganti
    (function () {
        var wadah = document.querySelector('[data-tumpukan]');
        if (!wadah) { return; }
        var buku = Array.prototype.slice.call(wadah.querySelectorAll('.tumpukan-buku'));

        function tampilkan(dipilih) {
            // urutan baru: buku yang dipilih di depan, sisanya tetap dengan urutan semula
            var urutan = [dipilih].concat(buku.filter(function (b) { return b !== dipilih; })
                .sort(function (a, b) { return a.dataset.posisi - b.dataset.posisi; }));
            urutan.forEach(function (b, i) {
                b.dataset.posisi = i;
                b.setAttribute('aria-label', (i === 0 ? 'Lihat detail ' : 'Tampilkan ') + b.dataset.judul + (i === 0 ? '' : ' di depan'));
            });
            wadah.querySelector('[data-ket-peringkat]').textContent = '#' + dipilih.dataset.peringkat;
            wadah.querySelector('[data-ket-judul]').textContent = dipilih.dataset.judul;
            wadah.querySelector('[data-ket-penulis]').textContent = dipilih.dataset.penulis;
        }

        buku.forEach(function (b) {
            b.addEventListener('click', function (e) {
                if (b.dataset.posisi !== '0') {   // buku paling depan tetap membuka detail
                    e.preventDefault();
                    tampilkan(b);
                }
            });
        });
    })();

    // "Paling Sering Dipinjam": tombol < > menampilkan 5 buku sebelumnya / berikutnya
    (function () {
        var wadah = document.querySelector('[data-halaman-populer]');
        if (!wadah) { return; }
        var halaman = wadah.querySelectorAll('[data-halaman]');
        var ruas = wadah.querySelectorAll('.penanda span');
        var tombol = wadah.querySelectorAll('[data-geser]');
        var aktif = 0;

        tombol.forEach(function (t) {
            t.addEventListener('click', function () {
                aktif = Math.min(halaman.length - 1, Math.max(0, aktif + parseInt(t.dataset.geser, 10)));
                halaman.forEach(function (h, i) { h.hidden = i !== aktif; });
                ruas.forEach(function (r, i) { r.classList.toggle('aktif', i === aktif); });
                tombol[0].disabled = aktif === 0;
                tombol[1].disabled = aktif === halaman.length - 1;
            });
        });
    })();
    </script>
</body>
</html>
