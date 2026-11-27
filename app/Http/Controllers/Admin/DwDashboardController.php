<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard analitik data warehouse (hanya membaca koneksi 'dw' = basdat_dw).
 * Semua angka berasal dari view di sql/05_view_dashboard.sql.
 * Rata-rata dan persen selalu dihitung SUM / SUM, bukan rata-rata dari rata-rata.
 */
class DwDashboardController extends Controller
{
    private const FILTER = ['tahun', 'kuartal', 'kategori', 'tingkat', 'kelas'];

    // ---------- util ----------

    private function dw()
    {
        return DB::connection('dw');
    }

    private function filter(Request $r): array
    {
        $f = [];
        foreach (self::FILTER as $k) {
            $f[$k] = $r->query($k);
        }
        return $f;
    }

    /** Query dasar ke satu view dengan filter global diterapkan. */
    private function dari(string $view, array $f)
    {
        $q = $this->dw()->table($view);
        foreach ($f as $k => $v) {
            if ($v !== null && $v !== '') {
                $q->where($k, $v);
            }
        }
        return $q;
    }

    private function opsi(): array
    {
        $v = fn () => $this->dw()->table('v_ringkasan_bulanan')->where('tahun', '>', 0);
        return [
            'tahun'    => $v()->distinct()->orderBy('tahun')->pluck('tahun'),
            'kuartal'  => $v()->distinct()->orderBy('kuartal')->pluck('kuartal'),
            'kategori' => $v()->distinct()->orderBy('kategori')->pluck('kategori'),
            'tingkat'  => $v()->distinct()->orderBy('tingkat')->pluck('tingkat'),
            'kelas'    => $v()->distinct()->orderBy('kelas')->pluck('kelas'),
        ];
    }

    private function periode(array $f): string
    {
        $p = $f['tahun'] ? 'Tahun ' . $f['tahun'] : 'Jan 2025 – Des 2026';
        if ($f['kuartal']) {
            $p .= ', ' . $f['kuartal'];
        }
        $lain = array_filter([$f['kategori'], $f['tingkat'], $f['kelas']]);
        return $lain ? $p . ' · ' . implode(' / ', $lain) : $p;
    }

    private function tampil(string $view, array $f, array $data)
    {
        return view($view, $data + [
            'f'       => $f,
            'opsi'    => $this->opsi(),
            'periode' => $this->periode($f),
        ]);
    }

    private function ints($koleksi)
    {
        return $koleksi->map(fn ($v) => (int) $v)->values();
    }

    private function labelBulan($baris)
    {
        return $baris->map(fn ($b) => sprintf('%04d-%02d', $b->tahun, $b->bulan))->values();
    }

    private function persen($bagian, $total, int $desimal = 1)
    {
        return $total > 0 ? round(100 * $bagian / $total, $desimal) : 0;
    }

    // ---------- halaman ringkasan ----------

    public function index(Request $r)
    {
        $f = $this->filter($r);

        $t = $this->dari('v_ringkasan_bulanan', $f)->selectRaw(
            'COALESCE(SUM(pengajuan),0) pengajuan, COALESCE(SUM(berhasil),0) berhasil,
             COALESCE(SUM(jumlah_ulasan),0) ulasan, COALESCE(SUM(total_rating),0) rating,
             COALESCE(SUM(kewajiban),0) kewajiban, COALESCE(SUM(terlambat),0) terlambat,
             COALESCE(SUM(total_denda),0) denda'
        )->first();

        $kpi = [
            'berhasil'         => (int) $t->berhasil,
            'persen_berhasil'  => $this->persen($t->berhasil, $t->pengajuan),
            'rata_rating'      => $t->ulasan > 0 ? round($t->rating / $t->ulasan, 2) : 0,
            'persen_terlambat' => $this->persen($t->terlambat, $t->kewajiban),
            'denda'            => (int) $t->denda,
        ];

        $bulan = $this->dari('v_ringkasan_bulanan', $f)->where('tahun', '>', 0)
            ->selectRaw('tahun, bulan, SUM(pengajuan) pengajuan, SUM(jumlah_ulasan) ulasan, SUM(terlambat) terlambat')
            ->groupBy('tahun', 'bulan')->orderBy('tahun')->orderBy('bulan')->get();

        return $this->tampil('admin.dw.ringkasan', $f, [
            'kpi'       => $kpi,
            'label'     => $this->labelBulan($bulan),
            'pengajuan' => $this->ints($bulan->pluck('pengajuan')),
            'ulasan'    => $this->ints($bulan->pluck('ulasan')),
            'terlambat' => $this->ints($bulan->pluck('terlambat')),
        ]);
    }

    // ---------- DW 1: peminjaman ----------

    public function peminjaman(Request $r)
    {
        $f = $this->filter($r);
        $rinci = $r->query('rinci') === 'kelas' ? 'kelas' : 'tingkat';   // daftar putih

        $tren = $this->dari('v_peminjaman_bulanan', $f)->where('tahun', '>', 0)
            ->selectRaw('tahun, bulan, SUM(pengajuan) pengajuan, SUM(berhasil) berhasil, SUM(gagal) gagal')
            ->groupBy('tahun', 'bulan')->orderBy('tahun')->orderBy('bulan')->get();

        $kt = $this->dari('v_peminjaman_bulanan', $f)
            ->selectRaw("kategori, {$rinci} as kelompok, SUM(berhasil) berhasil")
            ->groupBy('kategori', $rinci)->orderBy('kategori')->get();
        $kategori = $kt->pluck('kategori')->unique()->values();
        $kelompok = $kt->pluck('kelompok')->unique()->sort()->values();
        $stack = $kelompok->map(fn ($g) => [
            'label' => $g,
            'data'  => $kategori->map(function ($k) use ($kt, $g) {
                $x = $kt->first(fn ($b) => $b->kategori === $k && $b->kelompok === $g);
                return $x ? (int) $x->berhasil : 0;
            })->all(),
        ])->all();

        $buku = $this->dari('v_buku_populer', $f)
            ->selectRaw('judul, kategori, CAST(SUM(berhasil) AS SIGNED) berhasil')
            ->groupBy('book_id', 'judul', 'kategori')
            ->orderByDesc('berhasil')->orderBy('judul')->limit(10)->get();

        $favorit = $this->dari('v_peminjaman_bulanan', $f)
            ->selectRaw('tingkat, kategori, CAST(SUM(berhasil) AS SIGNED) dipinjam')
            ->groupBy('tingkat', 'kategori')->get()
            ->groupBy('tingkat')
            ->map(fn ($g) => $g->sortBy([['dipinjam', 'desc'], ['kategori', 'asc']])->first())
            ->sortKeys()->values();

        return $this->tampil('admin.dw.peminjaman', $f, [
            'rinci'     => $rinci,
            'label'     => $this->labelBulan($tren),
            'pengajuan' => $this->ints($tren->pluck('pengajuan')),
            'berhasil'  => $this->ints($tren->pluck('berhasil')),
            'gagal'     => $this->ints($tren->pluck('gagal')),
            'kategori'  => $kategori,
            'stack'     => $stack,
            'buku'      => $buku,
            'favorit'   => $favorit,
        ]);
    }

    // ---------- DW 2: kepuasan ----------

    public function kepuasan(Request $r)
    {
        $f = $this->filter($r);

        $kat = $this->dari('v_ulasan_bulanan', $f)
            ->selectRaw('kategori, SUM(jumlah_ulasan) n, SUM(total_rating) r')
            ->groupBy('kategori')->orderBy('kategori')->get();

        $kelompok = $this->dari('v_ulasan_bulanan', $f)
            ->selectRaw('tingkat, kelas, SUM(jumlah_ulasan) n, SUM(total_rating) r, SUM(rating_tinggi) t, SUM(rating_rendah) w')
            ->groupBy('tingkat', 'kelas')->orderBy('tingkat')->orderBy('kelas')->get()
            ->map(fn ($x) => [
                'nama'   => $x->tingkat . ' · ' . $x->kelas,
                'n'      => (int) $x->n,
                'rata'   => round($x->r / $x->n, 2),
                'tinggi' => $this->persen($x->t, $x->n),
                'rendah' => $this->persen($x->w, $x->n),
            ])->values();

        $dasar = fn () => $this->dari('v_ulasan_buku', $f)
            ->selectRaw('judul, kategori, SUM(jumlah_ulasan) n, SUM(total_rating) r, SUM(rating_rendah) w')
            ->groupBy('book_id', 'judul', 'kategori');

        $tertinggi = $dasar()->havingRaw('SUM(jumlah_ulasan) >= 3')
            ->orderByRaw('SUM(total_rating) / SUM(jumlah_ulasan) DESC')
            ->orderByRaw('SUM(jumlah_ulasan) DESC')->limit(10)->get();

        $rendah = $dasar()->havingRaw('SUM(rating_rendah) > 0')
            ->orderByRaw('SUM(rating_rendah) / SUM(jumlah_ulasan) DESC')
            ->orderByRaw('SUM(rating_rendah) DESC')->limit(10)->get();

        // sering dipinjam vs rating: ringkas per buku di DW 1 dan DW 2, cocokkan lewat book_id
        $pinjam = $this->dari('v_buku_populer', $f)
            ->selectRaw('book_id, judul, SUM(berhasil) dipinjam')
            ->groupBy('book_id', 'judul')->get()->keyBy('book_id');
        $rating = $this->dari('v_ulasan_buku', $f)
            ->selectRaw('book_id, SUM(jumlah_ulasan) n, SUM(total_rating) r')
            ->groupBy('book_id')->get()->keyBy('book_id');
        $banding = $pinjam->map(function ($p) use ($rating) {
            $u = $rating->get($p->book_id);
            return [
                'judul'    => $p->judul,
                'dipinjam' => (int) $p->dipinjam,
                'n'        => $u ? (int) $u->n : 0,
                'rata'     => $u && $u->n > 0 ? round($u->r / $u->n, 2) : null,
            ];
        })->sortByDesc('dipinjam')->values();

        $sebaran = $this->dari('v_rating_sebaran', $f)
            ->selectRaw('nilai, label_rating, SUM(jumlah_ulasan) n')
            ->groupBy('nilai', 'label_rating')->orderBy('nilai')->get();

        return $this->tampil('admin.dw.kepuasan', $f, [
            'katLabel'  => $kat->pluck('kategori')->values(),
            'katRata'   => $kat->map(fn ($x) => round($x->r / $x->n, 2))->values(),
            'kelompok'  => $kelompok,
            'tertinggi' => $tertinggi,
            'rendah'    => $rendah,
            'banding'   => $banding,
            'sebaran'   => $sebaran,
        ]);
    }

    // ---------- DW 3: keterlambatan ----------

    public function keterlambatan(Request $r)
    {
        $f = $this->filter($r);

        $t = $this->dari('v_keterlambatan_bulanan', $f)->selectRaw(
            'COALESCE(SUM(kewajiban),0) k, COALESCE(SUM(terlambat),0) l,
             COALESCE(SUM(total_hari_terlambat),0) h, COALESCE(SUM(total_denda),0) d'
        )->first();
        $kpi = [
            'kewajiban'        => (int) $t->k,
            'terlambat'        => (int) $t->l,
            'persen_terlambat' => $this->persen($t->l, $t->k),
            'rata_hari'        => $t->l > 0 ? round($t->h / $t->l, 1) : 0,
            'denda'            => (int) $t->d,
        ];

        $bulan = $this->dari('v_keterlambatan_bulanan', $f)->where('tahun', '>', 0)
            ->selectRaw('tahun, bulan, SUM(kewajiban) k, SUM(terlambat) l')
            ->groupBy('tahun', 'bulan')->orderBy('tahun')->orderBy('bulan')->get();

        $kat = $this->dari('v_keterlambatan_bulanan', $f)
            ->selectRaw('kategori, SUM(kewajiban) k, SUM(terlambat) l')
            ->groupBy('kategori')->get()
            ->map(fn ($x) => ['kategori' => $x->kategori, 'persen' => $this->persen($x->l, $x->k)])
            ->sortByDesc('persen')->values();

        $kelompok = $this->dari('v_keterlambatan_bulanan', $f)
            ->selectRaw('tingkat, kelas, SUM(kewajiban) k, SUM(terlambat) l, SUM(total_hari_terlambat) h, SUM(total_denda) d')
            ->groupBy('tingkat', 'kelas')->orderBy('tingkat')->orderBy('kelas')->get()
            ->map(fn ($x) => [
                'nama'      => $x->tingkat . ' · ' . $x->kelas,
                'kewajiban' => (int) $x->k,
                'terlambat' => (int) $x->l,
                'persen'    => $this->persen($x->l, $x->k),
                'rata_hari' => $x->l > 0 ? round($x->h / $x->l, 1) : 0,
                'denda'     => (int) $x->d,
            ])->values();

        $status = $this->dari('v_keterlambatan_status', $f)
            ->selectRaw('status_pengembalian, posisi_buku, status_denda, SUM(kewajiban) k, SUM(total_denda) d')
            ->groupBy('status_pengembalian', 'posisi_buku', 'status_denda')
            ->orderBy('status_pengembalian', 'desc')->orderBy('posisi_buku')->orderBy('status_denda')->get();

        return $this->tampil('admin.dw.keterlambatan', $f, [
            'kpi'      => $kpi,
            'label'    => $this->labelBulan($bulan),
            'persenBln' => $bulan->map(fn ($x) => $this->persen($x->l, $x->k))->values(),
            'kat'      => $kat,
            'kelompok' => $kelompok,
            'status'   => $status,
        ]);
    }
}
