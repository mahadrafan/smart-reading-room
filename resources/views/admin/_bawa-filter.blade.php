{{-- input tersembunyi: pencarian, filter, dan halaman ikut terkirim agar setelah aksi kembali ke tampilan yang sama --}}
@foreach ($bawa as $nama => $nilai)
    @foreach ((array) $nilai as $isi)
        <input type="hidden" name="{{ $nama }}{{ is_array($nilai) ? '[]' : '' }}" value="{{ $isi }}">
    @endforeach
@endforeach
