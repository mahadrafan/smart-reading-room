{{-- no. telepon dengan pilihan kode negara (dipakai form daftar & profil).
     butuh $kode dan $nomor (nilai awal); opsional $kelasSalah (kelas saat ada error) dan $placeholder.
     select kode negara dibuat transparan di atas teks "+62" supaya kotaknya tetap ringkas,
     sementara daftar pilihannya tetap menampilkan nama negara --}}
@php
    $kode = (string) old('phone_code', $kode);
    $salah = $errors->has('phone') || $errors->has('phone_code');
@endphp
<div class="telepon {{ $salah ? ($kelasSalah ?? 'is-invalid') : '' }}">
    <span class="kode-negara">
        <span class="kode-tampil" aria-hidden="true">+{{ $kode }}</span>
        <select name="phone_code" aria-label="Kode negara"
                onchange="this.previousElementSibling.textContent = '+' + this.value">
            @foreach (\App\Models\User::KODE_NEGARA as $k => $negara)
                <option value="{{ $k }}" @selected((string) $k === $kode)>{{ $negara }} (+{{ $k }})</option>
            @endforeach
        </select>
    </span>
    <input type="tel" id="phone" name="phone" inputmode="tel" maxlength="20"
           value="{{ old('phone', $nomor) }}" placeholder="{{ $placeholder ?? 'No. telepon' }}"
           autocomplete="tel-national" required
           @if ($salah) aria-invalid="true" @endif>
</div>
