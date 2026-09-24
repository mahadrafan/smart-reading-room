@extends('layouts.user')

@section('title', 'Profil')

@section('content')
    <div class="kepala">
        <h1>Profil</h1>
        <p class="ket">Data ini dipakai otomatis pada form peminjaman.</p>
    </div>

    <div class="dua-kotak">
        {{-- ubah data diri --}}
        <div class="kotak kotak-form">
            <h2 class="judul-kecil">Data diri</h2>
            <form method="POST" action="{{ route('profil.update') }}">
                @csrf
                @method('PUT')

                <div class="isian">
                    <label>NIS/NIP</label>
                    <input type="text" value="{{ Auth::user()->nim_nip }}" readonly>
                </div>

                <div class="isian">
                    <label for="name">Nama lengkap</label>
                    <input type="text" id="name" name="name" value="{{ old('name', Auth::user()->name) }}"
                           class="@error('name') salah @enderror" required>
                    @error('name')
                        <p class="pesan-salah">{{ $message }}</p>
                    @enderror
                </div>

                <div class="isian">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', Auth::user()->email) }}"
                           class="@error('email') salah @enderror" required>
                    @error('email')
                        <p class="pesan-salah">{{ $message }}</p>
                    @enderror
                </div>

                <div class="dua-kolom">
                    <div class="isian">
                        <label for="class">Kelas</label>
                        <input type="text" id="class" name="class" value="{{ old('class', Auth::user()->class) }}"
                               class="@error('class') salah @enderror" required>
                        @error('class')
                            <p class="pesan-salah">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="isian">
                        <label for="phone">No. telepon</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', Auth::user()->phone) }}"
                               class="@error('phone') salah @enderror" required>
                        @error('phone')
                            <p class="pesan-salah">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <p class="pesan-info">Terdaftar sejak {{ Auth::user()->created_at }}</p>

                <button type="submit" class="tombol">Simpan Perubahan</button>
            </form>
        </div>

        {{-- ganti password --}}
        <div class="kotak kotak-form">
            <h2 class="judul-kecil">Ganti password</h2>
            <form method="POST" action="{{ route('profil.password') }}">
                @csrf
                @method('PUT')

                <div class="isian">
                    <label for="password_lama">Password lama</label>
                    <input type="password" id="password_lama" name="password_lama"
                           class="@error('password_lama', 'password') salah @enderror" required>
                    @error('password_lama', 'password')
                        <p class="pesan-salah">{{ $message }}</p>
                    @enderror
                </div>

                <div class="isian">
                    <label for="password_baru">Password baru (minimal 8 karakter)</label>
                    <input type="password" id="password_baru" name="password_baru"
                           class="@error('password_baru', 'password') salah @enderror" required>
                    @error('password_baru', 'password')
                        <p class="pesan-salah">{{ $message }}</p>
                    @enderror
                </div>

                <div class="isian">
                    <label for="password_baru_confirmation">Ulangi password baru</label>
                    <input type="password" id="password_baru_confirmation" name="password_baru_confirmation" required>
                </div>

                <button type="submit" class="tombol">Ganti Password</button>
            </form>
        </div>
    </div>
@endsection
