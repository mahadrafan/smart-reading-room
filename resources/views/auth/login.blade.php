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
    <form method="POST" action="{{ route('login.proses') }}">
        @csrf

        <div class="field">
            <label for="nim_nip" class="sr-only">NIS/NIP</label>
            <input type="text" id="nim_nip" name="nim_nip"
                   placeholder="NIS/NIP"
                   value="{{ old('nim_nip') }}"
                   class="@error('nim_nip') is-invalid @enderror"
                   autocomplete="username" required autofocus>
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
                       autocomplete="current-password" required>
                <button type="button" class="pw-toggle"
                        onclick="tampilPassword('password', this)">Lihat</button>
            </div>
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="opsi-login">
            <label class="ingat">
                <input type="checkbox" name="remember">
                Ingat saya
            </label>
            <a href="{{ route('password.forgot') }}" class="lupa-pw">Lupa password?</a>
        </div>

        <button type="submit" class="btn">Masuk</button>
    </form>
@endsection
