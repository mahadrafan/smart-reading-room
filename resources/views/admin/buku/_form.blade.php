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
    $idPenulis  = (string) old('author_id', $buku->author_id ?? '');
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
        <label for="author_id" id="label_author_id">Penulis</label>

        {{-- pencarian penulis: aktif lewat JavaScript, nilainya disalin ke <select> di bawah --}}
        <div class="pilih-cari" data-pilih-cari data-select="author_id" data-jenis="penulis"
             data-baru-box="kotak_penulis_baru" data-baru-input="new_author_name" hidden>
            <input type="text" class="@error('author_id') salah @enderror" placeholder="Cari penulis..."
                   autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false">
            <ul class="pilih-cari-daftar" role="listbox" aria-labelledby="label_author_id" hidden></ul>
        </div>

        {{-- setiap buku wajib punya penulis: placeholder tidak bisa dipilih --}}
        <select id="author_id" name="author_id" class="@error('author_id') salah @enderror" required onchange="togglePenulisBaru(this.value)">
            <option value="" disabled {{ $idPenulis === '' ? 'selected' : '' }}>- Pilih penulis -</option>
            <option value="new" {{ $idPenulis === 'new' ? 'selected' : '' }}>+ Tambah Penulis Baru...</option>
            @foreach ($penulis as $p)
                <option value="{{ $p->author_id }}" {{ $idPenulis === (string) $p->author_id ? 'selected' : '' }}>
                    {{ $p->author_name }}
                </option>
            @endforeach
        </select>
        @error('author_id')<p class="pesan-salah">{{ $message }}</p>@enderror
        <p class="pesan-salah" data-pesan-wajib hidden>Penulis wajib dipilih.</p>

        <div id="kotak_penulis_baru" style="margin-top: 10px; display: {{ $idPenulis === 'new' ? 'block' : 'none' }};">
            <input type="text" id="new_author_name" name="new_author_name"
                   value="{{ old('new_author_name') }}"
                   placeholder="Tuliskan nama penulis baru..."
                   class="@error('new_author_name') salah @enderror">
            @error('new_author_name')<p class="pesan-salah">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<script>
// cadangan tanpa pencarian: tampilkan kolom nama baru saat "+ Tambah ... Baru" dipilih di <select>
function toggleKategoriBaru(val) {
    const box = document.getElementById('kotak_kategori_baru');
    box.style.display = val === 'new' ? 'block' : 'none';
    if (val === 'new') { document.getElementById('new_category_name').focus(); }
}

function togglePenulisBaru(val) {
    const box = document.getElementById('kotak_penulis_baru');
    box.style.display = val === 'new' ? 'block' : 'none';
    if (val === 'new') { document.getElementById('new_author_name').focus(); }
}

// pencarian kategori & penulis: daftar dibuat dari <select>, termasuk opsi "+ Tambah ... baru"
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
        <input type="number" id="stock" name="stock" min="0"
               value="{{ old('stock', $buku->stock ?? 1) }}"
               class="@error('stock') salah @enderror" required>
        @error('stock')<p class="pesan-salah">{{ $message }}</p>@enderror
        @if ($buku)
            <p class="pesan-info">Sedang dipinjam: {{ $buku->stock - $buku->available_stock }} eksemplar. Stok tersedia menyesuaikan otomatis.</p>
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
