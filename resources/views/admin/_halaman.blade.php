{{-- navigasi halaman (Sebelumnya / Berikutnya) untuk paginator $halaman; tidak tampil bila hanya satu halaman --}}
@if ($halaman->hasPages())
    <div class="halaman">
        @if ($halaman->onFirstPage())
            <span class="nonaktif">Sebelumnya</span>
        @else
            <a href="{{ $halaman->previousPageUrl() }}">Sebelumnya</a>
        @endif
        <span>Halaman {{ $halaman->currentPage() }} dari {{ $halaman->lastPage() }}</span>
        @if ($halaman->hasMorePages())
            <a href="{{ $halaman->nextPageUrl() }}">Berikutnya</a>
        @else
            <span class="nonaktif">Berikutnya</span>
        @endif
    </div>
@endif
