@extends('layouts.admin')

@section('title', 'Request Masuk')

@section('content')
    @php
        // pencarian, filter buku, dan halaman ikut dikirim saat Konfirmasi / Tolak agar kembali ke tampilan yang sama
        $bawaFilter = array_merge($filter, ['page' => $daftar->currentPage()]);
    @endphp

    <div class="kepala">
        <h1>Request Masuk</h1>
        <p class="ket">Pengajuan peminjaman yang menunggu persetujuan.</p>
    </div>

    {{-- cari nama peminjam + pilihan buku (bisa dicari, sama seperti Kelola Ulasan) --}}
    <form method="GET" action="{{ route('admin.peminjaman.index') }}" class="filter-ulasan" data-filter-daftar>
        <label class="sr-only" for="cari_peminjam">Cari nama peminjam</label>
        <input type="search" id="cari_peminjam" name="q" value="{{ $cari }}" class="cari-input"
               placeholder="Cari nama peminjam atau NIS..." title="Cari berdasarkan nama peminjam atau NIS/NIP">

        <label class="sr-only" for="filter_buku" id="label_filter_buku">Pilih buku</label>
        <div class="pilih-buku pilih-buku-filter" data-cari-buku-filter hidden>
            <input type="text" id="cari_buku_filter" placeholder="Semua buku" autocomplete="off"
                   role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="daftar_buku_filter">
            <ul id="daftar_buku_filter" class="pilih-buku-daftar" role="listbox" aria-labelledby="label_filter_buku" hidden>
                <li class="pilih-buku-kosong" hidden>Tidak ada buku yang cocok.</li>
            </ul>
        </div>
        <select id="filter_buku" name="buku" class="pilih-filter">
            <option value="" data-meta="{{ $jumlahTotal }} pengajuan">Semua buku</option>
            @foreach ($daftarBuku as $b)
                <option value="{{ $b->book_id }}" data-meta="{{ $b->jumlah_menunggu }} pengajuan" {{ $bukuDipilih?->book_id === $b->book_id ? 'selected' : '' }}>
                    {{ $b->title }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="tombol tombol-kecil">Cari</button>

        @if ($filter)
            <a href="{{ route('admin.peminjaman.index') }}" class="hapus-semua-filter">Hapus filter</a>
        @endif
    </form>

    <p class="ringkasan-hasil">
        @if ($daftar->total() > 0)
            Menampilkan {{ $daftar->firstItem() }}–{{ $daftar->lastItem() }} dari <strong>{{ $daftar->total() }}</strong>
            pengajuan{{ $filter ? ' yang cocok (total ' . $jumlahTotal . ')' : '' }}
        @endif
    </p>

    <div class="kotak">
        <table class="tabel">
            <thead>
                <tr>
                    <th>No. peminjaman</th>
                    <th>Peminjam</th>
                    <th>Kontak</th>
                    <th>Buku</th>
                    <th>Diajukan</th>
                    <th>Durasi</th>
                    <th>Perkiraan kembali</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $p)
                    <tr>
                        <td>{{ $p->kode }}</td>
                        <td>{{ $p->user->name }} ({{ $p->user->nim_nip }})</td>
                        <td><a href="mailto:{{ $p->user->email }}">{{ $p->user->email }}</a><br><a href="tel:{{ $p->user->phone }}">{{ $p->user->phone ?: '-' }}</a></td>
                        <td>{{ $p->book->title }}</td>
                        <td>{{ $p->request_date ? $p->request_date->format('d/m/Y') : '-' }}</td>
                        {{-- pengajuan belum punya tanggal pinjam; tampilkan lama pinjam yang diminta --}}
                        <td>{{ $p->durasi_pinjam }} hari</td>
                        <td>{{ $p->due_date ? \Illuminate\Support\Carbon::parse($p->due_date)->format('d/m/Y') : '-' }}</td>
                        <td class="aksi">
                            <form method="POST" action="{{ route('admin.peminjaman.setujui', $p->loan_id) }}" style="display:inline"
                                  onsubmit="return confirm('Konfirmasi pengajuan peminjaman ini?');">
                                @csrf
                                @method('PUT')
                                @include('admin._bawa-filter', ['bawa' => $bawaFilter])
                                <button type="submit">Konfirmasi</button>
                            </form>
                            <form method="POST" action="{{ route('admin.peminjaman.tolak', $p->loan_id) }}" style="display:inline"
                                  onsubmit="return confirm('Tolak pengajuan ini? Status akan menjadi Gagal.');">
                                @csrf
                                @method('PUT')
                                @include('admin._bawa-filter', ['bawa' => $bawaFilter])
                                <button type="submit" class="bahaya">Tolak</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="kosong-tabel">
                            @if ($filter)
                                Tidak ada pengajuan{{ $bukuDipilih ? ' untuk "' . $bukuDipilih->title . '"' : '' }}{{ $cari !== '' ? ' dari peminjam yang cocok dengan “' . $cari . '”' : '' }}.
                                <a href="{{ route('admin.peminjaman.index') }}">Tampilkan semua</a>
                            @else
                                Tidak ada pengajuan yang menunggu.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin._halaman', ['halaman' => $daftar])
@endsection

@section('scripts')
    <script src="{{ asset('js/filter-daftar.js') }}"></script>
    <script>
        var formFilter = document.querySelector('[data-filter-daftar]');
        if (formFilter) { pasangFilterDaftar(formFilter); }
    </script>
@endsection
