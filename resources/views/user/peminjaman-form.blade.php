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

            @php $idBukuDipilih = (string) old('book_id', $bukuDipilih); @endphp
            <div class="isian">
                <label for="book_id" id="label_buku">Nama buku</label>

                {{-- pencarian buku: aktif lewat JavaScript, isinya mengisi <select> di bawah yang dikirim ke server --}}
                <div class="pilih-buku" data-pilih-buku hidden>
                    <input type="text" id="cari_buku" class="@error('book_id') salah @enderror"
                           placeholder="Cari judul buku atau nama penulis..." autocomplete="off"
                           role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="daftar_buku">
                    <ul id="daftar_buku" class="pilih-buku-daftar" role="listbox" aria-labelledby="label_buku" hidden>
                        @foreach ($daftarBuku as $b)
                            <li id="opsi-buku-{{ $b->book_id }}" role="option" aria-selected="false"
                                data-id="{{ $b->book_id }}" data-judul="{{ $b->title }}"
                                data-cari="{{ Str::lower($b->title . ' ' . ($b->author->author_name ?? '')) }}">
                                <span class="opsi-judul">{{ $b->title }}</span>
                                <span class="opsi-meta">{{ $b->author->author_name ?? 'Penulis belum diisi' }} · tersedia {{ $b->available_stock }}</span>
                            </li>
                        @endforeach
                        <li class="pilih-buku-kosong" hidden>Tidak ada buku yang cocok dengan pencarianmu.</li>
                    </ul>
                </div>

                <select id="book_id" name="book_id" class="@error('book_id') salah @enderror" required>
                    <option value="">Pilih buku</option>
                    @foreach ($daftarBuku as $b)
                        <option value="{{ $b->book_id }}" {{ $idBukuDipilih === (string) $b->book_id ? 'selected' : '' }}>
                            {{ $b->title }} (tersedia {{ $b->available_stock }})
                        </option>
                    @endforeach
                </select>
                <p class="pesan-salah" id="pesan_buku" hidden>Pilih buku dari hasil pencarian.</p>
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

    <script>
        // pencarian buku: ketik judul/penulis, pilih dari daftar; nilainya disalin ke <select name="book_id">
        (function () {
            var wadah = document.querySelector('[data-pilih-buku]');
            var select = document.getElementById('book_id');
            if (!wadah || !select) { return; }

            var input = document.getElementById('cari_buku');
            var daftar = document.getElementById('daftar_buku');
            var opsi = Array.prototype.slice.call(daftar.querySelectorAll('[role="option"]'));
            var kosong = daftar.querySelector('.pilih-buku-kosong');
            var pesan = document.getElementById('pesan_buku');
            var aktif = -1;
            var terakhir = null;   // buku terakhir yang dipilih, dikembalikan bila kolom ditinggalkan tanpa memilih

            // tampilkan pencarian, sembunyikan select bawaan (tetap ikut terkirim)
            wadah.hidden = false;
            select.hidden = true;
            select.required = false;
            document.getElementById('label_buku').htmlFor = 'cari_buku';

            function opsiTerlihat() {
                return opsi.filter(function (o) { return !o.hidden; });
            }

            function tandaiAktif(i) {
                var terlihat = opsiTerlihat();
                opsi.forEach(function (o) { o.classList.remove('aktif'); });
                aktif = terlihat.length ? Math.max(0, Math.min(i, terlihat.length - 1)) : -1;
                if (aktif >= 0) {
                    terlihat[aktif].classList.add('aktif');
                    terlihat[aktif].scrollIntoView({ block: 'nearest' });
                    input.setAttribute('aria-activedescendant', terlihat[aktif].id);
                } else {
                    input.removeAttribute('aria-activedescendant');
                }
            }

            function saring() {
                var kata = input.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
                var ada = 0;
                opsi.forEach(function (o) {
                    var cocok = kata.every(function (k) { return o.dataset.cari.indexOf(k) !== -1; });
                    o.hidden = !cocok;
                    if (cocok) { ada++; }
                });
                kosong.hidden = ada > 0;
                tandaiAktif(0);
            }

            function buka() {
                daftar.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            }

            function tutup() {
                daftar.hidden = true;
                input.setAttribute('aria-expanded', 'false');
                input.removeAttribute('aria-activedescendant');
            }

            function pilih(o) {
                terakhir = o;
                select.value = o.dataset.id;
                input.value = o.dataset.judul;
                opsi.forEach(function (x) { x.setAttribute('aria-selected', x === o ? 'true' : 'false'); });
                input.classList.remove('salah');
                pesan.hidden = true;
                tutup();
            }

            // buku yang sudah terpilih (dari tombol "Pinjam Buku" atau input lama) langsung tampil
            var awal = opsi.filter(function (o) { return o.dataset.id === select.value; })[0];
            if (awal) { pilih(awal); }

            function bukaDaftar() {
                if (!daftar.hidden) { return; }
                // kolom masih berisi judul buku terpilih: tampilkan semua buku agar mudah mengganti pilihan
                var terpilih = opsi.filter(function (o) { return o.dataset.id === select.value; })[0];
                if (terpilih && input.value === terpilih.dataset.judul) {
                    opsi.forEach(function (o) { o.hidden = false; });
                    kosong.hidden = true;
                    tandaiAktif(opsi.indexOf(terpilih));
                } else {
                    saring();
                }
                buka();
            }
            input.addEventListener('focus', bukaDaftar);
            input.addEventListener('click', bukaDaftar);
            input.addEventListener('input', function () {
                select.value = '';   // teks diubah: pilihan lama dibatalkan sampai buku dipilih lagi
                opsi.forEach(function (o) { o.setAttribute('aria-selected', 'false'); });
                saring();
                buka();
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowDown') { e.preventDefault(); buka(); tandaiAktif(aktif + 1); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); buka(); tandaiAktif(aktif - 1); }
                else if (e.key === 'Enter' && !daftar.hidden) {
                    e.preventDefault();
                    var terlihat = opsiTerlihat();
                    if (terlihat[aktif]) { pilih(terlihat[aktif]); }
                } else if (e.key === 'Escape') { tutup(); }
            });
            opsi.forEach(function (o) {
                // mousedown agar terpilih sebelum input kehilangan fokus
                o.addEventListener('mousedown', function (e) { e.preventDefault(); pilih(o); });
            });
            input.addEventListener('blur', function () {
                tutup();
                if (select.value) { return; }
                if (input.value.trim() === '') {
                    // kolom dikosongkan: pilihan memang dihapus
                    terakhir = null;
                } else if (terakhir) {
                    // diketik tapi tidak memilih apa pun: kembalikan buku yang terakhir dipilih
                    pilih(terakhir);
                }
            });

            // wajib memilih buku dari daftar sebelum mengirim
            input.form.addEventListener('submit', function (e) {
                if (!select.value) {
                    e.preventDefault();
                    input.classList.add('salah');
                    pesan.hidden = false;
                    input.focus();
                }
            });
        })();
    </script>
@endsection
