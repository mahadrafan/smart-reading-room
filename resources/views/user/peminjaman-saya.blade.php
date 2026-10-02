@extends('layouts.user')

@section('title', 'Riwayat Peminjaman')

@section('content')
    {{-- kepala halaman --}}
    <div class="kepala kepala-baris">
        <div>
            <h1>Riwayat Peminjaman</h1>
            <p class="ket">Pantau status pengajuan dan buku yang sedang kamu pinjam.</p>
        </div>
        <a href="{{ route('peminjaman.buat') }}" class="tombol">Ajukan Peminjaman</a>
    </div>

    {{-- stat cards: dihitung dari semua peminjaman user, tidak terpengaruh tab filter --}}
    @php
        $aktif        = $semua->whereIn('status', ['Dipinjam'])->count();
        $menunggu     = $semua->whereIn('status', ['Menunggu'])->count();
        // sudah dikonfirmasi admin & siap diambil; berkurang saat admin mengubah status jadi Dipinjam
        $siapDiambil  = $semua->whereIn('status', ['Dikonfirmasi'])->count();
        $selesai      = $semua->whereIn('status', ['Dikembalikan'])->count();
        // cari yang paling dekat batas kembali (status Dipinjam)
        $dipinjamItems = $semua->where('status', 'Dipinjam');
        $hariTersisa = null;
        $statusAman  = true;
        foreach ($dipinjamItems as $p) {
            if ($p->due_date) {
                $sisa = now()->startOfDay()->diffInDays($p->due_date->startOfDay(), false);
                if ($hariTersisa === null || $sisa < $hariTersisa) {
                    $hariTersisa = $sisa;
                    if ($sisa < 0) $statusAman = false;
                }
            }
        }
    @endphp
    <div class="ringkasan">
        <div class="kartu-ringkas">
            <div class="kartu-ringkas-kiri">
                <div class="label-kecil">Sedang Dipinjam</div>
                <div class="angka">{{ $aktif }}</div>
                <div class="sub-label">Buku Aktif</div>
            </div>
            <div class="kartu-ringkas-ikon ikon-biru">📖</div>
        </div>
        <div class="kartu-ringkas">
            <div class="kartu-ringkas-kiri">
                <div class="label-kecil">Menunggu</div>
                <div class="angka">{{ $menunggu }}</div>
                <div class="sub-label">Pengajuan</div>
            </div>
            <div class="kartu-ringkas-ikon ikon-oranye">📋</div>
        </div>
        <div class="kartu-ringkas">
            <div class="kartu-ringkas-kiri">
                <div class="label-kecil">Siap Diambil</div>
                <div class="angka">{{ $siapDiambil }}</div>
                <div class="sub-label">Dikonfirmasi</div>
            </div>
            <div class="kartu-ringkas-ikon ikon-ungu">📦</div>
        </div>
        <div class="kartu-ringkas">
            <div class="kartu-ringkas-kiri">
                <div class="label-kecil">Selesai Dibaca</div>
                <div class="angka">{{ $selesai }}</div>
                <div class="sub-label">Buku</div>
            </div>
            <div class="kartu-ringkas-ikon ikon-hijau">✅</div>
        </div>
        <div class="kartu-ringkas">
            <div class="kartu-ringkas-kiri">
                <div class="label-kecil">Batas Kembali</div>
                <div class="angka">{{ $hariTersisa !== null ? ($hariTersisa < 0 ? abs($hariTersisa) : $hariTersisa) : 0 }}</div>
                @if ($hariTersisa !== null && $hariTersisa < 0)
                    <div class="sub-label peringatan">Terlambat {{ abs($hariTersisa) }} Hari</div>
                @elseif ($hariTersisa !== null)
                    <div class="sub-label aman">Aman</div>
                @else
                    <div class="sub-label aman">Aman</div>
                @endif
            </div>
            <div class="kartu-ringkas-ikon {{ (!$statusAman) ? 'ikon-merah' : 'ikon-merah' }}">📅</div>
        </div>
    </div>

    {{-- filter tabs & pencarian --}}
    <div class="filter-baris">
        <div class="chip-baris">
            <a href="{{ route('peminjaman.index') }}"
               class="chip {{ !$filter ? 'aktif' : '' }}">
               Semua <strong>{{ $semua->count() }}</strong>
            </a>
            @foreach (['aktif' => 'Aktif', 'menunggu' => 'Menunggu', 'selesai' => 'Selesai', 'terlambat' => 'Terlambat', 'gagal' => 'Gagal'] as $kunci => $teks)
                <a href="{{ route('peminjaman.index', ['filter' => $kunci]) }}"
                   class="chip {{ $filter == $kunci ? 'aktif' : '' }}">
                   {{ $teks }} <strong>{{ $jumlahFilter[$kunci] }}</strong>
                </a>
            @endforeach
        </div>
        <div class="cari-periode">
            <input type="search" class="cari-input" placeholder="🔍  Cari judul atau ID..." id="cariTabel">

            {{-- tombol periode dengan dropdown --}}
            <div class="periode-wrap" id="periodeWrap">
                <button class="tombol-outline" id="btnPeriode" type="button">
                    📅 <span id="labelPeriode">Periode</span>
                </button>
                <div class="periode-dropdown" id="periodeDropdown">
                    <div class="periode-dropdown-judul">Filter Periode</div>
                    <div class="periode-baris">
                        <div class="periode-grup">
                            <label>Dari tanggal</label>
                            <input type="date" id="periodeFrom" class="periode-input">
                        </div>
                        <div class="periode-grup">
                            <label>Sampai tanggal</label>
                            <input type="date" id="periodeTo" class="periode-input">
                        </div>
                    </div>
                    <div class="periode-aksi">
                        <button type="button" class="periode-reset" id="periodeReset">Reset</button>
                        <button type="button" class="periode-apply" id="periodeApply">Terapkan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- tabel --}}
    <div class="kotak kotak-overflow">
        <table class="tabel">
            <thead>
                <tr>
                    <th>No. Peminjaman</th>
                    <th>Judul Buku</th>
                    <th>Diajukan</th>
                    <th>Tanggal Pinjam</th>
                    <th>Batas Kembali</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $p)
                    <tr>
                        <td>
                            <span style="font-size:12px;font-weight:700;color:var(--abu)">{{ $p->kode }}</span>
                        </td>
                        <td>
                            <div class="tabel-buku">
                                {{-- sampul buku: gambar cover kalau filenya ada, fallback ke inisial judul --}}
                                @if ($p->book && $p->book->cover_url)
                                    <img src="{{ $p->book->cover_url }}" alt="Sampul {{ $p->book->title }}"
                                         class="tabel-sampul tabel-sampul-gambar" loading="lazy">
                                @else
                                    @php
                                        $daftarWarna = ['#8671c9', '#1b2340', '#2f6f73', '#b5574b', '#5b7b3a', '#a07a2c'];
                                        $kata = explode(' ', trim($p->book->title ?? '-'));
                                        $inisial = mb_strtoupper(mb_substr($kata[0], 0, 1) . (isset($kata[1]) ? mb_substr($kata[1], 0, 1) : ''));
                                    @endphp
                                    <div class="tabel-sampul" style="background: {{ $daftarWarna[($p->book->category_id ?? 0) % count($daftarWarna)] }};">{{ $inisial }}</div>
                                @endif
                                <div class="tabel-buku-info">
                                    <div class="judul">{{ $p->book ? $p->book->title : '-' }}</div>
                                    <div class="meta">
                                        {{ $p->book?->author?->author_name ?? '-' }}
                                        @if($p->book?->category)
                                            · {{ $p->book->category->category_name }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px">
                                {{ $p->request_date ? $p->request_date->format('d M Y') : '-' }}
                            </span>
                        </td>
                        <td>
                            <span style="font-size:13px">
                                {{ $p->tanggal_pinjam ? $p->tanggal_pinjam->format('d M Y') : '-' }}
                            </span>
                        </td>
                        <td>
                            @if ($p->due_date)
                                @php
                                    $sisaHari = now()->startOfDay()->diffInDays($p->due_date->startOfDay(), false);
                                @endphp
                                <div class="batas-kembali">
                                    {{ $p->due_date->format('d M Y') }}
                                    @if ($p->status == 'Dipinjam')
                                        @if ($sisaHari < 0)
                                            <div><span class="sisa-hari terlambat">Terlambat {{ abs($sisaHari) }} Hari</span></div>
                                        @else
                                            <div><span class="sisa-hari">Sisa {{ $sisaHari }} Hari</span></div>
                                        @endif
                                    @elseif ($p->status == 'Dikembalikan')
                                        <div class="tepat-waktu">{{ $p->hari_terlambat > 0 ? 'Terlambat '.$p->hari_terlambat.' hari' : 'Tepat waktu' }}</div>
                                    @elseif (in_array($p->status, ['Menunggu', 'Dikonfirmasi']))
                                        {{-- batas kembali masih perkiraan, digeser saat buku diambil --}}
                                        <div class="tepat-waktu">Perkiraan</div>
                                    @endif
                                </div>
                            @else
                                <span style="color:var(--abu)">-</span>
                            @endif
                        </td>
                        <td>
                            @include('user._status', ['pinjam' => $p])
                            @if ($p->hari_terlambat > 0)
                                <div class="pesan-salah" style="margin-top:4px">
                                    Denda Rp{{ number_format($p->jumlah_denda, 0, ',', '.') }}
                                </div>
                                <span class="badge {{ $p->denda_lunas ? 'b-setuju' : 'b-tolak' }}" style="margin-top:4px">
                                    <span class="badge-dot"></span>
                                    {{ $p->denda_lunas ? 'Lunas' : 'Belum Lunas' }}
                                </span>
                            @endif
                        </td>
                        <td class="aksi">
                            <a href="{{ route('peminjaman.detail', $p->loan_id) }}" class="btn-tabel btn-tabel-abu">Detail</a>
                            @if ($p->status == 'Menunggu')
                                <form method="POST" action="{{ route('peminjaman.batal', $p->loan_id) }}"
                                      style="display:inline" onsubmit="return confirm('Batalkan pengajuan ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-tabel" style="color:var(--merah);background:#fdecee;margin-left:6px">Batalkan</button>
                                </form>
                            @endif
                            @if ($p->status == 'Dikembalikan')
                                <a href="{{ route('buku.detail', $p->book_id) }}" class="btn-tabel btn-tabel-abu" style="margin-left:6px">Beri Ulasan</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="kosong-tabel">
                            @if ($filter)
                                Tidak ada peminjaman di filter ini.
                                <a href="{{ route('peminjaman.index') }}">Lihat semua peminjaman</a>
                            @else
                                Belum ada data peminjaman.
                                <a href="{{ route('dashboard') }}">Cari buku di katalog</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="tabel-footer">
            <span>Menampilkan {{ $daftar->count() }} data peminjaman</span>
            <div class="halaman">
                <span class="aktif-hal">1</span>
            </div>
        </div>
    </div>

    </div>

@endsection

@section('scripts')
<script>
    /* ---------- filter teks (cari judul/ID) ---------- */
    const cariInput = document.getElementById('cariTabel');
    if (cariInput) {
        cariInput.addEventListener('input', function() {
            filterRows();
        });
    }

    /* ---------- dropdown periode ---------- */
    const btnPeriode      = document.getElementById('btnPeriode');
    const periodeDropdown = document.getElementById('periodeDropdown');
    const periodeFrom     = document.getElementById('periodeFrom');
    const periodeTo       = document.getElementById('periodeTo');
    const periodeApply    = document.getElementById('periodeApply');
    const periodeReset    = document.getElementById('periodeReset');
    const labelPeriode    = document.getElementById('labelPeriode');

    // toggle buka/tutup dropdown
    btnPeriode.addEventListener('click', function(e) {
        e.stopPropagation();
        const isOpen = periodeDropdown.classList.contains('terbuka');
        periodeDropdown.classList.toggle('terbuka', !isOpen);
        btnPeriode.classList.toggle('aktif-outline', !isOpen);
    });

    // tutup kalau klik di luar dropdown
    document.addEventListener('click', function(e) {
        if (!document.getElementById('periodeWrap').contains(e.target)) {
            periodeDropdown.classList.remove('terbuka');
            btnPeriode.classList.remove('aktif-outline');
        }
    });

    // terapkan filter periode
    periodeApply.addEventListener('click', function() {
        filterRows();
        
        // update label tombol
        const from = periodeFrom.value;
        const to   = periodeTo.value;
        if (from || to) {
            const fmtFrom = from ? formatTanggal(from) : '…';
            const fmtTo   = to   ? formatTanggal(to)   : '…';
            labelPeriode.textContent = fmtFrom + ' - ' + fmtTo;
            btnPeriode.classList.add('aktif-outline');
        }
        
        periodeDropdown.classList.remove('terbuka');
    });

    // reset filter periode
    periodeReset.addEventListener('click', function() {
        periodeFrom.value = '';
        periodeTo.value   = '';
        labelPeriode.textContent = 'Periode';
        btnPeriode.classList.remove('aktif-outline');
        
        filterRows();
        periodeDropdown.classList.remove('terbuka');
    });

    /* ---------- fungsi filter baris tabel ---------- */
    function filterRows() {
        const q    = cariInput ? cariInput.value.toLowerCase() : '';
        const from = periodeFrom ? periodeFrom.value : '';
        const to   = periodeTo   ? periodeTo.value   : '';

        let fromDate = null;
        let toDate = null;
        
        if (from) {
            const p = from.split('-');
            fromDate = new Date(parseInt(p[0]), parseInt(p[1]) - 1, parseInt(p[2]), 0, 0, 0);
        }
        if (to) {
            const p = to.split('-');
            toDate = new Date(parseInt(p[0]), parseInt(p[1]) - 1, parseInt(p[2]), 23, 59, 59);
        }

        document.querySelectorAll('table.tabel tbody tr').forEach(function(row) {
            // abaikan baris pesan kosong
            if (row.querySelector('.kosong-tabel')) return;
            
            const matchTeks = !q || row.textContent.toLowerCase().includes(q);

            // filter tanggal — ambil kolom "Diajukan" (kolom ke-3, index 2)
            let matchPeriode = true;
            if (fromDate || toDate) {
                const selDiajukan = row.querySelectorAll('td')[2];
                if (selDiajukan) {
                    const tglStr = selDiajukan.textContent.trim(); 
                    const tgl    = parseTanggalID(tglStr);
                    
                    if (tgl) {
                        if (fromDate && tgl < fromDate) matchPeriode = false;
                        if (toDate   && tgl > toDate) matchPeriode = false;
                    } else {
                        if (tglStr !== '-') matchPeriode = false;
                    }
                }
            }

            row.style.display = (matchTeks && matchPeriode) ? '' : 'none';
        });
    }

    /* ---------- helper format tanggal ---------- */
    function formatTanggal(isoStr) {
        if (!isoStr) return '';
        const p = isoStr.split('-');
        const d = new Date(parseInt(p[0]), parseInt(p[1]) - 1, parseInt(p[2]));
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    // Mendukung output bulan format Inggris (PHP) maupun Indonesia
    const BULAN = { 
        'jan':0, 'feb':1, 'mar':2, 'apr':3, 'may':4, 'mei':4, 'jun':5, 
        'jul':6, 'aug':7, 'agu':7, 'sep':8, 'oct':9, 'okt':9, 'nov':10, 'dec':11, 'des':11 
    };
    
    function parseTanggalID(str) {
        const parts = str.trim().split(' ');
        if (parts.length !== 3) return null;
        
        const d = parseInt(parts[0]);
        const mStr = parts[1].toLowerCase();
        const m = BULAN[mStr];
        const y = parseInt(parts[2]);
        
        if (isNaN(d) || m === undefined || isNaN(y)) return null;
        return new Date(y, m, d, 12, 0, 0); // set jam 12 siang agar aman dari zona waktu
    }
</script>
@endsection
