{{-- pop up notifikasi baru: tampil di halaman peminjam mana pun selama masih ada notifikasi belum dibaca.
     Tombol "Mengerti" menandainya sudah dibaca; tanpa JavaScript tetap bekerja sebagai form biasa. --}}
<div class="popup-notif-latar" data-popup-notif>
    <div class="popup-notif" role="dialog" aria-modal="true" aria-labelledby="popup-notif-judul">
        <div class="popup-notif-kepala">
            <span class="popup-notif-lonceng" aria-hidden="true">🔔</span>
            <div>
                <h2 id="popup-notif-judul">Ada {{ $jumlahNotifBaru }} notifikasi baru</h2>
                <p>Perubahan peminjamanmu sejak terakhir kali dibuka.</p>
            </div>
        </div>

        <ul class="popup-notif-daftar">
            @foreach ($popupNotif as $notif)
                @php($tampil = $notif->tampilan)
                <li>
                    <div class="notif-ikon-wrap {{ $tampil['ikonClass'] }}" aria-hidden="true">{{ $tampil['ikon'] }}</div>
                    <div class="notif-isi">
                        <div class="notif-judul-baris">
                            <span class="notif-judul">{{ ucfirst($notif->type ?? 'Info') }}</span>
                            <span class="notif-waktu">{{ $notif->waktu_relatif }}</span>
                        </div>
                        <p class="notif-pesan">{{ $notif->message }}</p>
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($jumlahNotifBaru > $popupNotif->count())
            <p class="popup-notif-lainnya">dan {{ $jumlahNotifBaru - $popupNotif->count() }} notifikasi lainnya</p>
        @endif

        <div class="popup-notif-aksi">
            <a href="{{ route('notifikasi.index') }}" class="popup-notif-lihat">Lihat semua notifikasi</a>
            <form method="POST" action="{{ route('notifikasi.dibaca') }}" data-popup-notif-form>
                @csrf
                <input type="hidden" name="sampai" value="{{ $popupSampai }}">
                <button type="submit" class="tombol-pil-utama" autofocus>Mengerti</button>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        var latar = document.querySelector('[data-popup-notif]');
        var form = latar.querySelector('[data-popup-notif-form]');
        var sudahDitutup = false;

        // tandai dibaca di belakang layar lalu tutup tanpa memuat ulang halaman
        function tutup() {
            if (sudahDitutup) return;
            sudahDitutup = true;
            latar.remove();
            document.body.classList.remove('ada-popup');
            var angka = document.querySelector('[data-angka-notif]');
            if (angka) angka.remove();

            fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new FormData(form),
                credentials: 'same-origin'
            }).catch(function () {});
        }

        document.body.classList.add('ada-popup');

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            tutup();
        });

        // klik di luar kotak atau tombol Esc juga menutup (dan menandai sudah dibaca)
        latar.addEventListener('click', function (e) {
            if (e.target === latar) tutup();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') tutup();
        });

        // fokus tetap di dalam pop up selama terbuka
        var fokusable = latar.querySelectorAll('a, button');
        latar.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab' || fokusable.length === 0) return;
            var pertama = fokusable[0], terakhir = fokusable[fokusable.length - 1];
            if (e.shiftKey && document.activeElement === pertama) { e.preventDefault(); terakhir.focus(); }
            else if (! e.shiftKey && document.activeElement === terakhir) { e.preventDefault(); pertama.focus(); }
        });
    })();
</script>
