    <p class="foot">
        Semua angka dibaca dari basdat_dw. Data ini adalah data dummy untuk pengembangan,
        sehingga pola di dalamnya tidak menggambarkan kondisi perpustakaan sebenarnya.
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.color = '#4a3728';
    Chart.defaults.borderColor = '#eadfd2';
    const PAL = ['#8b5a2b', '#d9a066', '#4a7c59', '#c0392b', '#5b6c8f', '#b08968', '#7f5539', '#a3a380', '#6b705c'];
    function ds(label, data, i, extra) {
        return Object.assign({ label: label, data: data, backgroundColor: PAL[i % PAL.length], borderColor: PAL[i % PAL.length] }, extra || {});
    }
</script>
