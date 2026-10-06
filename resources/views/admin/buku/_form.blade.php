{{-- form ini dipakai bersama oleh create.blade.php dan edit.blade.php, butuh $buku (null saat tambah) --}}

<div class="isian">
    <label for="title">Judul buku</label>
    <input type="text" id="title" name="title" value="{{ old('title', $buku->title ?? '') }}"
           class="@error('title') salah @enderror" required autofocus>
    @error('title')<p class="pesan-salah">{{ $message }}</p>@enderror
</div>

@php
    // nilai terpilih (input lama saat validasi gagal, atau data buku saat edit)
    $idKategori = (string) old('category_id', $buku->category_id ?? '');

    // penulis terpilih berurutan: id penulis yang sudah ada atau "baru:Nama" untuk penulis baru
    $namaPenulis = $penulis->pluck('author_name', 'author_id');
    $nilaiPenulis = (array) old('penulis', $buku ? $buku->authors->pluck('author_id')->all() : []);
    $penulisAwal = collect($nilaiPenulis)->map(function ($nilai) use ($namaPenulis) {
        $nilai = trim((string) $nilai);
        if (ctype_digit($nilai) && isset($namaPenulis[$nilai])) {
            return ['nilai' => $nilai, 'teks' => $namaPenulis[$nilai]];
        }
        if (str_starts_with($nilai, 'baru:') && trim(substr($nilai, 5)) !== '') {
            return ['nilai' => $nilai, 'teks' => trim(substr($nilai, 5)), 'baru' => true];
        }
        return null;
    })->filter()->values();
    $idPenulisAwal = $penulisAwal->pluck('nilai')->all();
@endphp
<div class="dua-kolom">
    <div class="isian">
        <label for="category_id" id="label_category_id">Kategori</label>

        {{-- pencarian kategori: aktif lewat JavaScript, nilainya disalin ke <select> di bawah --}}
        <div class="pilih-cari" data-pilih-cari data-select="category_id" data-jenis="kategori"
             data-baru-box="kotak_kategori_baru" data-baru-input="new_category_name" hidden>
            <input type="text" class="@error('category_id') salah @enderror" placeholder="Cari kategori..."
                   autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false">
            <ul class="pilih-cari-daftar" role="listbox" aria-labelledby="label_category_id" hidden></ul>
        </div>

        {{-- setiap buku wajib punya kategori: placeholder tidak bisa dipilih --}}
        <select id="category_id" name="category_id" class="@error('category_id') salah @enderror" required onchange="toggleKategoriBaru(this.value)">
            <option value="" disabled {{ $idKategori === '' ? 'selected' : '' }}>- Pilih kategori -</option>
            <option value="new" {{ $idKategori === 'new' ? 'selected' : '' }}>+ Tambah Kategori Baru...</option>
            @foreach ($kategori as $k)
                <option value="{{ $k->category_id }}" {{ $idKategori === (string) $k->category_id ? 'selected' : '' }}>
                    {{ $k->category_name }}
                </option>
            @endforeach
        </select>
        @error('category_id')<p class="pesan-salah">{{ $message }}</p>@enderror
        <p class="pesan-salah" data-pesan-wajib hidden>Kategori wajib dipilih.</p>

        <div id="kotak_kategori_baru" style="margin-top: 10px; display: {{ $idKategori === 'new' ? 'block' : 'none' }};">
            <input type="text" id="new_category_name" name="new_category_name"
                   value="{{ old('new_category_name') }}"
                   placeholder="Tuliskan nama kategori baru..."
                   class="@error('new_category_name') salah @enderror">
            @error('new_category_name')<p class="pesan-salah">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="isian">
        <label for="penulis_cadangan" id="label_penulis">Penulis</label>

        {{-- pilihan penulis (aktif lewat JavaScript): penulis terpilih tampil berurutan, kolom di bawahnya untuk menambah --}}
        <div class="pilih-penulis" data-pilih-penulis data-maks="{{ \App\Http\Controllers\Admin\BukuController::MAKS_PENULIS }}"
             data-awal="{{ $penulisAwal->toJson() }}" hidden>
            <ul class="penulis-terpilih" aria-label="Penulis terpilih"></ul>
            <div class="pilih-cari">
                <input type="text" class="@error('penulis') salah @enderror" placeholder="Cari atau tambah penulis..."
                       autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false"
                       aria-labelledby="label_penulis" aria-describedby="info_penulis">
                <ul class="pilih-cari-daftar" id="daftar_penulis" role="listbox" aria-labelledby="label_penulis" hidden></ul>
            </div>
        </div>

        {{-- tanpa JavaScript: pilih beberapa penulis di daftar + satu nama penulis baru --}}
        <div data-penulis-cadangan>
            <select id="penulis_cadangan" name="penulis[]" multiple size="6" class="@error('penulis') salah @enderror">
                @foreach ($penulis as $p)
                    <option value="{{ $p->author_id }}" {{ in_array((string) $p->author_id, $idPenulisAwal, true) ? 'selected' : '' }}>
                        {{ $p->author_name }}
                    </option>
                @endforeach
            </select>
            <input type="text" name="penulis_baru" value="{{ old('penulis_baru') }}" maxlength="100"
                   placeholder="Atau tulis nama penulis baru..." style="margin-top: 8px;">
        </div>

        <p class="pesan-info" id="info_penulis">Bisa lebih dari satu penulis; urutan tampil sesuai urutan dipilih.</p>
        @error('penulis')<p class="pesan-salah">{{ $message }}</p>@enderror
        <p class="pesan-salah" data-pesan-penulis hidden>Penulis wajib dipilih.</p>
    </div>
</div>

<script>
// cadangan tanpa pencarian: tampilkan kolom nama baru saat "+ Tambah ... Baru" dipilih di <select>
function toggleKategoriBaru(val) {
    const box = document.getElementById('kotak_kategori_baru');
    box.style.display = val === 'new' ? 'block' : 'none';
    if (val === 'new') { document.getElementById('new_category_name').focus(); }
}

// pencarian kategori: daftar dibuat dari <select>, termasuk opsi "+ Tambah ... baru"
document.querySelectorAll('[data-pilih-cari]').forEach(function (wadah) {
    var select = document.getElementById(wadah.dataset.select);
    var input = wadah.querySelector('input');
    var daftar = wadah.querySelector('[role="listbox"]');
    var kotakBaru = document.getElementById(wadah.dataset.baruBox);
    var inputBaru = document.getElementById(wadah.dataset.baruInput);
    var pesan = wadah.parentNode.querySelector('[data-pesan-wajib]');
    var jenis = wadah.dataset.jenis;
    var labelBaru = '+ ' + jenis.charAt(0).toUpperCase() + jenis.slice(1) + ' baru';
    var aktif = -1;
    var terakhir = null;   // pilihan terakhir, dikembalikan bila kolom ditinggalkan tanpa memilih

    daftar.id = 'daftar_' + select.id;
    input.setAttribute('aria-controls', daftar.id);

    // susun opsi dari <select>; placeholder kosong tidak ikut
    var opsi = [];
    Array.prototype.forEach.call(select.options, function (o, i) {
        if (o.value === '' || o.value === 'new') { return; }
        var li = document.createElement('li');
        li.id = daftar.id + '_' + i;
        li.setAttribute('role', 'option');
        li.dataset.nilai = o.value;
        li.dataset.teks = o.textContent.trim();
        li.textContent = li.dataset.teks;
        daftar.appendChild(li);
        opsi.push(li);
    });
    var kosong = document.createElement('li');
    kosong.className = 'pilih-cari-kosong';
    kosong.textContent = 'Tidak ada ' + jenis + ' yang cocok.';
    daftar.appendChild(kosong);
    var opsiBaru = document.createElement('li');
    opsiBaru.id = daftar.id + '_baru';
    opsiBaru.setAttribute('role', 'option');
    opsiBaru.className = 'opsi-baru';
    opsiBaru.dataset.nilai = 'new';
    daftar.appendChild(opsiBaru);

    // tampilkan pencarian, sembunyikan select bawaan (tetap ikut terkirim)
    wadah.hidden = false;
    select.hidden = true;
    select.required = false;
    document.getElementById('label_' + select.id).removeAttribute('for');

    function semuaOpsi() { return opsi.concat([opsiBaru]); }
    function terlihat() { return semuaOpsi().filter(function (o) { return !o.hidden; }); }
    function opsiTerpilih() { return semuaOpsi().filter(function (o) { return o.dataset.nilai === select.value; })[0]; }

    function tandaiAktif(i) {
        var t = terlihat();
        semuaOpsi().forEach(function (o) { o.classList.remove('aktif'); });
        aktif = t.length ? Math.max(0, Math.min(i, t.length - 1)) : -1;
        if (aktif >= 0) {
            t[aktif].classList.add('aktif');
            t[aktif].scrollIntoView({ block: 'nearest' });
            input.setAttribute('aria-activedescendant', t[aktif].id);
        }
    }

    // saring opsi sesuai teks; tampilSemua dipakai saat kolom masih berisi pilihan yang sudah ada
    function saring(tampilSemua) {
        var teks = input.value.trim();
        var kata = tampilSemua ? [] : teks.toLowerCase().split(/\s+/).filter(Boolean);
        var ada = 0, persis = false;
        opsi.forEach(function (o) {
            var nama = o.dataset.teks.toLowerCase();
            var cocok = kata.every(function (k) { return nama.indexOf(k) !== -1; });
            o.hidden = !cocok;
            if (cocok) { ada++; }
            if (nama === teks.toLowerCase()) { persis = true; }
        });
        kosong.hidden = ada > 0;
        // opsi tambah baru ikut membawa teks yang sedang diketik
        opsiBaru.textContent = (!tampilSemua && teks && !persis)
            ? '+ Tambah "' + teks + '" sebagai ' + jenis + ' baru'
            : '+ Tambah ' + jenis + ' baru...';
        tandaiAktif(0);
    }

    function buka() { daftar.hidden = false; input.setAttribute('aria-expanded', 'true'); }
    function tutup() {
        daftar.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
    }

    function tandaiTerpilih(o) {
        semuaOpsi().forEach(function (x) { x.setAttribute('aria-selected', x === o ? 'true' : 'false'); });
    }

    function pilih(o, teksKetik) {
        terakhir = o;
        select.value = o.dataset.nilai;
        tandaiTerpilih(o);
        input.classList.remove('salah');
        pesan.hidden = true;
        tutup();
        if (o === opsiBaru) {
            input.value = labelBaru;
            kotakBaru.style.display = 'block';
            if (teksKetik && teksKetik !== labelBaru && !inputBaru.value) { inputBaru.value = teksKetik; }
            return true;
        }
        input.value = o.dataset.teks;
        kotakBaru.style.display = 'none';
        inputBaru.value = '';   // nama baru yang tertinggal tidak ikut terkirim
        return false;
    }

    function bukaDaftar() {
        if (!daftar.hidden) { return; }
        var t = opsiTerpilih();
        var masihPilihan = t && input.value === (t === opsiBaru ? labelBaru : t.dataset.teks);
        saring(Boolean(masihPilihan));
        if (masihPilihan) { tandaiAktif(terlihat().indexOf(t)); }
        buka();
    }

    // nilai awal (edit buku / input lama setelah validasi gagal)
    var awal = opsiTerpilih();
    if (awal) {
        terakhir = awal;
        tandaiTerpilih(awal);
        input.value = awal === opsiBaru ? labelBaru : awal.dataset.teks;
    }

    input.addEventListener('focus', bukaDaftar);
    input.addEventListener('click', bukaDaftar);
    input.addEventListener('input', function () {
        select.value = '';
        saring(false);
        buka();
    });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); buka(); tandaiAktif(aktif + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); buka(); tandaiAktif(aktif - 1); }
        else if (e.key === 'Enter' && !daftar.hidden) {
            e.preventDefault();
            var t = terlihat();
            if (t[aktif] && pilih(t[aktif], input.value.trim())) { inputBaru.focus(); }
        } else if (e.key === 'Escape') { tutup(); }
    });
    semuaOpsi().forEach(function (o) {
        // mousedown agar terpilih sebelum kolom kehilangan fokus
        o.addEventListener('mousedown', function (e) {
            e.preventDefault();
            if (pilih(o, input.value.trim())) { inputBaru.focus(); }
        });
    });
    input.addEventListener('blur', function () {
        tutup();
        if (select.value) { return; }
        // diketik tapi tidak memilih: kembalikan pilihan terakhir (buku wajib punya kategori/penulis)
        if (terakhir) { pilih(terakhir); } else { input.value = ''; }
    });

    // wajib memilih sebelum menyimpan
    input.form.addEventListener('submit', function (e) {
        if (!select.value) {
            e.preventDefault();
            input.classList.add('salah');
            pesan.hidden = false;
            input.focus();
        }
    });
});

// pilihan penulis (bisa lebih dari satu): penulis terpilih berurutan + kolom cari / tambah penulis baru.
// setiap penulis terpilih membawa <input hidden name="penulis[]"> berisi id penulis atau "baru:Nama"
(function () {
    var wadah = document.querySelector('[data-pilih-penulis]');
    if (!wadah) { return; }
    var cadangan = document.querySelector('[data-penulis-cadangan]');
    var selectCadangan = cadangan.querySelector('select');
    var terpilih = wadah.querySelector('.penulis-terpilih');
    var input = wadah.querySelector('input[role="combobox"]');
    var daftar = wadah.querySelector('[role="listbox"]');
    var pesan = document.querySelector('[data-pesan-penulis]');
    var maks = parseInt(wadah.dataset.maks, 10);
    var placeholder = input.placeholder;
    var aktif = -1;

    // semua penulis yang sudah ada, diambil dari daftar cadangan
    var semuaPenulis = Array.prototype.map.call(selectCadangan.options, function (o) {
        return { nilai: o.value, teks: o.textContent.trim() };
    });
    var dipilih = JSON.parse(wadah.dataset.awal || '[]');

    // aktifkan tampilan JavaScript; daftar cadangan dimatikan agar tidak ikut terkirim
    wadah.hidden = false;
    cadangan.hidden = true;
    cadangan.querySelectorAll('select, input').forEach(function (el) { el.disabled = true; });
    document.getElementById('label_penulis').removeAttribute('for');
    input.setAttribute('aria-controls', daftar.id);

    function sama(a, b) { return a.toLowerCase() === b.toLowerCase(); }
    function sudahDipilih(p) {
        return dipilih.some(function (d) { return d.nilai === p.nilai || sama(d.teks, p.teks); });
    }

    function gambarTerpilih() {
        terpilih.innerHTML = '';
        dipilih.forEach(function (p, i) {
            var li = document.createElement('li');
            li.className = 'penulis-chip' + (p.baru ? ' baru' : '');

            var nama = document.createElement('span');
            nama.textContent = (dipilih.length > 1 ? (i + 1) + '. ' : '') + p.teks + (p.baru ? ' (baru)' : '');
            li.appendChild(nama);

            var nilai = document.createElement('input');
            nilai.type = 'hidden';
            nilai.name = 'penulis[]';
            nilai.value = p.nilai;
            li.appendChild(nilai);

            var hapus = document.createElement('button');
            hapus.type = 'button';
            hapus.className = 'penulis-chip-hapus';
            hapus.setAttribute('aria-label', 'Hapus penulis ' + p.teks);
            hapus.textContent = '×';
            hapus.addEventListener('click', function () {
                dipilih.splice(i, 1);
                gambarTerpilih();
                input.focus();
            });
            li.appendChild(hapus);

            terpilih.appendChild(li);
        });
        terpilih.hidden = dipilih.length === 0;

        var penuh = dipilih.length >= maks;
        input.disabled = penuh;
        input.placeholder = penuh ? 'Maksimal ' + maks + ' penulis' : (dipilih.length ? 'Tambah penulis lain...' : placeholder);
    }

    // opsi daftar: penulis yang cocok & belum dipilih, lalu opsi tambah penulis baru dari teks yang diketik
    function opsiDaftar() {
        var teks = input.value.trim();
        var kata = teks.toLowerCase().split(/\s+/).filter(Boolean);
        var opsi = semuaPenulis.filter(function (p) {
            var nama = p.teks.toLowerCase();
            return !sudahDipilih(p) && kata.every(function (k) { return nama.indexOf(k) !== -1; });
        });
        var adaPersis = semuaPenulis.some(function (p) { return sama(p.teks, teks); })
            || dipilih.some(function (p) { return sama(p.teks, teks); });
        if (teks && !adaPersis) {
            opsi.push({ nilai: 'baru:' + teks, teks: teks, baru: true });
        }
        return opsi;
    }

    function gambarDaftar() {
        var opsi = opsiDaftar();
        daftar.innerHTML = '';
        opsi.forEach(function (p, i) {
            var li = document.createElement('li');
            li.id = 'opsi_penulis_' + i;
            li.setAttribute('role', 'option');
            li.textContent = p.baru ? '+ Tambah "' + p.teks + '" sebagai penulis baru' : p.teks;
            if (p.baru) { li.className = 'opsi-baru'; }
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();   // terpilih sebelum kolom kehilangan fokus
                pilih(p);
            });
            daftar.appendChild(li);
        });
        if (opsi.length === 0) {
            var kosong = document.createElement('li');
            kosong.className = 'pilih-cari-kosong';
            kosong.textContent = input.value.trim() ? 'Penulis ini sudah dipilih.' : 'Semua penulis sudah dipilih. Ketik nama untuk menambah penulis baru.';
            daftar.appendChild(kosong);
        }
        tandaiAktif(0);
        return opsi;
    }

    function tandaiAktif(i) {
        var opsi = daftar.querySelectorAll('[role="option"]');
        Array.prototype.forEach.call(opsi, function (o) { o.classList.remove('aktif'); });
        aktif = opsi.length ? Math.max(0, Math.min(i, opsi.length - 1)) : -1;
        if (aktif >= 0) {
            opsi[aktif].classList.add('aktif');
            opsi[aktif].scrollIntoView({ block: 'nearest' });
            input.setAttribute('aria-activedescendant', opsi[aktif].id);
        } else {
            input.removeAttribute('aria-activedescendant');
        }
    }

    function buka() { gambarDaftar(); daftar.hidden = false; input.setAttribute('aria-expanded', 'true'); }
    function tutup() {
        daftar.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
    }

    function pilih(p) {
        if (dipilih.length >= maks || sudahDipilih(p)) { return; }
        dipilih.push(p);
        input.value = '';
        input.classList.remove('salah');
        pesan.hidden = true;
        gambarTerpilih();
        if (input.disabled) { tutup(); } else { buka(); }
    }

    input.addEventListener('focus', buka);
    input.addEventListener('click', buka);
    input.addEventListener('input', buka);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); if (daftar.hidden) { buka(); } else { tandaiAktif(aktif + 1); } }
        else if (e.key === 'ArrowUp') { e.preventDefault(); tandaiAktif(aktif - 1); }
        else if (e.key === 'Enter') {
            // Enter tidak mengirim form: memilih opsi yang sedang disorot
            e.preventDefault();
            var opsi = opsiDaftar();
            if (!daftar.hidden && opsi[aktif]) { pilih(opsi[aktif]); }
        } else if (e.key === 'Escape') { tutup(); }
        else if (e.key === 'Backspace' && input.value === '' && dipilih.length) {
            // hapus penulis terakhir
            dipilih.pop();
            gambarTerpilih();
            buka();
        }
    });
    input.addEventListener('blur', tutup);

    input.form.addEventListener('submit', function (e) {
        // nama yang sudah diketik tapi belum dipilih tetap ikut tersimpan (penulis lama bila namanya sama persis)
        var teks = input.value.trim();
        if (teks && !input.disabled) {
            var ada = semuaPenulis.filter(function (p) { return sama(p.teks, teks); })[0];
            pilih(ada || { nilai: 'baru:' + teks, teks: teks, baru: true });
            tutup();
        }

        // buku wajib punya minimal satu penulis
        if (dipilih.length === 0) {
            e.preventDefault();
            input.classList.add('salah');
            pesan.hidden = false;
            input.focus();
        }
    });

    gambarTerpilih();
})();
</script>

<div class="dua-kolom">
    <div class="isian">
        <label for="publisher">Penerbit</label>
        <input type="text" id="publisher" name="publisher" value="{{ old('publisher', $buku->publisher ?? '') }}"
               class="@error('publisher') salah @enderror">
        @error('publisher')<p class="pesan-salah">{{ $message }}</p>@enderror
    </div>
    <div class="isian">
        <label for="publication_year">Tahun terbit</label>
        <input type="number" id="publication_year" name="publication_year"
               value="{{ old('publication_year', $buku->publication_year ?? '') }}"
               class="@error('publication_year') salah @enderror">
        @error('publication_year')<p class="pesan-salah">{{ $message }}</p>@enderror
    </div>
</div>

<div class="dua-kolom">
    <div class="isian">
        <label for="location">Lokasi rak</label>
        <input type="text" id="location" name="location" value="{{ old('location', $buku->location ?? '') }}"
               placeholder="Contoh: Rak A-3" class="@error('location') salah @enderror">
        @error('location')<p class="pesan-salah">{{ $message }}</p>@enderror
    </div>
    <div class="isian">
        <label for="stock">Jumlah stok</label>
        {{-- saat edit, stok tidak boleh kurang dari eksemplar yang sedang dipinjam / menunggu diambil --}}
        @php $sedangDipinjam = $buku ? max(0, $buku->stock - $buku->available_stock) : 0; @endphp
        <input type="number" id="stock" name="stock" min="{{ $sedangDipinjam }}"
               value="{{ old('stock', $buku->stock ?? 1) }}"
               class="@error('stock') salah @enderror" required>
        @error('stock')<p class="pesan-salah">{{ $message }}</p>@enderror
        @if ($buku)
            <p class="pesan-info">
                Sedang dipinjam / menunggu diambil: {{ $sedangDipinjam }} eksemplar{{ $sedangDipinjam > 0 ? ', jadi stok minimal ' . $sedangDipinjam : '' }}.
                Stok tersedia menyesuaikan otomatis.
            </p>
        @endif
    </div>
</div>

<div class="isian">
    <label for="cover_image">Cover buku</label>
    <input type="file" id="cover_image" name="cover_image" accept="image/png,image/jpeg"
           class="@error('cover_image') salah @enderror">
    @error('cover_image')<p class="pesan-salah">{{ $message }}</p>@enderror
    <p class="pesan-info">Format JPG/PNG, maksimal 2MB.</p>

    @if ($buku && $buku->cover_url)
        <div style="margin-top:10px;">
            <img src="{{ $buku->cover_url }}" alt="Cover saat ini"
                 style="width:100px;height:auto;display:block;border-radius:8px;border:1px solid var(--garis);">
            <p class="pesan-info">Cover saat ini. Upload file baru untuk menggantinya.</p>
        </div>
    @endif
</div>

<div class="isian">
    <label for="description">Deskripsi / sinopsis</label>
    <textarea id="description" name="description" rows="4"
              class="@error('description') salah @enderror">{{ old('description', $buku->description ?? '') }}</textarea>
    @error('description')<p class="pesan-salah">{{ $message }}</p>@enderror
</div>
