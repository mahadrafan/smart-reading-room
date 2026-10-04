@extends('layouts.admin')

@section('title', 'Kelola Ulasan')

@section('content')
    <div class="kepala">
        <h1>Kelola Ulasan</h1>
        <p class="ket">Pantau rating dan hapus komentar yang tidak sesuai.</p>
    </div>

    {{-- pencarian, pilihan buku (bisa dicari), dan filter rating; sama seperti halaman Review & Ulasan --}}
    <form method="GET" action="{{ route('admin.reviews.index') }}" class="filter-ulasan" data-filter-ulasan>
        <label class="sr-only" for="cari_ulasan">Cari ulasan</label>
        <input type="search" id="cari_ulasan" name="q" value="{{ $cari }}" class="cari-input"
               placeholder="Cari ulasan, pengguna, atau buku..." title="Cari berdasarkan isi komentar, nama pengguna, atau judul buku">

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
            <option value="" data-meta="{{ $jumlahTotal }} ulasan">Semua buku</option>
            @foreach ($daftarBuku as $b)
                <option value="{{ $b->book_id }}" data-meta="{{ $b->jumlah_ulasan }} ulasan{{ $b->is_active ? '' : ' · nonaktif' }}"
                        {{ $bukuDipilih?->book_id === $b->book_id ? 'selected' : '' }}>
                    {{ $b->title }}
                </option>
            @endforeach
        </select>

        {{-- filter rating: kotak centang 5 bintang sampai 1 bintang, boleh lebih dari satu --}}
        <details class="panel-rating" data-panel-rating>
            <summary class="pemicu-rating {{ $bintang ? 'ada-filter' : '' }}">
                <span class="sr-only">Filter rating:</span>
                <span>{{ $bintang ? implode(', ', $bintang) . ' bintang' : 'Semua rating' }}</span>
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
                    <a href="{{ route('admin.reviews.index', collect($filter)->except('bintang')->all()) }}" class="hapus-filter-rating">Tampilkan semua rating</a>
                @endif
            </fieldset>
        </details>

        <button type="submit" class="tombol-outline" data-tombol-terapkan>Terapkan</button>

        @if ($filter)
            <a href="{{ route('admin.reviews.index') }}" class="hapus-semua-filter">Hapus filter</a>
        @endif
    </form>

    <p class="ringkasan-hasil">
        @if ($reviews->total() > 0)
            Menampilkan {{ $reviews->firstItem() }}–{{ $reviews->lastItem() }} dari <strong>{{ $reviews->total() }}</strong>
            ulasan{{ $filter ? ' yang cocok' : '' }}{{ $filter ? ' (total ' . $jumlahTotal . ')' : '' }}
        @endif
    </p>

    <div class="kotak">
        <table class="tabel">
            <thead><tr><th>Pengguna</th><th>Buku</th><th>Rating</th><th>Komentar</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($reviews as $review)
                    <tr>
                        <td>{{ $review->user->name ?? '-' }}</td>
                        <td>
                            {{ $review->book->title ?? '-' }}
                            @if ($review->book && ! $review->book->is_active)
                                <span class="label-nonaktif">nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <span class="bintang-kartu" role="img" aria-label="Rating {{ $review->rating }} dari 5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <span class="{{ $i <= $review->rating ? 'isi' : '' }}" aria-hidden="true">★</span>
                                @endfor
                            </span>
                        </td>
                        <td style="white-space:normal; min-width:240px;">{{ $review->review_text }}</td>
                        <td>{{ $review->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="aksi">
                            <form method="POST" action="{{ route('admin.reviews.destroy', $review->review_id) }}"
                                  onsubmit="return confirm({{ Js::from('Hapus ulasan ' . ($review->user->name ?? '-') . ' untuk buku "' . ($review->book->title ?? '-') . '"?') }});">
                                @csrf
                                @method('DELETE')
                                {{-- setelah dihapus, kembali ke daftar dengan filter & halaman yang sama --}}
                                @include('admin._bawa-filter', ['bawa' => array_merge($filter, ['page' => $reviews->currentPage()])])
                                <button class="bahaya">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="kosong-tabel">
                            @if ($filter)
                                Tidak ada ulasan{{ $bintang ? ' dengan rating ' . implode(', ', $bintang) . ' bintang' : '' }}{{ $bukuDipilih ? ' untuk "' . $bukuDipilih->title . '"' : '' }}{{ $cari !== '' ? ' yang cocok dengan “' . $cari . '”' : '' }}.
                                <a href="{{ route('admin.reviews.index') }}">Hapus filter</a>
                            @else
                                Belum ada ulasan.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin._halaman', ['halaman' => $reviews])
@endsection

@section('scripts')
    <script src="{{ asset('js/filter-daftar.js') }}"></script>
    <script>
        var formFilter = document.querySelector('[data-filter-ulasan]');
        if (formFilter) { pasangFilterDaftar(formFilter, 'admin-ulasan-panel-rating'); }
    </script>
@endsection
