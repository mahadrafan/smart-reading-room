@extends('layouts.user')

@section('title', 'Review & Ulasan')

@section('content')
    @php
        $milikSaya = fn ($u) => $u->user_id == Auth::id();
        // tautan memilih ulasan tanpa JavaScript (filter halaman tetap dibawa)
        $urlPilih = fn ($u, $edit = false) => route('ulasan.index', $filter + ['ulasan' => $u->review_id] + ($edit ? ['edit' => 1] : [])) . '#ulasan-' . $u->review_id;
        $filterTanpaSaya = collect($filter)->except('saya')->all();
        $judulJejak = $bukuDipilih->title ?? ($hanyaSaya ? 'Ulasan Saya' : 'Semua Buku');
    @endphp

    {{-- kepala halaman: "Review & Ulasan › nama buku" --}}
    <div class="kepala">
        <h1 class="jejak-ulasan">
            @if ($bukuDipilih)
                <a href="{{ route('ulasan.index', $hanyaSaya ? ['saya' => 1] : []) }}" class="jejak-induk">Review &amp; Ulasan</a>
            @else
                <span class="jejak-induk">Review &amp; Ulasan</span>
            @endif
            <span class="jejak-pemisah" aria-hidden="true">›</span>
            <span class="jejak-aktif">{{ $judulJejak }}</span>
        </h1>
        <p class="ket">Baca ulasan dan rating pembaca untuk setiap buku, lalu ubah ulasan milikmu bila perlu.</p>
    </div>

    {{-- tab ulasan + pencarian, pilihan buku (bisa dicari), dan filter rating --}}
    <div class="filter-baris">
        <div class="chip-baris">
            <a href="{{ route('ulasan.index', $filterTanpaSaya) }}" class="chip {{ !$hanyaSaya ? 'aktif' : '' }}">
                Semua Ulasan <strong>{{ $jumlahSemua }}</strong>
            </a>
            <a href="{{ route('ulasan.index', $filterTanpaSaya + ['saya' => 1]) }}" class="chip {{ $hanyaSaya ? 'aktif' : '' }}">
                Ulasan Saya <strong>{{ $jumlahSaya }}</strong>
            </a>
        </div>

        <form method="GET" action="{{ route('ulasan.index') }}" class="filter-ulasan" data-filter-ulasan>
            @if ($hanyaSaya)<input type="hidden" name="saya" value="1">@endif
            <label class="sr-only" for="cari_ulasan">Cari ulasan</label>
            <input type="search" id="cari_ulasan" name="q" value="{{ $cari }}" class="cari-input" placeholder="Cari ulasan..." title="Cari berdasarkan judul buku, nama pembaca, atau isi ulasan">

            {{-- pencarian buku: aktif lewat JavaScript, nilainya disalin ke <select> di bawah --}}
            <label class="sr-only" for="filter_buku" id="label_filter_buku">Pilih buku</label>
            <div class="pilih-buku pilih-buku-filter" data-cari-buku-filter hidden>
                <input type="text" id="cari_buku_filter" placeholder="Semua buku" autocomplete="off"
                       role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="daftar_buku_filter">
                <ul id="daftar_buku_filter" class="pilih-buku-daftar" role="listbox" aria-labelledby="label_filter_buku" hidden>
                    <li class="pilih-buku-kosong" hidden>Tidak ada buku yang cocok.</li>
                </ul>
            </div>
            <select id="filter_buku" name="buku" class="pilih-filter">
                <option value="" data-meta="{{ $daftarBuku->sum('jumlah_rating') }} ulasan">Semua buku</option>
                @foreach ($daftarBuku as $b)
                    <option value="{{ $b->book_id }}" data-meta="{{ $b->jumlah_rating }} ulasan" {{ $bukuDipilih?->book_id === $b->book_id ? 'selected' : '' }}>
                        {{ $b->title }}
                    </option>
                @endforeach
            </select>

            {{-- filter rating: kotak centang 5 bintang sampai 1 bintang, boleh lebih dari satu --}}
            @php
                $labelRating = $bintang ? implode(', ', $bintang) . ' bintang' : 'Semua rating';
            @endphp
            <details class="panel-rating" data-panel-rating>
                <summary class="pemicu-rating {{ $bintang ? 'ada-filter' : '' }}">
                    <span class="sr-only">Filter rating:</span>
                    <span>{{ $labelRating }}</span>
                    @if (count($bintang) > 1)<span class="jumlah-filter" aria-hidden="true">{{ count($bintang) }}</span>@endif
                </summary>
                <fieldset class="panel-rating-isi">
                    <legend class="panel-rating-judul">Filter rating</legend>
                    @for ($n = 5; $n >= 1; $n--)
                        @php $dicentang = in_array($n, $bintang, true); @endphp
                        <label class="baris-rating {{ $dicentang ? 'dicentang' : '' }}">
                            <span class="bintang-kartu" role="img" aria-label="{{ $n }} bintang">
                                @for ($i = 1; $i <= 5; $i++)
                                    <span class="{{ $i <= $n ? 'isi' : '' }}" aria-hidden="true">★</span>
                                @endfor
                            </span>
                            <span class="jumlah-baris-rating">{{ $jumlahRating[$n] ?? 0 }}<span class="sr-only"> ulasan</span></span>
                            <input type="checkbox" name="bintang[]" value="{{ $n }}" class="centang-rating" {{ $dicentang ? 'checked' : '' }}>
                        </label>
                    @endfor
                    @if ($bintang)
                        <a href="{{ route('ulasan.index', collect($filter)->except('bintang')->all()) }}" class="hapus-filter-rating">Tampilkan semua rating</a>
                    @endif
                </fieldset>
            </details>

            <button type="submit" class="tombol-outline" data-tombol-terapkan>Terapkan</button>
        </form>
    </div>

    @if ($daftarUlasan->isEmpty())
        <div class="kotak kosong-ulasan">
            @if ($cari !== '' || $bintang)
                <p>
                    Tidak ada ulasan{{ $bintang ? ' dengan rating ' . implode(', ', $bintang) . ' bintang' : '' }}{{ $cari !== '' ? ' yang cocok dengan “' . $cari . '”' : '' }}.
                </p>
                <a href="{{ route('ulasan.index', collect($filter)->except(['q', 'bintang'])->all()) }}" class="tombol-luar">Hapus filter</a>
            @elseif ($hanyaSaya)
                <p>Kamu belum memberi ulasan{{ $bukuDipilih ? ' untuk buku ini' : '' }}. Ulasan bisa ditulis dari halaman detail buku.</p>
                <a href="{{ $bukuDipilih ? route('buku.detail', $bukuDipilih->book_id) : route('dashboard') }}" class="tombol">{{ $bukuDipilih ? 'Buka detail buku' : 'Buka Katalog Buku' }}</a>
            @else
                <p>Belum ada ulasan{{ $bukuDipilih ? ' untuk buku ini' : '' }}.</p>
            @endif
        </div>
    @else
        <div class="papan-ulasan" data-papan-ulasan>
            <div class="daftar-ulasan">
                {{-- ringkasan rating buku saat disaring per buku --}}
                @if ($bukuDipilih)
                    <div class="ringkasan-buku-ulasan">
                        <div class="ringkasan-buku-sampul">
                            @if ($bukuDipilih->cover_url)
                                <img src="{{ $bukuDipilih->cover_url }}" alt="">
                            @else
                                <span aria-hidden="true">📘</span>
                            @endif
                        </div>
                        <div class="ringkasan-buku-info">
                            <div class="ringkasan-buku-judul">{{ $bukuDipilih->title }}</div>
                            <div class="ringkasan-buku-penulis">{{ $bukuDipilih->nama_penulis ?? 'Penulis tidak diketahui' }}</div>
                        </div>
                        <div class="ringkasan-buku-rating">
                            <div class="bintang-rata" role="img" aria-label="Rating rata-rata {{ number_format($bukuDipilih->rata_rating, 1) }} dari 5">
                                <span class="bintang-dasar" aria-hidden="true">★★★★★</span>
                                <span class="bintang-isi" aria-hidden="true" style="width: {{ $bukuDipilih->rata_rating / 5 * 100 }}%;">★★★★★</span>
                            </div>
                            <div><strong>{{ number_format($bukuDipilih->rata_rating, 1) }}</strong> · {{ $bukuDipilih->jumlah_rating }} ulasan</div>
                        </div>
                        <a href="{{ route('buku.detail', $bukuDipilih->book_id) }}" class="rak-tautan">Lihat buku →</a>
                    </div>
                @endif

                @include('user._navigasi-ulasan')

                <div class="halaman-ulasan" data-halaman-ulasan>
                @foreach ($daftarUlasan as $u)
                    @php
                        $milik = $milikSaya($u);
                        $terpilih = $dipilih && (int) $dipilih->review_id === (int) $u->review_id;
                        $sedangEdit = $terpilih && $modeEdit;
                    @endphp
                    <article id="ulasan-{{ $u->review_id }}"
                             class="kartu-ulasan {{ $terpilih ? 'terpilih' : '' }} {{ $sedangEdit ? 'sedang-edit' : '' }}"
                             data-kartu-ulasan
                             data-id="{{ $u->review_id }}"
                             data-milik="{{ $milik ? 1 : 0 }}"
                             data-judul="{{ $u->book->title }}"
                             data-pengulas="{{ $milik ? 'Ulasanmu' : 'Ulasan ' . ($u->user->name ?? 'Pengguna') }}"
                             data-pilih-url="{{ $urlPilih($u) }}"
                             data-edit-url="{{ $urlPilih($u, true) }}"
                             data-hapus-url="{{ route('ulasan.hapus', $u->review_id) }}"
                             data-buku-url="{{ route('buku.detail', $u->book_id) }}"
                             @if ($terpilih) aria-current="true" @endif>
                        <div class="kartu-ulasan-kepala">
                            <span class="avatar-ulasan" style="background: {{ $u->user->warna_avatar ?? '#8b5e34' }};" aria-hidden="true">{{ $u->user->inisial ?? 'P' }}</span>
                            <div class="pengulas">
                                <div class="pengulas-nama">
                                    <a href="{{ $urlPilih($u) }}" data-pilih-ulasan>{{ $u->user->name ?? 'Pengguna' }}</a>
                                    @if ($milik)<span class="label-kamu">Ulasanmu</span>@endif
                                </div>
                                <time class="pengulas-tanggal" datetime="{{ $u->created_at?->toDateString() }}">{{ $u->created_at?->format('d M Y') }}</time>
                                {{-- judul buku tidak perlu diulang saat halaman sudah disaring per buku --}}
                                @unless ($bukuDipilih)
                                    <div class="pengulas-buku">
                                        untuk <a href="{{ route('buku.detail', $u->book_id) }}" class="judul-diulas">{{ $u->book->title }}</a>
                                    </div>
                                @endunless
                            </div>
                            <div class="nilai-ulasan">
                                <span class="bintang-kartu" role="img" aria-label="Rating {{ $u->rating }} dari 5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="{{ $i <= $u->rating ? 'isi' : '' }}" aria-hidden="true">★</span>
                                    @endfor
                                </span>
                            </div>
                        </div>

                        <p class="isi-ulasan">{{ $u->review_text }}</p>

                        {{-- form edit hanya untuk ulasan milik sendiri; tampil saat tombol Edit Ulasan ditekan --}}
                        @if ($milik)
                            <form method="POST" action="{{ route('ulasan.update', $u->review_id) }}" class="form-edit-ulasan" data-form-edit>
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="ulasan_id" value="{{ $u->review_id }}">
                                <input type="hidden" name="halaman" value="{{ $halaman }}">
                                @foreach ($filter as $nama => $nilai)
                                    @foreach ((array) $nilai as $isi)
                                        <input type="hidden" name="{{ is_array($nilai) ? $nama . '[]' : $nama }}" value="{{ $isi }}">
                                    @endforeach
                                @endforeach

                                @php
                                    $pakaiLama = $sedangEdit && (int) old('ulasan_id') === (int) $u->review_id;
                                    $ratingAwal = (int) ($pakaiLama ? old('rating') : $u->rating);
                                @endphp
                                <fieldset class="pilih-bintang">
                                    <legend>Rating</legend>
                                    <div class="pilih-bintang-baris" data-pilih-bintang>
                                        @for ($n = 1; $n <= 5; $n++)
                                            <input type="radio" id="rating-{{ $u->review_id }}-{{ $n }}" name="rating" value="{{ $n }}" {{ $ratingAwal === $n ? 'checked' : '' }} required>
                                            <label for="rating-{{ $u->review_id }}-{{ $n }}" class="{{ $n <= $ratingAwal ? 'isi' : '' }}"><span aria-hidden="true">★</span><span class="sr-only">{{ $n }} bintang</span></label>
                                        @endfor
                                    </div>
                                    @if ($sedangEdit)
                                        @error('rating')<p class="pesan-salah">{{ $message }}</p>@enderror
                                    @endif
                                </fieldset>

                                <div class="isian">
                                    <label for="teks-{{ $u->review_id }}">Ulasan</label>
                                    <textarea id="teks-{{ $u->review_id }}" name="review_text" rows="4" maxlength="1000" required>{{ $pakaiLama ? old('review_text') : $u->review_text }}</textarea>
                                    @if ($sedangEdit)
                                        @error('review_text')<p class="pesan-salah">{{ $message }}</p>@enderror
                                    @endif
                                </div>

                                <div class="form-aksi">
                                    <button type="submit" class="tombol">Simpan Perubahan</button>
                                    <a href="{{ $urlPilih($u) }}" class="tombol-luar" data-batal-edit>Batal</a>
                                </div>
                            </form>
                        @endif
                    </article>
                @endforeach
                </div>
            </div>

            {{-- panel aksi untuk ulasan yang sedang dipilih --}}
            @php $dipilihMilik = $dipilih && $milikSaya($dipilih); @endphp
            <aside class="panel-aksi-ulasan" aria-label="Aksi ulasan terpilih" data-panel-aksi>
                <div class="panel-aksi-judul">Ulasan dipilih</div>
                <p class="panel-aksi-info" data-panel-info>
                    {{ $dipilihMilik ? 'Ulasanmu' : 'Ulasan ' . ($dipilih->user->name ?? 'Pengguna') }} untuk <strong>{{ $dipilih->book->title }}</strong>
                </p>

                <div class="panel-aksi-daftar" data-aksi-milik @unless ($dipilihMilik) hidden @endunless>
                    <a href="{{ $urlPilih($dipilih, true) }}" class="tombol-pil" data-tombol-edit>
                        Edit Ulasan
                        <span class="tombol-pil-ikon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4z"/><path d="m13.5 6.5 4 4"/></svg>
                        </span>
                    </a>
                    <form method="POST" action="{{ route('ulasan.hapus', $dipilih->review_id) }}" data-form-hapus>
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="halaman" value="{{ $halaman }}">
                        @foreach ($filter as $nama => $nilai)
                            @foreach ((array) $nilai as $isi)
                                <input type="hidden" name="{{ is_array($nilai) ? $nama . '[]' : $nama }}" value="{{ $isi }}">
                            @endforeach
                        @endforeach
                        <button type="submit" class="aksi-ulasan aksi-hapus">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="M6 7l1 13h10l1-13"/></svg>
                            Hapus Ulasan
                        </button>
                    </form>
                </div>
                <p class="panel-aksi-catatan" data-aksi-bukan-milik @if ($dipilihMilik) hidden @endif>
                    Ulasan pembaca lain hanya bisa dibaca. Kamu hanya bisa mengedit ulasan milikmu sendiri.
                </p>

                <a href="{{ route('buku.detail', $dipilih->book_id) }}" class="aksi-ulasan" data-tautan-buku>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z"/><path d="M4 20.5A2.5 2.5 0 0 0 6.5 23H20v-5"/></svg>
                    Lihat Detail Buku
                </a>
            </aside>
        </div>
    @endif
@endsection

@section('scripts')
<script src="{{ asset('js/filter-daftar.js') }}?v={{ filemtime(public_path('js/filter-daftar.js')) }}"></script>
<script>
(function () {
    // filter: pilihan buku & rating langsung diterapkan (public/js/filter-daftar.js)
    var formFilter = document.querySelector('[data-filter-ulasan]');
    if (formFilter) { pasangFilterDaftar(formFilter, 'ulasan-panel-rating'); }

    var papan = document.querySelector('[data-papan-ulasan]');
    if (!papan) { return; }

    // tombol ‹ ›: 5 ulasan berikutnya / sebelumnya masuk dengan efek geser sesuai arah
    var kunciArah = 'ulasan-arah-halaman';
    papan.querySelectorAll('[data-arah-halaman]').forEach(function (t) {
        t.addEventListener('click', function () {
            try { sessionStorage.setItem(kunciArah, t.dataset.arahHalaman); } catch (e) {}
        });
    });
    var arah = null;
    try { arah = sessionStorage.getItem(kunciArah); sessionStorage.removeItem(kunciArah); } catch (e) {}
    if (arah === 'maju' || arah === 'mundur') {
        papan.querySelector('[data-halaman-ulasan]').classList.add('masuk-' + arah);
    }

    var kartu = Array.prototype.slice.call(papan.querySelectorAll('[data-kartu-ulasan]'));
    var panelInfo = papan.querySelector('[data-panel-info]');
    var aksiMilik = papan.querySelector('[data-aksi-milik]');
    var aksiBukanMilik = papan.querySelector('[data-aksi-bukan-milik]');
    var tombolEdit = papan.querySelector('[data-tombol-edit]');
    var formHapus = papan.querySelector('[data-form-hapus]');
    var tautanBuku = papan.querySelector('[data-tautan-buku]');

    // bintang rating di form edit: terisi sampai nilai yang dipilih, atau sampai bintang yang disorot
    function warnaiBintang(baris, sorot) {
        var cek = baris.querySelector('input:checked');
        var nilai = sorot || (cek ? Number(cek.value) : 0);
        baris.querySelectorAll('label').forEach(function (l, i) { l.classList.toggle('isi', i < nilai); });
    }
    papan.querySelectorAll('[data-pilih-bintang]').forEach(function (baris) {
        baris.addEventListener('change', function () { warnaiBintang(baris, 0); });
        baris.querySelectorAll('label').forEach(function (l, i) {
            l.addEventListener('mouseenter', function () { warnaiBintang(baris, i + 1); });
        });
        baris.addEventListener('mouseleave', function () { warnaiBintang(baris, 0); });
    });

    function terpilih() { return papan.querySelector('.kartu-ulasan.terpilih'); }

    function tutupEdit(k) {
        var form = k.querySelector('[data-form-edit]');
        if (form && k.classList.contains('sedang-edit')) {
            form.reset();
            warnaiBintang(form.querySelector('[data-pilih-bintang]'), 0);
            k.classList.remove('sedang-edit');
        }
    }

    function pilih(k) {
        if (k === terpilih()) { return; }
        kartu.forEach(function (x) {
            tutupEdit(x);
            x.classList.toggle('terpilih', x === k);
            if (x === k) { x.setAttribute('aria-current', 'true'); } else { x.removeAttribute('aria-current'); }
        });

        var milik = k.dataset.milik === '1';
        panelInfo.textContent = '';
        panelInfo.append(k.dataset.pengulas + ' untuk ');
        var judul = document.createElement('strong');
        judul.textContent = k.dataset.judul;
        panelInfo.append(judul);

        aksiMilik.hidden = !milik;
        aksiBukanMilik.hidden = milik;
        tombolEdit.href = k.dataset.editUrl;
        formHapus.action = k.dataset.hapusUrl;
        tautanBuku.href = k.dataset.bukuUrl;
        history.replaceState(null, '', k.dataset.pilihUrl);
    }

    function bukaEdit(k) {
        k.classList.add('sedang-edit');
        var dipilih = k.querySelector('input[name="rating"]:checked') || k.querySelector('textarea');
        dipilih.focus();
    }

    kartu.forEach(function (k) {
        k.addEventListener('click', function (e) {
            // klik pada tautan judul buku / isi form tetap berjalan seperti biasa
            var target = e.target.closest('a, button, input, textarea, label, select');
            if (target && !target.hasAttribute('data-pilih-ulasan')) { return; }
            if (target) { e.preventDefault(); }
            pilih(k);
        });

        var batal = k.querySelector('[data-batal-edit]');
        if (batal) {
            batal.addEventListener('click', function (e) {
                e.preventDefault();
                tutupEdit(k);
                k.querySelector('[data-pilih-ulasan]').focus();
            });
        }
    });

    tombolEdit.addEventListener('click', function (e) {
        var k = terpilih();
        if (!k || k.dataset.milik !== '1') { return; }
        e.preventDefault();
        bukaEdit(k);
    });

    formHapus.addEventListener('submit', function (e) {
        var k = terpilih();
        var judul = k ? k.dataset.judul : 'buku ini';
        if (!confirm('Hapus ulasanmu untuk "' + judul + '"? Ulasan yang dihapus tidak bisa dikembalikan.')) {
            e.preventDefault();
        }
    });

    // dibuka dari tombol "Edit Ulasan" di halaman detail buku: arahkan ke ulasan yang diedit
    var awal = terpilih();
    if (awal && awal.classList.contains('sedang-edit')) {
        awal.scrollIntoView({ block: 'center' });
        bukaEdit(awal);
    }
})();
</script>
@endsection
