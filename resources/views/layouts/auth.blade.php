<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Smart Reading Room')</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <div class="panel">
        <div class="brand">Smart Reading Room</div>

        <div class="isi">
            <div class="ilustrasi">
                <img src="{{ asset('img/ilustrasi.svg') }}" alt="Ilustrasi membaca buku">
            </div>

            <div class="kartu">
                @yield('content')
            </div>
        </div>
    </div>

    <script>
        function tampilPassword(id, btn) {
            const input = document.getElementById(id);
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'Sembunyikan';
            } else {
                input.type = 'password';
                btn.textContent = 'Lihat';
            }
        }
    </script>
</body>
</html>
