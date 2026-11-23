// Membuat Laporan-Data-Warehouse-Smart-Reading-Room-Revisi.docx
// Jalankan: node buat_laporan.js <data.json>
// data.json berisi hasil 04_query_analisis.sql (lihat README di dokumen-data-warehouse.md).
const fs = require("fs");
const path = require("path");
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, ImageRun,
  AlignmentType, HeadingLevel, WidthType, ShadingType, BorderStyle, LevelFormat,
  PageBreak, Footer, PageNumber, VerticalAlign,
} = require("docx");

const D = JSON.parse(fs.readFileSync(process.argv[2], "utf8"));
const HERE = __dirname;
const FONT = "Calibri";
const UNGU = "4D0057", ABU = "767686", INK = "1F1F2B";
const PAGE_W = 11906 - 2 * 1440; // lebar isi A4 (DXA)

// ---------- helper teks ----------
const run = (t, o = {}) => new TextRun({ text: t, font: FONT, size: 22, color: INK, ...o });
function para(parts, o = {}) {
  const children = (Array.isArray(parts) ? parts : [parts]).map(p => typeof p === "string" ? run(p) : p);
  return new Paragraph({ children, alignment: AlignmentType.JUSTIFIED, spacing: { after: 120, line: 288 }, ...o });
}
const b = t => run(t, { bold: true });
const code = t => run(t, { font: "Consolas", size: 20, color: UNGU });
const h1 = t => new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun({ text: t })] });
const h2 = t => new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun({ text: t })] });
const caption = t => new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 60, after: 200 },
  children: [run(t, { size: 20, italics: true, color: ABU })] });
const tblTitle = t => new Paragraph({ keepNext: true, spacing: { before: 160, after: 80 },
  children: [run(t, { size: 20, bold: true, color: UNGU })] });

// ---------- helper tabel ----------
const border = { style: BorderStyle.SINGLE, size: 4, color: "D9D9E3" };
const borders = { top: border, bottom: border, left: border, right: border };
/**
 * rows: array baris; tiap baris = array sel (string) atau {cells, kind}
 * kind: 'sub' (subtotal), 'total'
 */
function table(header, rows, widths, align = []) {
  const total = widths.reduce((a, c) => a + c, 0);
  const mk = (txt, i, opt) => new TableCell({
    width: { size: widths[i], type: WidthType.DXA },
    borders, verticalAlign: VerticalAlign.CENTER,
    margins: { top: 50, bottom: 50, left: 90, right: 90 },
    shading: opt.fill ? { fill: opt.fill, type: ShadingType.CLEAR, color: "auto" } : undefined,
    columnSpan: opt.span,
    children: [new Paragraph({ alignment: opt.al || AlignmentType.LEFT,
      children: [run(String(txt), { size: 18, bold: !!opt.bold, color: opt.color || INK })] })],
  });
  const al = i => align[i] === "c" ? AlignmentType.CENTER : align[i] === "r" ? AlignmentType.RIGHT : AlignmentType.LEFT;
  const out = [new TableRow({ tableHeader: true,
    children: header.map((h, i) => mk(h, i, { fill: UNGU, bold: true, color: "FFFFFF", al: al(i) })) })];
  rows.forEach((r, k) => {
    const cells = Array.isArray(r) ? r : r.cells;
    const kind = Array.isArray(r) ? null : r.kind;
    const fill = kind === "total" ? "D9B8E0" : kind === "sub" ? "EFE3F2" : (k % 2 ? "F4F4F7" : null);
    out.push(new TableRow({ cantSplit: true,
      children: cells.map((c, i) => mk(c, i, { fill, bold: !!kind, al: al(i) })) }));
  });
  return new Table({ width: { size: total, type: WidthType.DXA }, columnWidths: widths, rows: out });
}
const img = (file, w) => {
  const buf = fs.readFileSync(path.join(HERE, "gambar-tanpa-judul", file));
  const pw = buf.readUInt32BE(16), ph = buf.readUInt32BE(20); // ukuran asli PNG
  const wpx = w, hpx = Math.round(w * ph / pw);
  return new Paragraph({ alignment: AlignmentType.CENTER, keepNext: true, spacing: { before: 120 },
    children: [new ImageRun({ type: "png", data: buf, transformation: { width: wpx, height: hpx },
      altText: { title: file, description: file, name: file } })] });
};

// ---------- format angka ----------
const n0 = v => (v === undefined || v === "NULL" || v === "0" || v === 0) ? "-" : String(v);
const dec = v => (v === undefined || v === "NULL") ? "-" : Number(v).toFixed(2).replace(".", ",");
const pct = v => (v === undefined || v === "NULL") ? "-" : Number(v).toFixed(1).replace(".", ",") + "%";
const rp = v => (!v || v === "0") ? "-" : "Rp" + Number(v).toLocaleString("id-ID");

const KATEGORI = ["Ekonomi & Akuntansi", "Fiksi & Sastra", "horror", "Komputer & Teknologi", "Pengujian Sistem", "Romance & Drama"];
const KUARTAL = ["Q1", "Q2", "Q3", "Q4"];

// ---------- Tabel information package DW 1 ----------
function tabelDw1() {
  const rows = [];
  for (const q of KUARTAL) {
    KATEGORI.forEach((k, i) => {
      const r = D.dw1_pivot.find(x => x.kuartal === q && x.kategori === k) || {};
      rows.push([i === 0 ? q : "", k, n0(r.kelas_10), n0(r.kelas_11), n0(r.kelas_12), n0(r.tenaga_pendidik), n0(r.lainnya), n0(r.total)]);
    });
    const s = D.dw1_pivot.find(x => x.kuartal === q && x.kategori === "Semua kategori");
    rows.push({ kind: "sub", cells: ["Total " + q, "", n0(s.kelas_10), n0(s.kelas_11), n0(s.kelas_12), n0(s.tenaga_pendidik), n0(s.lainnya), n0(s.total)] });
  }
  const t = D.dw1_pivot.find(x => x.tahun === "TOTAL");
  rows.push({ kind: "total", cells: ["Total", "", t.kelas_10, t.kelas_11, t.kelas_12, t.tenaga_pendidik, t.lainnya, t.total] });
  return table(["Kuartal", "Kategori buku", "Kelas 10", "Kelas 11", "Kelas 12", "Tenaga Pendidik", "Lainnya", "Total"],
    rows, [1050, 2150, 880, 880, 880, 1186, 900, 1100], ["l", "l", "c", "c", "c", "c", "c", "c"]);
}

// ---------- Tabel information package DW 2 ----------
function tabelDw2() {
  const rows = [];
  for (const q of KUARTAL) {
    KATEGORI.forEach((k, i) => {
      const r = D.dw2_pivot.find(x => x.kuartal === q && x.kategori === k) || {};
      rows.push([i === 0 ? q : "", k, dec(r.kelas_10), dec(r.kelas_11), dec(r.kelas_12), dec(r.tenaga_pendidik), dec(r.lainnya), n0(r.jumlah_ulasan), dec(r.rata_rating)]);
    });
    const s = D.dw2_pivot.find(x => x.kuartal === q && x.kategori === "Semua kategori");
    rows.push({ kind: "sub", cells: ["Total " + q, "", dec(s.kelas_10), dec(s.kelas_11), dec(s.kelas_12), dec(s.tenaga_pendidik), dec(s.lainnya), n0(s.jumlah_ulasan), dec(s.rata_rating)] });
  }
  const t = D.dw2_pivot.find(x => x.tahun === "TOTAL");
  rows.push({ kind: "total", cells: ["Total", "", dec(t.kelas_10), dec(t.kelas_11), dec(t.kelas_12), dec(t.tenaga_pendidik), dec(t.lainnya), t.jumlah_ulasan, dec(t.rata_rating)] });
  return table(["Kuartal", "Kategori buku", "Kelas 10", "Kelas 11", "Kelas 12", "Tenaga Pendidik", "Lainnya", "Jumlah ulasan", "Rata-rata"],
    rows, [1000, 2000, 760, 760, 760, 1006, 780, 900, 1060], ["l", "l", "c", "c", "c", "c", "c", "c", "c"]);
}

// ---------- Tabel information package DW 3 ----------
function tabelDw3() {
  const rows = [];
  for (const q of KUARTAL) {
    const bulan = D.dw3_pivot.filter(x => x.kuartal === q && x.bulan_jatuh_tempo !== "Semua bulan");
    bulan.forEach((r, i) => rows.push([i === 0 ? q : "", r.bulan_jatuh_tempo, n0(r.kelas_10), n0(r.kelas_11), n0(r.kelas_12),
      n0(r.tenaga_pendidik), n0(r.lainnya), n0(r.total_terlambat), r.kewajiban_kembali, pct(r.persen_terlambat)]));
    const s = D.dw3_pivot.find(x => x.kuartal === q && x.bulan_jatuh_tempo === "Semua bulan");
    rows.push({ kind: "sub", cells: ["Total " + q, "", n0(s.kelas_10), n0(s.kelas_11), n0(s.kelas_12), n0(s.tenaga_pendidik), n0(s.lainnya), s.total_terlambat, s.kewajiban_kembali, pct(s.persen_terlambat)] });
  }
  const t = D.dw3_pivot.find(x => x.tahun === "TOTAL");
  rows.push({ kind: "total", cells: ["Total", "", t.kelas_10, t.kelas_11, t.kelas_12, t.tenaga_pendidik, t.lainnya, t.total_terlambat, t.kewajiban_kembali, pct(t.persen_terlambat)] });
  return table(["Kuartal", "Bulan jatuh tempo", "Kelas 10", "Kelas 11", "Kelas 12", "Tenaga Pendidik", "Lainnya", "Total terlambat", "Kewajiban kembali", "% terlambat"],
    rows, [1000, 1130, 720, 720, 720, 940, 780, 940, 1000, 1076], ["l", "l", "c", "c", "c", "c", "c", "c", "c", "c"]);
}
function tabelDw3Kategori() {
  const rows = D.dw3_kat.map(r => [r.nama_kategori, r.kewajiban_kembali, r.terlambat, pct(r.persen_terlambat),
    dec(r.rata_hari_terlambat).replace(/,(\d)0$/, ",$1"), rp(r.total_denda)]);
  return table(["Kategori buku (Y)", "Kewajiban kembali", "Terlambat", "% terlambat", "Rata-rata hari terlambat", "Nominal denda"],
    rows, [2426, 1300, 1100, 1200, 1500, 1500], ["l", "c", "c", "c", "c", "r"]);
}

// ---------- angka untuk narasi ----------
const t1 = D.dw1_pivot.find(x => x.tahun === "TOTAL");
const t2 = D.dw2_pivot.find(x => x.tahun === "TOTAL");
const t3 = D.dw3_pivot.find(x => x.tahun === "TOTAL");
const top1 = D.dw1_top[0];
const topRating = D.dw2_top[0];
const kel2 = Object.fromEntries(D.dw2_kel.map(r => [r.kelompok_peminjam, r]));
const kel3 = Object.fromEntries(D.dw3_kel.map(r => [r.kelompok_peminjam, r]));
const laut = D.dw2_top.find(r => r.judul === top1.judul);
const sebaran = D.dw2_kel.reduce((acc, r) => {
  const n = Number(r.jumlah_ulasan);
  acc.tinggi += Math.round(n * r.persen_rating_tinggi / 100);
  acc.rendah += Math.round(n * r.persen_rating_rendah / 100);
  return acc;
}, { tinggi: 0, rendah: 0 });
const sep = D.dw3_pivot.find(x => x.bulan_jatuh_tempo === "September");
const okt = D.dw3_pivot.find(x => x.bulan_jatuh_tempo === "Oktober");
const kat3 = D.dw3_kat[0], kat3b = D.dw3_kat[1];

// =====================================================================
// ISI DOKUMEN
// =====================================================================
const anggota = [
  ["Irma Nur Alfiyah", "164241006"], ["Ismanisa Ridhan Mutiarso", "164241043"],
  ["Sitepu Mako Rumba Efrata", "164241061"], ["Mahad Madani Rafan", "164241065"],
  ["Vincentius Inda Tandioga", "164241096"],
];
const cover = [
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 1800, after: 120 }, children: [run("TUGAS TM 10", { bold: true, size: 32, color: UNGU })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 600 }, children: [run("MATA KULIAH BASIS DATA", { bold: true, size: 28, color: UNGU })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 120 }, children: [run("Data Warehouse Smart Reading Room", { bold: true, size: 44 })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 900 }, children: [run("Analisis Peminjaman Buku, Kepuasan Pembaca, dan Keterlambatan Pengembalian (Revisi)", { size: 24, color: ABU })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 200 }, children: [run("Kelompok G", { bold: true, size: 26 })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 120 }, children: [run("Anggota:", { size: 22 })] }),
  ...anggota.map((a, i) => new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 60 },
    children: [run(`${i + 1}. ${a[0]}  (${a[1]})`, { size: 22 })] })),
  new Paragraph({ children: [new PageBreak()] }),
];

const isi = [
  // ---------------- 1. PENDAHULUAN ----------------
  h1("1. Pendahuluan"),
  h2("1.1 Tujuan dan Alasan Pembuatan Data Warehouse"),
  para("Database aplikasi Smart Reading Room menggunakan pendekatan OLTP (Online Transaction Processing) untuk mencatat kegiatan operasional, seperti pengajuan peminjaman, persetujuan admin, pengembalian buku, dan pemberian ulasan. Di sisi lain, pengelola perpustakaan membutuhkan informasi untuk melihat pola dalam jangka waktu tertentu, misalnya buku dan kategori yang paling sering dipinjam oleh tiap kelompok peminjam, seberapa puas pembaca terhadap buku yang dipinjam, dan bulan apa pengembalian paling sering terlambat."),
  para("Informasi tersebut dapat diperoleh dari database operasional, tetapi prosesnya membutuhkan penggabungan beberapa tabel dan perhitungan tambahan. Jika query analisis dijalankan langsung pada database transaksi, prosesnya menjadi rumit dan berpotensi menambah beban sistem. Data warehouse (DW) dibuat untuk menyimpan data yang telah diintegrasikan dan disusun agar mudah dipakai untuk analisis historis. Hasil analisisnya dapat menjadi bahan pertimbangan pengadaan buku, promosi buku, dan evaluasi kebijakan pengembalian."),
  para("Data yang digunakan dalam laporan ini adalah data asli aplikasi ditambah data dummy (250 peminjaman, 30 peminjam, dan ulasan selama Januari sampai September 2026) yang dibuat untuk mensimulasikan aktivitas perpustakaan. Oleh karena itu, hasil analisis menggambarkan penerapan data warehouse pada data simulasi dan tidak mewakili kondisi perpustakaan yang sebenarnya. Angka dihitung berdasarkan ETL tanggal 19 November 2026."),
  h2("1.2 Data Warehouse yang Dibangun"),
  para("Kelompok kami membangun tiga data warehouse. Ketiganya memiliki fokus yang berbeda sehingga tidak saling tumpang tindih: DW 1 mengukur seberapa sering buku dipinjam, DW 2 mengukur seberapa puas pembaca, dan DW 3 mengukur kapan pengembalian terlambat."),
  tblTitle("Tabel 1. Data warehouse yang dibangun"),
  table(["Data warehouse", "Fokus", "Pertanyaan strategis"], [
    [ "DW 1 Analisis Peminjaman Buku", "Volume peminjaman", "Buku dan kategori mana yang paling sering dipinjam, dan oleh kelompok mana (Kelas 10, 11, 12, atau tenaga pendidik)? Bagaimana perubahannya per kuartal?" ],
    [ "DW 2 Analisis Kepuasan Pembaca", "Kepuasan pembaca", "Seberapa puas pembaca terhadap buku? Buku mana yang paling banyak mendapat rating tinggi atau rendah? Kelompok mana yang paling puas?" ],
    [ "DW 3 Analisis Keterlambatan Pengembalian", "Kepatuhan pengembalian", "Bulan apa keterlambatan paling banyak terjadi, dan kategori buku serta kelompok peminjam mana yang paling sering terlambat?" ],
  ], [2300, 1700, PAGE_W - 4000]),

  // ---------------- 2. ANALISIS KEBUTUHAN ----------------
  h1("2. Analisis Kebutuhan"),
  para("Analisis kebutuhan mengikuti empat langkah: mengidentifikasi proses bisnis, menentukan level granularity (grain), mengidentifikasi dimensi, dan mengidentifikasi fakta. Setiap DW memiliki empat tabel dimensi miliknya sendiri: tiga dimensi berperan sebagai sumbu X, Y, dan Z pada information package, dan satu dimensi khas yang hanya ada di DW tersebut. Tidak ada tabel dimensi yang dipakai oleh dua tabel fakta."),
  para("Setiap tabel fakta memakai surrogate key AUTO_INCREMENT sebagai primary key (misalnya peminjaman_key). Nomor transaksi asli dari aplikasi (loan_id atau review_id) tetap disimpan sebagai kolom biasa berindeks unik, hanya untuk menelusuri data kembali ke database sumber."),

  h2("2.1 DW 1: Analisis Peminjaman Buku"),
  para([b("Proses bisnis: "), "pengajuan peminjaman buku oleh siswa dan tenaga pendidik sampai buku berhasil dipinjam atau dinyatakan gagal."]),
  para([b("Grain: "), "satu baris adalah satu pengajuan peminjaman dari tabel sumber ", code("loans"), ". Pengajuan yang gagal ikut disimpan agar tingkat keberhasilan dapat dihitung."]),
  tblTitle("Tabel 2. Dimensi DW 1"),
  table(["Peran", "Tabel dimensi", "Atribut dan hierarki"], [
    ["X", "dim_buku_pinjam", "Judul, status aktif; kategori sebagai atribut. Hierarki: judul → kategori"],
    ["Y", "dim_waktu_pengajuan", "Tanggal pengajuan, nama hari, bulan, kuartal, tahun. Hierarki: tanggal → bulan → kuartal → tahun"],
    ["Z", "dim_peminjam_pinjam", "NIM/NIP, nama, tipe peminjam, kelas, jabatan, kelompok peminjam (Kelas 10, Kelas 11, Kelas 12, Tenaga Pendidik)"],
    ["Khas", "dim_status_pinjam", "Status pengajuan (Menunggu, Dikonfirmasi, Dipinjam, Dikembalikan, Gagal) dan kelompok status (Dalam Proses, Berhasil, Gagal)"],
  ], [800, 2100, PAGE_W - 2900], ["c"]),
  tblTitle("Tabel 3. Fakta DW 1 (fact_peminjaman)"),
  table(["Kolom", "Keterangan"], [
    ["peminjaman_key", "Primary key (surrogate key AUTO_INCREMENT)"],
    ["loan_id", "Nomor peminjaman asli, berindeks unik, untuk penelusuran ke sumber"],
    ["jumlah_pengajuan", "Ukuran. Selalu 1 per baris; dijumlahkan menjadi banyaknya pengajuan"],
    ["jumlah_berhasil", "Ukuran. 1 bila status Dipinjam atau Dikembalikan"],
    ["jumlah_gagal", "Ukuran. 1 bila status Gagal (buku tidak diambil atau pengajuan ditolak)"],
  ], [2600, PAGE_W - 2600]),

  h2("2.2 DW 2: Analisis Kepuasan Pembaca"),
  para("DW 2 menjawab pertanyaan yang tidak bisa dijawab DW 1: buku yang sering dipinjam belum tentu disukai pembacanya. Dengan DW ini admin dapat menentukan buku yang perlu ditambah stoknya, dipromosikan, atau tidak dibeli lagi."),
  para([b("Proses bisnis: "), "pemberian ulasan dan rating (1 sampai 5) oleh peminjam terhadap buku yang telah dipinjam."]),
  para([b("Grain: "), "satu baris adalah satu ulasan dari tabel sumber ", code("reviews"), ". Ulasan tanpa rating tidak dimasukkan."]),
  tblTitle("Tabel 4. Dimensi DW 2"),
  table(["Peran", "Tabel dimensi", "Atribut dan hierarki"], [
    ["X", "dim_buku_ulasan", "Judul, penulis, penerbit, tahun terbit, kategori. Hierarki: judul → kategori"],
    ["Y", "dim_waktu_ulasan", "Tanggal ulasan, bulan, kuartal, tahun. Hierarki: tanggal → bulan → kuartal → tahun"],
    ["Z", "dim_pengulas", "Nama, tipe peminjam, kelompok peminjam"],
    ["Khas", "dim_rating", "Nilai 1 sampai 5, label (Sangat Kecewa sampai Sangat Puas), kelompok rating (Tinggi untuk 3–5, Rendah untuk 1–2)"],
  ], [800, 2100, PAGE_W - 2900], ["c"]),
  tblTitle("Tabel 5. Fakta DW 2 (fact_ulasan)"),
  table(["Kolom", "Keterangan"], [
    ["ulasan_key", "Primary key (surrogate key AUTO_INCREMENT)"],
    ["review_id", "Nomor ulasan asli, berindeks unik, untuk penelusuran ke sumber"],
    ["rating", "Ukuran. Nilai 1 sampai 5. Rata-rata rating dihitung saat query: jumlah rating dibagi jumlah ulasan"],
    ["jumlah_ulasan", "Ukuran. Selalu 1 per baris; dijumlahkan menjadi banyaknya ulasan"],
    ["flag_rating_tinggi", "Ukuran. 1 bila rating ≥ 3 (pembaca puas)"],
    ["flag_rating_rendah", "Ukuran. 1 bila rating ≤ 2 (pembaca kecewa). Setiap ulasan masuk tepat satu dari dua kelompok, sehingga flag tinggi ditambah flag rendah sama dengan jumlah ulasan"],
  ], [2600, PAGE_W - 2600]),

  h2("2.3 DW 3: Analisis Keterlambatan Pengembalian"),
  para([b("Proses bisnis: "), "pengembalian buku oleh peminjam terhadap tanggal jatuh tempo."]),
  para([b("Grain: "), "satu baris adalah satu kewajiban pengembalian yang sudah jatuh tempo, yaitu peminjaman berstatus Dikembalikan, atau Dipinjam yang sudah melewati jatuh tempo. Pengembalian tepat waktu ikut disimpan agar persentase keterlambatan dapat dihitung, bukan hanya jumlahnya."]),
  para("Karena fokus DW 3 adalah bulan dengan keterlambatan terbanyak, dimensi Waktu ditempatkan sebagai sumbu X. Buku dianalisis pada tingkat kategori, sehingga DW 3 memakai dimensi kategori, bukan dimensi judul buku."),
  tblTitle("Tabel 6. Dimensi DW 3"),
  table(["Peran", "Tabel dimensi", "Atribut dan hierarki"], [
    ["X", "dim_waktu_jatuh_tempo", "Tanggal jatuh tempo, bulan, kuartal, tahun. Hierarki: tanggal → bulan → kuartal → tahun"],
    ["Y", "dim_kategori_buku", "Nama kategori buku"],
    ["Z", "dim_peminjam_kembali", "NIM/NIP, nama, kelompok peminjam"],
    ["Khas", "dim_status_kembali", "Status pengembalian (Tepat Waktu, Terlambat), posisi buku (Sudah Kembali, Belum Kembali), status denda (Tidak Ada Denda, Lunas, Belum Lunas)"],
  ], [800, 2100, PAGE_W - 2900], ["c"]),
  tblTitle("Tabel 7. Fakta DW 3 (fact_keterlambatan)"),
  table(["Kolom", "Keterangan"], [
    ["keterlambatan_key", "Primary key (surrogate key AUTO_INCREMENT)"],
    ["loan_id", "Nomor peminjaman asli, berindeks unik, untuk penelusuran ke sumber"],
    ["flag_terlambat", "Ukuran. 1 bila buku dikembalikan (atau belum dikembalikan) setelah jatuh tempo"],
    ["flag_tepat_waktu", "Ukuran. 1 bila buku dikembalikan paling lambat pada tanggal jatuh tempo"],
    ["hari_terlambat", "Ukuran. Tanggal kembali dikurangi jatuh tempo; bila belum kembali, tanggal ETL dikurangi jatuh tempo"],
    ["nominal_denda", "Ukuran. Hari terlambat dikali Rp1.000 (sesuai aturan aplikasi). Nilai ini tidak tersimpan di database sumber, sehingga dihitung saat ETL"],
  ], [2600, PAGE_W - 2600]),

  h2("2.4 Perbandingan Ketiga Data Warehouse"),
  para("Setiap DW berdiri sendiri: proses bisnis, grain, ukuran, dan seluruh tabel dimensinya berbeda. Dengan demikian, setiap tabel fakta menghasilkan informasi yang berbeda dan perubahan pada satu DW tidak memengaruhi DW lain. Bila hasil dua DW perlu dibandingkan (misalnya jumlah dipinjam dan rata-rata rating per buku), hasil tiap DW diringkas lebih dulu, lalu dicocokkan melalui kunci asli book_id."),
  tblTitle("Tabel 8. Perbandingan DW 1, DW 2, dan DW 3"),
  table(["", "DW 1", "DW 2", "DW 3"], [
    ["Fokus", "Volume peminjaman", "Kepuasan pembaca", "Kepatuhan pengembalian"],
    ["Sumber", "loans", "reviews", "loans (denda dihitung)"],
    ["Grain", "1 pengajuan peminjaman", "1 ulasan", "1 kewajiban pengembalian"],
    ["X", "dim_buku_pinjam", "dim_buku_ulasan", "dim_waktu_jatuh_tempo"],
    ["Y", "dim_waktu_pengajuan", "dim_waktu_ulasan", "dim_kategori_buku"],
    ["Z", "dim_peminjam_pinjam", "dim_pengulas", "dim_peminjam_kembali"],
    ["Dimensi khas", "dim_status_pinjam", "dim_rating", "dim_status_kembali"],
    ["Ukuran utama", "Jumlah peminjaman", "Rating (tinggi, rendah)", "Terlambat, hari terlambat, denda"],
  ], [1700, 2400, 2200, PAGE_W - 6300]),

  // ---------------- 3. DESAIN SKEMA ----------------
  h1("3. Desain Skema"),
  h2("3.1 Pemilihan Skema"),
  para("Ketiga DW memakai star schema yang berdiri sendiri. Setiap tabel fakta terhubung langsung ke empat tabel dimensi miliknya tanpa tabel perantara. Atribut seperti kategori disimpan langsung di dalam tabel dimensi (didenormalisasi), sehingga query cukup melakukan satu kali join untuk setiap dimensi. Karena tidak ada tabel dimensi yang dipakai bersama, ketiga skema tidak membentuk fact constellation."),
  tblTitle("Tabel 9. Pemilihan skema"),
  table(["DW", "Skema", "Alasan"], [
    ["DW 1", "Star", "fact_peminjaman terhubung langsung ke dim_buku_pinjam, dim_waktu_pengajuan, dim_peminjam_pinjam, dan dim_status_pinjam. Kategori menjadi atribut buku, sehingga tidak perlu snowflake."],
    ["DW 2", "Star", "fact_ulasan terhubung langsung ke dim_buku_ulasan, dim_waktu_ulasan, dim_pengulas, dan dim_rating. Satu ulasan hanya menilai satu buku, jadi tidak ada relasi many-to-many."],
    ["DW 3", "Star", "fact_keterlambatan terhubung langsung ke dim_waktu_jatuh_tempo, dim_kategori_buku, dim_peminjam_kembali, dan dim_status_kembali."],
  ], [1300, 1200, PAGE_W - 2500]),
  para("Gambar design schema setiap DW disajikan di bawah ini. Teks ungu tebal pada tabel fakta menandai ukuran (measure), dan kotak oranye menandai dimensi khas.", { spacing: { before: 160, after: 120 } }),
  img("skema-dw1-peminjaman-buku.png", 600),
  caption("Gambar 1. Design star schema DW 1 (Analisis Peminjaman Buku)"),
  img("skema-dw2-kepuasan-pembaca.png", 600),
  caption("Gambar 2. Design star schema DW 2 (Analisis Kepuasan Pembaca)"),
  img("skema-dw3-keterlambatan.png", 600),
  caption("Gambar 3. Design star schema DW 3 (Analisis Keterlambatan Pengembalian)"),
  img("skema-ringkasan-tiga-dw.png", 600),
  caption("Gambar 4. Ringkasan tiga star schema: setiap tabel fakta memiliki empat tabel dimensi sendiri"),

  // ---------------- 4. INFORMATION PACKAGE ----------------
  h1("4. Information Package"),
  para("Information package menyajikan fakta yang diiris menurut ketiga dimensinya: kuartal dan item pada baris, nilai dimensi Z (kelompok peminjam) pada kolom, lengkap dengan subtotal per kuartal dan total keseluruhan. Kolom Lainnya berisi dua akun uji pada data asli yang isi kelasnya tidak dapat dikenali (\"s\" dan \"QA\"); kolom ini ditampilkan agar total setiap baris tetap sesuai."),

  h2("4.1 DW 1: Peminjaman berhasil per kuartal, kategori, dan kelompok peminjam"),
  para("Fakta: jumlah peminjaman berhasil. Dimensi: Kategori (X, Buku), Kuartal (Y, Waktu pengajuan), dan Kelompok peminjam (Z, Peminjam)."),
  tblTitle("Tabel 10. Analisis peminjaman buku"),
  tabelDw1(),
  para(`Sepanjang periode terdapat ${t1.total} peminjaman berhasil. Komputer & Teknologi menjadi kategori yang paling sering dipinjam pada kuartal 1 sampai 3 dan menjadi kategori favorit di keempat kelompok peminjam. Kelas 10 adalah kelompok paling aktif (${t1.kelas_10} peminjaman), disusul Kelas 11 dan Kelas 12 (masing-masing ${t1.kelas_11}). Buku yang paling sering dipinjam adalah "${top1.judul}" (${top1.total_dipinjam} kali). Kuartal 4 hanya berisi data asli sampai Oktober, sehingga jumlahnya jauh lebih kecil.`, { spacing: { before: 160, after: 120, line: 288 } }),

  h2("4.2 DW 2: Rata-rata rating per kuartal, kategori, dan kelompok peminjam"),
  para("Fakta: rata-rata rating dan jumlah ulasan. Dimensi: Kategori (X, Buku), Kuartal (Y, Waktu ulasan), dan Kelompok peminjam (Z, Peminjam). Nilai pada kolom kelompok adalah rata-rata rating (skala 1 sampai 5); tanda - berarti tidak ada ulasan."),
  tblTitle("Tabel 11. Analisis kepuasan pembaca"),
  tabelDw2(),
  para(`Dari ${t2.jumlah_ulasan} ulasan, rata-rata rating keseluruhan adalah ${dec(t2.rata_rating)}: ${sebaran.tinggi} ulasan berating tinggi (${pct(100*sebaran.tinggi/t2.jumlah_ulasan)}) dan ${sebaran.rendah} berating rendah (${pct(100*sebaran.rendah/t2.jumlah_ulasan)}). Di antara keempat kelompok peminjam, Kelas 12 memberi rating rata-rata tertinggi (${dec(kel2["Kelas 12"].rata_rating)}), sedangkan Kelas 11 paling rendah (${dec(kel2["Kelas 11"].rata_rating)}) dengan ${pct(kel2["Kelas 11"].persen_rating_rendah)} ulasan berating rendah. Buku dengan kepuasan tertinggi (minimal tiga ulasan) adalah "${topRating.judul}" (${dec(topRating.rata_rating)}, seluruh ${topRating.jumlah_ulasan} ulasan berating tinggi). Sebaliknya, "${top1.judul}" yang paling sering dipinjam hanya mendapat rata-rata ${dec(laut.rata_rating)} dan ${laut.rating_rendah} rating rendah. Hal ini menunjukkan bahwa buku yang populer belum tentu paling memuaskan, sehingga DW 2 memberi informasi yang tidak tersedia di DW 1.`, { spacing: { before: 160, after: 120, line: 288 } }),

  h2("4.3 DW 3: Keterlambatan per bulan jatuh tempo, kelompok peminjam, dan kategori"),
  para("Fakta: kasus terlambat, kewajiban pengembalian, dan persentase terlambat. Dimensi: Bulan jatuh tempo (X, Waktu), Kelompok peminjam (Z, Peminjam) pada Tabel 12, dan Kategori (Y, Buku) pada Tabel 13. Nilai pada kolom kelompok adalah jumlah kasus terlambat."),
  tblTitle("Tabel 12. Keterlambatan per bulan jatuh tempo dan kelompok peminjam"),
  tabelDw3(),
  tblTitle("Tabel 13. Keterlambatan per kategori buku"),
  tabelDw3Kategori(),
  para(`Dari ${t3.kewajiban_kembali} kewajiban pengembalian, ${t3.total_terlambat} terlambat (${pct(t3.persen_terlambat)}). Di antara bulan dengan data lengkap, September memiliki keterlambatan terbanyak (${sep.total_terlambat} kasus, ${pct(sep.persen_terlambat)}). Oktober terlihat paling tinggi (${okt.total_terlambat} dari ${okt.kewajiban_kembali} kewajiban), tetapi sebagian besar berasal dari peminjaman data asli yang sampai tanggal ETL belum dikembalikan, sehingga hari terlambat dan dendanya masih terus bertambah. Kategori dengan persentase keterlambatan tertinggi adalah ${kat3.nama_kategori} (${pct(kat3.persen_terlambat)}) dan ${kat3b.nama_kategori} (${pct(kat3b.persen_terlambat)}). Di antara kelompok peminjam, Kelas 12 paling sering terlambat (${kel3["Kelas 12"].terlambat} kasus, ${pct(kel3["Kelas 12"].persen_terlambat)}), sedangkan tenaga pendidik ${pct(kel3["Tenaga Pendidik"].persen_terlambat)}.`, { spacing: { before: 160, after: 120, line: 288 } }),

  h2("4.4 Analisis dimensi khas setiap DW"),
  para("Setiap DW memiliki satu dimensi yang tidak dimiliki DW lain. Tabel berikut menunjukkan informasi tambahan yang hanya dapat dijawab oleh dimensi khas tersebut."),
  tblTitle("Tabel 14. DW 1: pengajuan menurut status peminjaman (dim_status_pinjam)"),
  table(["Status", "Kelompok status", "Pengajuan", "Persentase"],
    D.dw1_status.map(r => [r.nama_status, r.kelompok_status, r.pengajuan, pct(r.persen)]),
    [2600, 2600, 1900, PAGE_W - 7100], ["l", "l", "c", "c"]),
  tblTitle("Tabel 15. DW 2: ulasan menurut skala rating (dim_rating)"),
  table(["Rating", "Label", "Kelompok rating", "Jumlah ulasan", "Persentase"],
    D.dw2_rating.map(r => [r.nilai, r.label_rating, r.kelompok_rating, r.jumlah_ulasan, pct(r.persen)]),
    [1100, 2400, 2000, 1800, PAGE_W - 7300], ["c", "l", "l", "c", "c"]),
  tblTitle("Tabel 16. DW 3: kewajiban pengembalian menurut status pengembalian (dim_status_kembali)"),
  table(["Status pengembalian", "Posisi buku", "Status denda", "Kewajiban", "Nominal denda"],
    D.dw3_status.map(r => [r.status_pengembalian, r.posisi_buku, r.status_denda, r.kewajiban_kembali, rp(r.total_denda)]),
    [2100, 1900, 1900, 1300, PAGE_W - 7200], ["l", "l", "l", "c", "r"]),
  para("Dimensi khas ini menambah sudut pandang yang berbeda untuk tiap DW: DW 1 memperlihatkan berapa pengajuan yang masih diproses atau berakhir gagal, DW 2 memperlihatkan sebaran penilaian pembaca dari sangat kecewa sampai sangat puas, dan DW 3 memisahkan keterlambatan yang dendanya sudah lunas dari yang masih tertunggak, termasuk buku yang belum dikembalikan.", { spacing: { before: 160, after: 120, line: 288 } }),

  // ---------------- 5. KESIMPULAN ----------------
  h1("5. Kesimpulan"),
  para("Perancangan data warehouse Smart Reading Room mengubah data transaksi operasional menjadi informasi analitis untuk evaluasi layanan perpustakaan. Tiga data warehouse dibangun dengan fokus dan grain yang berbeda, yaitu analisis peminjaman buku (satu baris per pengajuan peminjaman), analisis kepuasan pembaca (satu baris per ulasan), dan analisis keterlambatan pengembalian (satu baris per kewajiban pengembalian yang sudah jatuh tempo)."),
  para("Ketiganya memakai star schema yang berdiri sendiri: setiap tabel fakta memiliki empat tabel dimensi miliknya, termasuk satu dimensi khas (status peminjaman, rating, dan status pengembalian), sehingga setiap DW menghasilkan informasi yang berbeda dan tidak saling bergantung. Dari hasil analisis, admin dapat menambah stok buku kategori Komputer & Teknologi yang paling diminati, mempromosikan buku dengan rating tinggi, meninjau ulang buku yang sering mendapat rating rendah, dan memberi pengingat lebih awal kepada kelompok serta kategori yang paling sering terlambat."),
];

const doc = new Document({
  creator: "Kelompok G",
  title: "Data Warehouse Smart Reading Room (Revisi)",
  styles: {
    default: { document: { run: { font: FONT, size: 22 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { font: FONT, size: 30, bold: true, color: UNGU },
        paragraph: { spacing: { before: 320, after: 140 }, keepNext: true, outlineLevel: 0 } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { font: FONT, size: 25, bold: true, color: INK },
        paragraph: { spacing: { before: 240, after: 100 }, keepNext: true, outlineLevel: 1 } },
    ],
  },
  numbering: { config: [{ reference: "poin", levels: [{ level: 0, format: LevelFormat.BULLET, text: "•",
    alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 540, hanging: 270 } } } }] }] },
  sections: [{
    properties: { page: { size: { width: 11906, height: 16838 }, margin: { top: 1300, bottom: 1300, left: 1440, right: 1440 } } },
    footers: { default: new Footer({ children: [new Paragraph({ alignment: AlignmentType.CENTER,
      children: [new TextRun({ children: [PageNumber.CURRENT], font: FONT, size: 18, color: ABU })] })] }) },
    children: [...cover, ...isi],
  }],
});

const out = path.join(HERE, "Laporan-Data-Warehouse-Smart-Reading-Room-Revisi.docx");
Packer.toBuffer(doc).then(buf => { fs.writeFileSync(out, buf); console.log("dibuat:", out); });
