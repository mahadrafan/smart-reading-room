{{-- form ini dipakai bersama oleh create.blade.php dan edit.blade.php, butuh $buku (null saat tambah) --}}

<div class="isian">
    <label for="title">Judul buku</label>
    <input type="text" id="title" name="title" value="{{ old('title', $buku->title ?? '') }}"
           class="@error('title') salah @enderror" required autofocus>
    @error('title')<p class="pesan-salah">{{ $message }}</p>@enderror
</div>

<div class="dua-kolom">
    <div class="isian">
        <label for="category_id">Kategori</label>
        <select id="category_id" name="category_id" class="@error('category_id') salah @enderror" onchange="toggleKategoriBaru(this.value)">
            <option value="">- Pilih kategori -</option>
            <option value="new" {{ old('category_id') == 'new' ? 'selected' : '' }}>+ Tambah Kategori Baru...</option>
            @foreach ($kategori as $k)
                <option value="{{ $k->category_id }}"
                    {{ old('category_id', $buku->category_id ?? '') == $k->category_id ? 'selected' : '' }}>
                    {{ $k->category_name }}
                </option>
            @endforeach
        </select>
        @error('category_id')<p class="pesan-salah">{{ $message }}</p>@enderror

        <div id="kotak_kategori_baru" style="margin-top: 10px; display: {{ (old('category_id') == 'new' || old('new_category_name')) ? 'block' : 'none' }};">
            <input type="text" id="new_category_name" name="new_category_name"
                   value="{{ old('new_category_name') }}"
                   placeholder="Tuliskan nama kategori baru..."
                   class="@error('new_category_name') salah @enderror">
            @error('new_category_name')<p class="pesan-salah">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="isian">
        <label for="author_id">Penulis</label>
        <select id="author_id" name="author_id" class="@error('author_id') salah @enderror" onchange="togglePenulisBaru(this.value)">
            <option value="">- Pilih penulis -</option>
            <option value="new" {{ old('author_id') == 'new' ? 'selected' : '' }}>+ Tambah Penulis Baru...</option>
            @foreach ($penulis as $p)
                <option value="{{ $p->author_id }}"
                    {{ old('author_id', $buku->author_id ?? '') == $p->author_id ? 'selected' : '' }}>
                    {{ $p->author_name }}
                </option>
            @endforeach
        </select>
        @error('author_id')<p class="pesan-salah">{{ $message }}</p>@enderror

        <div id="kotak_penulis_baru" style="margin-top: 10px; display: {{ (old('author_id') == 'new' || old('new_author_name')) ? 'block' : 'none' }};">
            <input type="text" id="new_author_name" name="new_author_name"
                   value="{{ old('new_author_name') }}"
                   placeholder="Tuliskan nama penulis baru..."
                   class="@error('new_author_name') salah @enderror">
            @error('new_author_name')<p class="pesan-salah">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<script>
function toggleKategoriBaru(val) {
    const box = document.getElementById('kotak_kategori_baru');
    const input = document.getElementById('new_category_name');
    if (val === 'new') {
        box.style.display = 'block';
        input.focus();
    } else {
        box.style.display = 'none';
    }
}

function togglePenulisBaru(val) {
    const box = document.getElementById('kotak_penulis_baru');
    const input = document.getElementById('new_author_name');
    if (val === 'new') {
        box.style.display = 'block';
        input.focus();
    } else {
        box.style.display = 'none';
    }
}
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

    @if ($buku && $buku->cover_image)
        <div style="margin-top:10px;">
            <img src="{{ asset('storage/' . $buku->cover_image) }}" alt="Cover saat ini"
                 style="width:100px;height:140px;object-fit:cover;border-radius:8px;border:1px solid var(--garis);">
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
