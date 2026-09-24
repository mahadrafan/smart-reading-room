@extends('layouts.auth')

@section('title', 'Daftar akun')

@section('content')
    <h1>Yuk, Bergabung<br>di Ruang Baca</h1>
    <p class="sub">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>

    <form method="POST" action="{{ route('register.proses') }}">
        @csrf

        <div class="field">
            <label for="name" class="sr-only">Nama lengkap</label>
            <input type="text" id="name" name="name"
                   placeholder="Nama lengkap"
                   value="{{ old('name') }}"
                   class="@error('name') is-invalid @enderror"
                   autocomplete="name" required autofocus>
            @error('name')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="email" class="sr-only">Email</label>
            <input type="email" id="email" name="email"
                   placeholder="Email"
                   value="{{ old('email') }}"
                   class="@error('email') is-invalid @enderror"
                   autocomplete="email" required>
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="row">
            <div class="field">
                <label for="nim_nip" class="sr-only">NIS/NIP</label>
                <input type="text" id="nim_nip" name="nim_nip"
                       placeholder="NIS/NIP"
                       value="{{ old('nim_nip') }}"
                       class="@error('nim_nip') is-invalid @enderror"
                       required>
                @error('nim_nip')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="class" class="sr-only">Kelas</label>
                <input type="text" id="class" name="class"
                       placeholder="Kelas (XII IPA 3)"
                       value="{{ old('class') }}"
                       class="@error('class') is-invalid @enderror"
                       required>
                @error('class')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="field">
            <label for="phone" class="sr-only">No. telepon</label>
            <input type="tel" id="phone" name="phone"
                   placeholder="No. telepon"
                   value="{{ old('phone') }}"
                   class="@error('phone') is-invalid @enderror"
                   autocomplete="tel" required>
            @error('phone')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="password" class="sr-only">Password</label>
            <div class="pw">
                <input type="password" id="password" name="password"
                       placeholder="Password (minimal 8 karakter)"
                       class="@error('password') is-invalid @enderror"
                       autocomplete="new-password" required>
                <button type="button" class="pw-toggle"
                        onclick="tampilPassword('password', this)">Lihat</button>
            </div>
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="password_confirmation" class="sr-only">Ulangi password</label>
            <div class="pw">
                <input type="password" id="password_confirmation" name="password_confirmation"
                       placeholder="Ulangi password"
                       autocomplete="new-password" required>
                <button type="button" class="pw-toggle"
                        onclick="tampilPassword('password_confirmation', this)">Lihat</button>
            </div>
        </div>

        <button type="submit" class="btn">Daftar</button>
    </form>
@endsection