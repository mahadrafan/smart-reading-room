{{-- isian data diri admin (form Tambah dan Edit); $akun null saat menambah --}}
<div class="isian">
    <label for="nim_nip">NIP / ID admin</label>
    <input type="text" id="nim_nip" name="nim_nip" maxlength="20" value="{{ old('nim_nip', $akun->nim_nip ?? '') }}"
           class="@error('nim_nip') salah @enderror" placeholder="Contoh: ADM002" required>
    @error('nim_nip')<p class="pesan-salah">{{ $message }}</p>@enderror
    <p class="pesan-info">Dipakai untuk login di kolom NIS/NIP.</p>
</div>

<div class="isian">
    <label for="name">Nama lengkap</label>
    <input type="text" id="name" name="name" maxlength="100" value="{{ old('name', $akun->name ?? '') }}"
           class="@error('name') salah @enderror" required>
    @error('name')<p class="pesan-salah">{{ $message }}</p>@enderror
</div>

<div class="isian">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" maxlength="100" value="{{ old('email', $akun->email ?? '') }}"
           class="@error('email') salah @enderror" required>
    @error('email')<p class="pesan-salah">{{ $message }}</p>@enderror
</div>

<div class="isian">
    <label for="phone">No. telepon <span class="pesan-info">(opsional)</span></label>
    @include('_isian-telepon', [
        'kode'       => $akun->kode_telepon ?? \App\Models\User::KODE_INDONESIA,
        'nomor'      => $akun->nomor_telepon ?? '',
        'kelasSalah' => 'salah',
        'wajib'      => false,
    ])
    @error('phone_code')<p class="pesan-salah">{{ $message }}</p>@enderror
    @error('phone')<p class="pesan-salah">{{ $message }}</p>@enderror
</div>
