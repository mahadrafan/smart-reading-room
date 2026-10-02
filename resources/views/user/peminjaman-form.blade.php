@extends('layouts.user')

@section('title', 'Form Peminjaman')

@section('content')
    <div class="kepala">
        <h1>Form Peminjaman</h1>
        <p class="ket">Isi data di bawah, lalu kirim. Permintaan akan diperiksa admin.</p>
    </div>

    <div class="kotak kotak-form">
        <form method="POST" action="{{ route('peminjaman.simpan') }}">
            @csrf

            {{-- tanggal pengajuan selalu hari ini dan tidak bisa diubah (tidak ikut dikirim ke server) --}}
            <div class="isian">
                <label for="tanggal_pengajuan">Tanggal pengajuan</label>
                <input type="text" id="tanggal_pengajuan" value="{{ today()->format('d/m/Y') }}"
                       data-tanggal="{{ today()->format('Y-m-d') }}" readonly tabindex="-1" aria-readonly="true">
            </div>

            <div class="isian">
                <label for="book_id">Nama buku</label>
                <select id="book_id" name="book_id" class="@error('book_id') salah @enderror" required>
                    <option value="">Pilih buku</option>
                    @foreach ($daftarBuku as $b)
                        <option value="{{ $b->book_id }}"
                            {{ old('book_id', $bukuDipilih) == $b->book_id ? 'selected' : '' }}>
                            {{ $b->title }} (tersedia {{ $b->available_stock }})
                        </option>
                    @endforeach
                </select>
                @error('book_id')
                    <p class="pesan-salah">{{ $message }}</p>
                @enderror
                @if ($daftarBuku->count() == 0)
                    <p class="pesan-info">Belum ada buku yang stoknya tersedia.</p>
                @endif
            </div>

            <div class="dua-kolom">
                <div class="isian">
                    <label>Nama peminjam</label>
                    <input type="text" value="{{ Auth::user()->name }}" readonly>
                </div>
                <div class="isian">
                    <label>Kelas</label>
                    <input type="text" value="{{ Auth::user()->class }}" readonly>
                </div>
            </div>

            <div class="dua-kolom">
                <div class="isian">
                    <label>No. telepon</label>
                    <input type="text" value="{{ Auth::user()->phone }}" readonly>
                </div>
                <div class="isian">
                    <label>Email</label>
                    <input type="text" value="{{ Auth::user()->email }}" readonly>
                </div>
            </div>
            <p class="pesan-info">Data peminjam diambil dari akunmu. Ubah lewat menu Profil kalau ada yang salah.</p>

            <div class="isian">
                <span class="label-grup">Durasi peminjaman</span>
                <div class="pilihan-durasi">
                    @foreach (['1' => '1 hari', '3' => '3 hari', '5' => '5 hari', '7' => '7 hari', 'custom' => 'Custom'] as $nilai => $teks)
                        <label class="pilihan">
                            <input type="radio" name="durasi" value="{{ $nilai }}"
                                   {{ old('durasi', '7') == $nilai ? 'checked' : '' }}>
                            {{ $teks }}
                        </label>
                    @endforeach
                </div>

                <div id="kotak-custom" class="isian-custom">
                    <label for="durasi_custom">Jumlah hari (1 sampai 30)</label>
                    <input type="number" id="durasi_custom" name="durasi_custom" min="1" max="30"
                           value="{{ old('durasi_custom') }}"
                           class="@error('durasi_custom') salah @enderror">
                </div>
                @error('durasi')
                    <p class="pesan-salah">{{ $message }}</p>
                @enderror
                @error('durasi_custom')
                    <p class="pesan-salah">{{ $message }}</p>
                @enderror

                <p class="pesan-info" id="batas-kembali"></p>
                <p class="pesan-info">Tanggal pinjam dan batas pengembalian dihitung dari hari buku diambil di perpustakaan.</p>
            </div>

            <button type="submit" class="tombol tombol-penuh">Kirim Permintaan</button>
        </form>
    </div>

    <script>
        // menampilkan perkiraan batas pengembalian dan kolom durasi custom
        function hitungBatas() {
            var tanggal = document.getElementById('tanggal_pengajuan').dataset.tanggal;
            var pilih = document.querySelector('input[name="durasi"]:checked');
            var kotakCustom = document.getElementById('kotak-custom');
            var teks = document.getElementById('batas-kembali');

            if (!pilih) { return; }

            var lama;
            if (pilih.value === 'custom') {
                kotakCustom.style.display = 'block';
                lama = parseInt(document.getElementById('durasi_custom').value);
            } else {
                kotakCustom.style.display = 'none';
                lama = parseInt(pilih.value);
            }

            if (!tanggal || isNaN(lama)) {
                teks.textContent = '';
                return;
            }

            var d = new Date(tanggal + 'T00:00:00');
            d.setDate(d.getDate() + lama);
            var hari = ('0' + d.getDate()).slice(-2);
            var bulan = ('0' + (d.getMonth() + 1)).slice(-2);
            teks.textContent = 'Perkiraan batas pengembalian (jika diambil hari ini): ' + hari + '/' + bulan + '/' + d.getFullYear();
        }

        document.getElementById('durasi_custom').addEventListener('input', hitungBatas);
        var radioDurasi = document.querySelectorAll('input[name="durasi"]');
        for (var i = 0; i < radioDurasi.length; i++) {
            radioDurasi[i].addEventListener('change', hitungBatas);
        }
        hitungBatas();
    </script>
@endsection
