<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Smart Reading Room Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <div class="app">

        <aside class="sisi">
            <div class="brand">
                Smart Reading Room
                <small>Panel admin</small>
            </div>

            <nav class="menu">
                <a href="{{ route('admin.dashboard') }}"
                   class="{{ request()->routeIs('admin.dashboard') ? 'aktif' : '' }}">Dashboard</a>

                <div class="grup-menu">Kelola Data</div>
                <a href="{{ route('admin.buku.index') }}"
                   class="{{ request()->routeIs('admin.buku.*') ? 'aktif' : '' }}">Kelola Buku</a>
                <a href="{{ route('admin.kp.index') }}"
                   class="{{ request()->routeIs('admin.kp.*') ? 'aktif' : '' }}">Kategori &amp; Penulis</a>
                <a href="{{ route('admin.ebooks.index') }}"
                   class="{{ request()->routeIs('admin.ebooks.*') ? 'aktif' : '' }}">Kelola E-book</a>
                <a href="{{ route('admin.reviews.index') }}"
                   class="{{ request()->routeIs('admin.reviews.*') ? 'aktif' : '' }}">Kelola Ulasan</a>
                <a href="{{ route('admin.peminjam.index') }}"
                   class="{{ request()->routeIs('admin.peminjam.*') ? 'aktif' : '' }}">Data Peminjam</a>

                <div class="grup-menu">Peminjaman</div>
                <a href="{{ route('admin.peminjaman.index') }}"
                   class="{{ request()->routeIs('admin.peminjaman.index') ? 'aktif' : '' }}">Request Masuk</a>
                <a href="{{ route('admin.peminjaman.riwayat') }}"
                   class="{{ request()->routeIs('admin.peminjaman.riwayat') ? 'aktif' : '' }}">Riwayat</a>
            </nav>

            <div class="akun-bawah">
                <div class="nama">{{ Auth::user()->name }}</div>
                <div class="kelas">{{ Auth::user()->nim_nip }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Keluar</button>
                </form>
            </div>
        </aside>

        <main class="konten">
            @if (session('status'))
                <div class="alert alert-sukses" role="status">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-galat" role="alert">{{ session('error') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
