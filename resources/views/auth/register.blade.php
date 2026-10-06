@extends('layouts.auth')

@section('title', 'Daftar akun')

@section('content')
    @php($pendidik = $jenis === \App\Models\User::TENAGA_PENDIDIK)

    <h1>Yuk, Bergabung<br>di Ruang Baca</h1>
    <p class="sub">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>

    {{-- dua versi form daftar; tanpa JavaScript tautan ini memuat ulang halaman dengan versi yang dipilih --}}
    <nav class="pilih-jenis" aria-label="Daftar sebagai" data-pilih-jenis>
        <span class="pilih-jenis-label">Daftar sebagai</span>
        <div class="pilih-jenis-tombol">
            <a href="{{ route('register') }}" data-jenis="Siswa" class="{{ $pendidik ? '' : 'aktif' }}"
               @unless ($pendidik) aria-current="page" @endunless>Siswa</a>
            <a href="{{ route('register', ['sebagai' => 'pendidik']) }}" data-jenis="Tenaga Pendidik" class="{{ $pendidik ? 'aktif' : '' }}"
               @if ($pendidik) aria-current="page" @endif>Tenaga Pendidik</a>
        </div>
    </nav>

    <form method="POST" action="{{ route('register.proses') }}">
        @csrf
        <input type="hidden" name="borrower_type" value="{{ $jenis }}">
        @error('borrower_type')
            <p class="field-error">{{ $message }}</p>
        @enderror

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
                <label for="nim_nip" class="sr-only" data-label-nomor>{{ $pendidik ? 'NIP' : 'NIS' }}</label>
                <input type="text" id="nim_nip" name="nim_nip"
                       placeholder="{{ $pendidik ? 'NIP' : 'NIS' }}"
                       value="{{ old('nim_nip') }}"
                       class="@error('nim_nip') is-invalid @enderror"
                       required>
                @error('nim_nip')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- hanya isian milik jenis yang dipilih yang aktif & ikut terkirim --}}
            <div class="field" data-khusus="Siswa" @if ($pendidik) hidden @endif>
                <label for="class" class="sr-only">Kelas</label>
                <input type="text" id="class" name="class" maxlength="20"
                       placeholder="Kelas (XII IPA 3)"
                       value="{{ old('class') }}"
                       class="@error('class') is-invalid @enderror"
                       @if ($pendidik) disabled @else required @endif>
                @error('class')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field" data-khusus="Tenaga Pendidik" @unless ($pendidik) hidden @endunless>
                <label for="job_title" class="sr-only">Jabatan / mapel</label>
                <input type="text" id="job_title" name="job_title" maxlength="50"
                       placeholder="Jabatan / mapel"
                       value="{{ old('job_title') }}"
                       class="@error('job_title') is-invalid @enderror"
                       @if ($pendidik) required @else disabled @endif>
                @error('job_title')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="field">
            <label for="phone" class="sr-only">No. telepon</label>
            @include('_isian-telepon', ['kode' => \App\Models\User::KODE_INDONESIA, 'nomor' => '', 'placeholder' => 'No. HP (81234567890)'])
            @error('phone_code')
                <p class="field-error">{{ $message }}</p>
            @enderror
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

    <script>
    // ganti versi form tanpa memuat ulang halaman, agar isian yang sudah diketik tidak hilang
    (function () {
        var pilihan = document.querySelector('[data-pilih-jenis]');
        var form = pilihan.nextElementSibling;
        var jenisInput = form.querySelector('input[name="borrower_type"]');
        var nomor = document.getElementById('nim_nip');
        var labelNomor = form.querySelector('[data-label-nomor]');

        pilihan.querySelectorAll('a[data-jenis]').forEach(function (tautan) {
            tautan.addEventListener('click', function (e) {
                e.preventDefault();
                var jenis = tautan.dataset.jenis;
                var pendidik = jenis === 'Tenaga Pendidik';

                jenisInput.value = jenis;
                pilihan.querySelectorAll('a[data-jenis]').forEach(function (a) {
                    var aktif = a === tautan;
                    a.classList.toggle('aktif', aktif);
                    if (aktif) { a.setAttribute('aria-current', 'page'); } else { a.removeAttribute('aria-current'); }
                });
                form.querySelectorAll('[data-khusus]').forEach(function (kotak) {
                    var tampil = kotak.dataset.khusus === jenis;
                    var isian = kotak.querySelector('input');
                    kotak.hidden = !tampil;
                    isian.disabled = !tampil;
                    isian.required = tampil;
                });
                nomor.placeholder = labelNomor.textContent = pendidik ? 'NIP' : 'NIS';
                history.replaceState(null, '', tautan.href);
            });
        });
    })();
    </script>
@endsection