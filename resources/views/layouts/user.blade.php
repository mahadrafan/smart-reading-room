<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Smart Reading Room</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/user.css') }}?v={{ filemtime(public_path('css/user.css')) }}">
</head>
<body>
    <div class="app">

        {{-- sidebar kiri --}}
        <aside class="sisi">
            <div class="brand">
                Smart Reading Room
                <small>Portal peminjam</small>
            </div>

            <nav class="menu">
                <a href="{{ route('dashboard') }}"
                   class="{{ request()->routeIs('dashboard', 'buku.detail') ? 'aktif' : '' }}">Katalog Buku</a>
                <a href="{{ route('peminjaman.buat') }}"
                   class="{{ request()->routeIs('peminjaman.buat') ? 'aktif' : '' }}">Form Peminjaman</a>
                <a href="{{ route('peminjaman.index') }}"
                   class="{{ request()->routeIs('peminjaman.index', 'peminjaman.detail') ? 'aktif' : '' }}">Riwayat Peminjaman</a>
                <a href="{{ route('ulasan.index') }}"
                   class="{{ request()->routeIs('ulasan.index') ? 'aktif' : '' }}">Review &amp; Ulasan</a>
                <a href="{{ route('notifikasi.index') }}"
                   class="{{ request()->routeIs('notifikasi.*') ? 'aktif' : '' }}">
                    Notifikasi
                    @if ($jumlahNotifBaru > 0)
                        <span class="menu-angka" data-angka-notif aria-label="{{ $jumlahNotifBaru }} notifikasi belum dibaca">{{ $jumlahNotifBaru }}</span>
                    @endif
                </a>
                <a href="{{ route('profil') }}"
                   class="{{ request()->routeIs('profil*') ? 'aktif' : '' }}">Profil</a>
            </nav>

            <div class="akun-bawah">
                <div class="nama">{{ Auth::user()->name }}</div>
                {{-- kelas untuk siswa, jabatan untuk tenaga pendidik --}}
                <div class="kelas">{{ Auth::user()->keterangan }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Keluar</button>
                </form>
            </div>
        </aside>

        {{-- isi halaman --}}
        <main class="konten">
            @if (session('status'))
                <div class="alert alert-sukses" role="status">{{ session('status') }}</div>
            @endif

            @yield('content')
        </main>
    </div>

    @if ($popupNotif->isNotEmpty())
        @include('_popup-notifikasi')
    @endif

    @yield('scripts')
</body>
</html>
