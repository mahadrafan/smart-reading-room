@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('content')
    <h1>Reset<br>Password</h1>
    <p class="sub">Sudah ingat password? <a href="{{ route('login') }}">Masuk di sini</a></p>

    @if (session('error'))
        <div class="alert alert-error" role="alert">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('password.proses') }}">
        @csrf

        <div class="field">
            <label for="nim_nip" class="sr-only">NIS/NIP</label>
            <input type="text" id="nim_nip" name="nim_nip"
                   placeholder="NIS/NIP Terdaftar"
                   value="{{ old('nim_nip') }}"
                   class="@error('nim_nip') is-invalid @enderror" required autofocus>
            @error('nim_nip')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="email" class="sr-only">Email</label>
            <input type="email" id="email" name="email"
                   placeholder="Email Terdaftar"
                   value="{{ old('email') }}"
                   class="@error('email') is-invalid @enderror" required>
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="password" class="sr-only">Password Baru</label>
            <div class="pw">
                <input type="password" id="password" name="password"
                       placeholder="Password Baru (min. 8 karakter)"
                       class="@error('password') is-invalid @enderror" required>
                <button type="button" class="pw-toggle"
                        onclick="tampilPassword('password', this)">Lihat</button>
            </div>
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="password_confirmation" class="sr-only">Ulangi Password Baru</label>
            <div class="pw">
                <input type="password" id="password_confirmation" name="password_confirmation"
                       placeholder="Ulangi Password Baru" required>
                <button type="button" class="pw-toggle"
                        onclick="tampilPassword('password_confirmation', this)">Lihat</button>
            </div>
        </div>

        <button type="submit" class="btn">Simpan Password Baru</button>
    </form>
@endsection
