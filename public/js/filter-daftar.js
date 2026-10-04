// filter daftar (Review & Ulasan peminjam, Kelola Ulasan admin, Request Masuk admin):
// pilihan buku yang bisa dicari + panel filter rating (opsional); setiap perubahan langsung diterapkan.
// form berisi: #filter_buku (select), #cari_buku_filter, #daftar_buku_filter, #label_filter_buku,
// [data-cari-buku-filter], [data-panel-rating] bila ada, dan tombol [data-tombol-terapkan] untuk tanpa JavaScript.
function pasangFilterDaftar(form, kunciPanel) {
    form.querySelectorAll('select').forEach(function (s) {
        s.addEventListener('change', function () { form.requestSubmit(); });
    });
    pasangCariBuku(form);
    if (form.querySelector('[data-panel-rating]')) { pasangFilterRating(form, kunciPanel); }
    var terapkan = form.querySelector('[data-tombol-terapkan]');
    if (terapkan) { terapkan.remove(); }
    // isian kosong / bawaan tidak ikut ke alamat halaman
    form.addEventListener('submit', function () {
        form.querySelectorAll('input, select').forEach(function (el) {
            if (el.value === '' || !el.name) { el.disabled = true; }
        });
    });
    // kembali lewat tombol Back: aktifkan lagi isian yang tadi dinonaktifkan
    window.addEventListener('pageshow', function () {
        form.querySelectorAll('[disabled]').forEach(function (el) { el.disabled = false; });
    });

    // filter rating: setiap kotak yang dicentang langsung diterapkan, panel tetap terbuka setelah halaman dimuat ulang
    function pasangFilterRating(form, kunci) {
        var panel = form.querySelector('[data-panel-rating]');

        panel.querySelectorAll('input[type="checkbox"]').forEach(function (cek) {
            cek.addEventListener('change', function () {
                try { sessionStorage.setItem(kunci, cek.value); } catch (e) {}
                form.requestSubmit();
            });
        });

        var terakhir = null;
        try { terakhir = sessionStorage.getItem(kunci); sessionStorage.removeItem(kunci); } catch (e) {}
        if (terakhir) {
            panel.open = true;
            var cek = panel.querySelector('input[value="' + terakhir + '"]');
            if (cek) { cek.focus({ preventScroll: true }); }
        }

        // tutup saat klik di luar panel atau menekan Esc
        document.addEventListener('click', function (e) {
            if (panel.open && !panel.contains(e.target)) { panel.open = false; }
        });
        panel.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && panel.open) {
                panel.open = false;
                panel.querySelector('summary').focus();
            }
        });
    }

    // pilihan buku yang bisa dicari: daftar dibuat dari <select>, memilih buku langsung menerapkan filter
    function pasangCariBuku(form) {
        var wadah = form.querySelector('[data-cari-buku-filter]');
        var select = form.querySelector('#filter_buku');
        var input = form.querySelector('#cari_buku_filter');
        var daftar = form.querySelector('#daftar_buku_filter');
        var kosong = daftar.querySelector('.pilih-buku-kosong');
        var aktif = -1;

        var opsi = Array.prototype.map.call(select.options, function (o, i) {
            var li = document.createElement('li');
            li.id = 'buku_filter_' + i;
            li.setAttribute('role', 'option');
            li.dataset.nilai = o.value;
            li.dataset.teks = o.textContent.trim();
            var judul = document.createElement('span');
            judul.className = 'opsi-judul';
            judul.textContent = li.dataset.teks;
            var meta = document.createElement('span');
            meta.className = 'opsi-meta';
            meta.textContent = o.dataset.meta || '';
            li.append(judul, meta);
            li.setAttribute('aria-selected', o.selected ? 'true' : 'false');
            daftar.insertBefore(li, kosong);
            return li;
        });

        // tampilkan pencarian, sembunyikan select bawaan (tetap ikut terkirim)
        wadah.hidden = false;
        select.hidden = true;
        form.querySelector('#label_filter_buku').htmlFor = input.id;

        function teksTerpilih() { return select.value ? select.options[select.selectedIndex].textContent.trim() : ''; }
        input.value = teksTerpilih();

        function terlihat() { return opsi.filter(function (o) { return !o.hidden; }); }
        function tandai(i) {
            var t = terlihat();
            opsi.forEach(function (o) { o.classList.remove('aktif'); });
            aktif = t.length ? Math.max(0, Math.min(i, t.length - 1)) : -1;
            if (aktif >= 0) {
                t[aktif].classList.add('aktif');
                t[aktif].scrollIntoView({ block: 'nearest' });
                input.setAttribute('aria-activedescendant', t[aktif].id);
            }
        }
        function saring(semua) {
            var kata = semua ? [] : input.value.toLowerCase().split(/\s+/).filter(Boolean);
            var ada = 0;
            opsi.forEach(function (o) {
                // "Semua buku" selalu ada di paling atas
                var cocok = o.dataset.nilai === '' || kata.every(function (k) { return o.dataset.teks.toLowerCase().indexOf(k) !== -1; });
                o.hidden = !cocok;
                if (cocok && o.dataset.nilai !== '') { ada++; }
            });
            kosong.hidden = ada > 0;
        }
        function buka() {
            if (!daftar.hidden) { return; }
            // kolom masih berisi buku terpilih: tampilkan semua buku dan sorot pilihan saat ini
            var semua = input.value === teksTerpilih();
            saring(semua);
            daftar.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            tandai(semua ? terlihat().indexOf(opsi[select.selectedIndex]) : 1);
            if (semua) { input.select(); }
        }
        function tutup() {
            daftar.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
        }
        function pilih(o) {
            tutup();
            input.value = o.dataset.nilai === '' ? '' : o.dataset.teks;
            if (select.value === o.dataset.nilai) { return; }
            select.value = o.dataset.nilai;
            form.requestSubmit();
        }

        input.addEventListener('focus', buka);
        input.addEventListener('click', buka);
        input.addEventListener('input', function () {
            if (daftar.hidden) { daftar.hidden = false; input.setAttribute('aria-expanded', 'true'); }
            saring(false);
            tandai(input.value.trim() ? 1 : 0);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); buka(); tandai(aktif + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); buka(); tandai(aktif - 1); }
            else if (e.key === 'Enter') {
                // Enter di kolom ini memilih buku, bukan mengirim form pencarian
                e.preventDefault();
                var t = terlihat();
                if (!daftar.hidden && t[aktif]) { pilih(t[aktif]); }
            } else if (e.key === 'Escape') { tutup(); input.value = teksTerpilih(); }
        });
        opsi.forEach(function (o) {
            // mousedown agar terpilih sebelum kolom kehilangan fokus
            o.addEventListener('mousedown', function (e) { e.preventDefault(); pilih(o); });
        });
        // ditinggalkan tanpa memilih: kembali ke buku yang sedang dipilih
        input.addEventListener('blur', function () { tutup(); input.value = teksTerpilih(); });
    }
}
