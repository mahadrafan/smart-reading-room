<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Smart Reading Room</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>
    <div class="atas">
        <div class="brand">Smart Reading Room</div>
        <div class="akun">
            <span>{{ Auth::user()->name }} (Peminjam)</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Keluar</button>
            </form>
        </div>
    </div>

    <div class="isi">
        <h1>Halo, {{ Auth::user()->name }}</h1>
        <p class="ket">Halaman ini sementara. Data di bawah dibaca dari tabel users di database.</p>

        <div class="kotak">
            <table>
                <tr><th>Nama</th><td>{{ Auth::user()->name }}</td></tr>
                <tr><th>NIS/NIP</th><td>{{ Auth::user()->nim_nip }}</td></tr>
                <tr><th>Kelas</th><td>{{ Auth::user()->class }}</td></tr>
                <tr><th>Email</th><td>{{ Auth::user()->email }}</td></tr>
                <tr><th>No. telepon</th><td>{{ Auth::user()->phone }}</td></tr>
                <tr><th>Role</th><td>{{ Auth::user()->role }}</td></tr>
                <tr><th>Terdaftar</th><td>{{ Auth::user()->created_at }}</td></tr>
            </table>
        </div>
    </div>
</body>
</html>
