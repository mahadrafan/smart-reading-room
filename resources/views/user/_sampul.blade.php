{{-- sampul buku: pakai cover_image kalau filenya ada, fallback ke inisial --}}
@if ($b->cover_url)
    <div class="sampul sampul-gambar">
        <img src="{{ $b->cover_url }}" alt="Sampul {{ $b->title }}" loading="lazy">
    </div>
@else
    @php
        $daftarWarna = ['#8671c9', '#1b2340', '#2f6f73', '#b5574b', '#5b7b3a', '#a07a2c'];
        $warnaSampul = $daftarWarna[($b->category_id ?? 0) % count($daftarWarna)];
        $kata = explode(' ', trim($b->title));
        $inisial = mb_strtoupper(mb_substr($kata[0], 0, 1) . (isset($kata[1]) ? mb_substr($kata[1], 0, 1) : ''));
    @endphp
    <div class="sampul" style="background: {{ $warnaSampul }};">
        <span class="inisial">{{ $inisial }}</span>
    </div>
@endif