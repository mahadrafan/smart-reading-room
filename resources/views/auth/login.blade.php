@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
    <h1>Mau Baca Apa<br>Hari Ini?</h1>
    <p class="sub">Pengguna baru? <a href="{{ route('register') }}">Daftar di sini</a></p>

    {{-- muncul setelah berhasil daftar --}}
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    {{-- muncul kalau login gagal --}}
    @if (session('error'))
        <div class="alert alert-error" role="alert">{{ session('error') }}</div>
    @endif

    {{-- satu form ini dipakai peminjam maupun admin;
         sistem membaca kolom role di database untuk menentukan
         diarahkan ke landing page peminjam atau admin --}}
    @php
        // NIS / ID admin dari login terakhir dengan "Ingat saya"; setelah percobaan login gagal, isian form yang dipakai.
        // password tidak pernah diisi aplikasi: autocomplete current-password membuat password manager browser mengisinya
        $adaIsianLama = old('nim_nip') !== null;
        $nis = $adaIsianLama ? old('nim_nip') : $nisDiingat;
        $ingat = $adaIsianLama ? (bool) old('remember') : $nisDiingat !== null;
    @endphp

    <form method="POST" action="{{ route('login.proses') }}">
        @csrf

        <div class="field">
            <label for="nim_nip" class="sr-only">NIS atau ID admin</label>
            <input type="text" id="nim_nip" name="nim_nip"
                   placeholder="NIS atau ID admin"
                   value="{{ $nis }}"
                   class="@error('nim_nip') is-invalid @enderror"
                   autocomplete="username" required @unless ($nis) autofocus @endunless>
            @if ($nisDiingat && ! $adaIsianLama)
                <p class="info-diingat">Bukan akun kamu? <a href="{{ route('login', ['ganti' => 1]) }}">Ganti akun</a></p>
            @endif
            @error('nim_nip')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="password" class="sr-only">Password</label>
            <div class="pw">
                <input type="password" id="password" name="password"
                       placeholder="Password"
                       class="@error('password') is-invalid @enderror"
                       autocomplete="current-password" required @if ($nis) autofocus @endif>
                <button type="button" class="pw-toggle"
                        onclick="tampilPassword('password', this)">Lihat</button>
            </div>
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="opsi-login">
            <label class="ingat">
                <input type="checkbox" name="remember" @checked($ingat)>
                Ingat saya
            </label>
            <a href="{{ route('password.forgot') }}" class="lupa-pw">Lupa password?</a>
        </div>

        <button type="submit" class="btn">Masuk</button>
    </form>
@endsection
