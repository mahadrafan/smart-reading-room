<div class="kepala">
    <h1>Dashboard Data Warehouse</h1>
    <p class="ket">Analisis peminjaman, kepuasan pembaca, dan keterlambatan dari basdat_dw.</p>
</div>

<style>
    .dw { --kartu: #fffdf9; --garis: #eadfd2; --redup: #7a6a5b; --coklat: #8b5a2b; --pasir: #d9a066; }
    .dw .tabs { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 16px; }
    .dw .tabs a { padding: 9px 18px; border-radius: 999px; background: #eadfd2; color: inherit; text-decoration: none; font-size: 14px; }
    .dw .tabs a.aktif { background: var(--coklat); color: #fff; font-weight: 700; }

    .dw .filter { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; background: var(--kartu);
                  padding: 14px 18px; border: 1px solid var(--garis); border-radius: 14px; }
    .dw .filter label { display: flex; flex-direction: column; font-size: 12px; color: var(--redup); font-weight: 600; }
    .dw .filter select { margin-top: 4px; padding: 7px 10px; min-width: 120px; border: 1px solid #d9c9b8;
                         border-radius: 8px; background: #fff; color: inherit; font-family: inherit; }
    .dw .filter a { font-size: 13px; color: var(--coklat); font-weight: 600; text-decoration: none; padding-bottom: 8px; }

    .dw .kpi { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-top: 18px; }
    .dw .kpi div { background: var(--kartu); border: 1px solid var(--garis); border-radius: 14px; padding: 18px; }
    .dw .kpi span { display: block; font-size: 13px; color: var(--redup); }
    .dw .kpi b { display: block; font-size: 30px; margin-top: 4px; }
    .dw .kpi small { display: block; font-size: 12px; color: #9a8a7a; margin-top: 6px; }

    .dw .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 18px; }
    .dw .panel { background: var(--kartu); border: 1px solid var(--garis); border-radius: 14px; padding: 20px;
                 margin-top: 18px; overflow-x: auto; }
    .dw .panel h3 { margin: 0 0 10px; font-size: 18px; }
    .dw .cv { position: relative; height: 300px; }
    .dw .ket { color: var(--redup); font-size: 12px; margin: 10px 0 0; line-height: 1.5; }
    .dw .ket a { color: var(--coklat); font-weight: 600; }

    .dw table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .dw th { font-size: 12px; letter-spacing: .04em; color: var(--redup); text-transform: uppercase; }
    .dw th, .dw td { padding: 9px 10px; border-bottom: 1px solid #f0e6da; text-align: left; }
    .dw th.n, .dw td.n { text-align: right; }
    .dw tr:last-child td { border-bottom: 0; }
    .dw .foot { color: var(--redup); font-size: 12px; margin: 24px 0 6px; }

    @media (max-width: 900px) { .dw .grid { grid-template-columns: 1fr; } }
</style>

<div class="dw">
    @php($q = request()->only(['tahun', 'kuartal', 'kategori', 'tingkat', 'kelas']))
    <nav class="tabs">
        <a href="{{ route('admin.dw.index', $q) }}" class="{{ request()->routeIs('admin.dw.index') ? 'aktif' : '' }}">Ringkasan</a>
        <a href="{{ route('admin.dw.peminjaman', $q) }}" class="{{ request()->routeIs('admin.dw.peminjaman') ? 'aktif' : '' }}">DW 1 · Peminjaman</a>
        <a href="{{ route('admin.dw.kepuasan', $q) }}" class="{{ request()->routeIs('admin.dw.kepuasan') ? 'aktif' : '' }}">DW 2 · Kepuasan</a>
        <a href="{{ route('admin.dw.keterlambatan', $q) }}" class="{{ request()->routeIs('admin.dw.keterlambatan') ? 'aktif' : '' }}">DW 3 · Keterlambatan</a>
    </nav>

    <form method="GET" class="filter">
        @if (request('rinci'))
            <input type="hidden" name="rinci" value="{{ request('rinci') }}">
        @endif
        @foreach (['tahun' => 'Tahun', 'kuartal' => 'Kuartal', 'kategori' => 'Kategori', 'tingkat' => 'Tingkat', 'kelas' => 'Kelas'] as $k => $judulFilter)
            <label>{{ $judulFilter }}
                <select name="{{ $k }}" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach ($opsi[$k] as $o)
                        <option value="{{ $o }}" @selected((string) ($f[$k] ?? '') === (string) $o)>{{ $o }}</option>
                    @endforeach
                </select>
            </label>
        @endforeach
        <a href="{{ url()->current() }}">Reset filter</a>
    </form>
