{{-- tab di halaman Kelola Buku: Buku / Nonaktif / Kategori / Penulis --}}
<nav class="tab-kelola" aria-label="Bagian Kelola Buku">
    <a href="{{ route('admin.buku.index') }}" class="{{ request()->routeIs('admin.buku.index') ? 'aktif' : '' }}"
       @if (request()->routeIs('admin.buku.index')) aria-current="page" @endif>
        Buku <span class="jumlah-tab">{{ $jumlah['buku'] }}</span>
    </a>
    <a href="{{ route('admin.buku.nonaktif') }}" class="{{ request()->routeIs('admin.buku.nonaktif') ? 'aktif' : '' }}"
       @if (request()->routeIs('admin.buku.nonaktif')) aria-current="page" @endif>
        Nonaktif <span class="jumlah-tab">{{ $jumlah['nonaktif'] }}</span>
    </a>
    <a href="{{ route('admin.buku.kategori.index') }}" class="{{ request()->routeIs('admin.buku.kategori.*') ? 'aktif' : '' }}"
       @if (request()->routeIs('admin.buku.kategori.*')) aria-current="page" @endif>
        Kategori <span class="jumlah-tab">{{ $jumlah['kategori'] }}</span>
    </a>
    <a href="{{ route('admin.buku.penulis.index') }}" class="{{ request()->routeIs('admin.buku.penulis.*') ? 'aktif' : '' }}"
       @if (request()->routeIs('admin.buku.penulis.*')) aria-current="page" @endif>
        Penulis <span class="jumlah-tab">{{ $jumlah['penulis'] }}</span>
    </a>
</nav>
