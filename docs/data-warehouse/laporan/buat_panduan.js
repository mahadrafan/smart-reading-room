// Membuat Panduan-Pengerjaan-Implementasi-DW.docx
// Jalankan: node buat_panduan.js   (butuh paket npm "docx")
const fs = require("fs");
const path = require("path");
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, AlignmentType,
  HeadingLevel, WidthType, ShadingType, BorderStyle, LevelFormat, Header, Footer,
  PageNumber, VerticalAlign, PageBreak,
} = require("docx");

const FONT = "Calibri", MONO = "Consolas";
const UNGU = "4D0057", UNGU_MUDA = "EFE3F2", ABU = "767686", INK = "1F1F2B", MERAH = "9B1C31";
const W = 11906 - 2 * 1134; // lebar isi A4, margin 2 cm
const VERSI = "versi 22 November 2026";

// ---------------- helper teks ----------------
// teks bisa memakai **tebal** dan `kode`
function runs(text, o = {}) {
  const out = [];
  String(text).split(/(\*\*[^*]+\*\*|`[^`]+`)/).forEach(part => {
    if (!part) return;
    if (part.startsWith("**")) out.push(new TextRun({ text: part.slice(2, -2), bold: true, font: FONT, size: o.size || 21, color: o.color || INK }));
    else if (part.startsWith("`")) out.push(new TextRun({ text: part.slice(1, -1), font: MONO, size: (o.size || 21) - 2, color: UNGU }));
    else out.push(new TextRun({ text: part, font: FONT, size: o.size || 21, color: o.color || INK, bold: o.bold, italics: o.italics }));
  });
  return out;
}
const p = (t, o = {}) => new Paragraph({ children: runs(t, o), alignment: o.align || AlignmentType.JUSTIFIED,
  spacing: { after: o.after ?? 100, line: 276 }, keepNext: o.keepNext });
const h1 = t => new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun(t)] });
const h2 = t => new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun(t)] });
const bullet = t => new Paragraph({ children: runs(t), numbering: { reference: "poin", level: 0 }, spacing: { after: 40, line: 264 } });
const num = (t, ref = "angka") => new Paragraph({ children: runs(t), numbering: { reference: ref, level: 0 }, spacing: { after: 50, line: 264 } });
const code = lines => lines.map((l, i) => new Paragraph({
  shading: { fill: "F4F4F7", type: ShadingType.CLEAR, color: "auto" },
  spacing: { before: i === 0 ? 60 : 0, after: i === lines.length - 1 ? 120 : 0, line: 240 },
  indent: { left: 120, right: 120 }, keepNext: i < lines.length - 1,
  children: [new TextRun({ text: l || " ", font: MONO, size: 17, color: INK })],
}));
const spacer = (n = 80) => new Paragraph({ spacing: { after: n }, children: [] });

// ---------------- helper tabel ----------------
const bd = { style: BorderStyle.SINGLE, size: 4, color: "D9D9E3" };
const borders = { top: bd, bottom: bd, left: bd, right: bd };
function cell(content, width, o = {}) {
  const items = Array.isArray(content) ? content : [content];
  return new TableCell({
    width: { size: width, type: WidthType.DXA }, borders, verticalAlign: VerticalAlign.TOP,
    margins: { top: 60, bottom: 60, left: 100, right: 100 }, columnSpan: o.span,
    shading: o.fill ? { fill: o.fill, type: ShadingType.CLEAR, color: "auto" } : undefined,
    children: items.map(t => new Paragraph({ alignment: o.align || AlignmentType.LEFT, spacing: { after: 30 },
      children: runs(t, { size: 18, bold: o.bold, color: o.color }) })),
  });
}
function table(header, rows, widths) {
  const total = widths.reduce((a, b) => a + b, 0);
  const trs = [];
  if (header) trs.push(new TableRow({ tableHeader: true, cantSplit: true,
    children: header.map((h, i) => cell(h, widths[i], { fill: UNGU, bold: true, color: "FFFFFF" })) }));
  rows.forEach((r, k) => trs.push(new TableRow({ cantSplit: true,
    children: r.map((c, i) => cell(c, widths[i], { fill: k % 2 ? "F7F7FA" : undefined })) })));
  return new Table({ width: { size: total, type: WidthType.DXA }, columnWidths: widths, rows: trs });
}
// kotak spesifikasi: kolom kiri label, kolom kanan isi (seperti "Fungsi / Urutan / Selesai kalau")
function spec(title, owner, pairs) {
  const wL = 1700, wR = W - wL;
  const trs = [new TableRow({ cantSplit: true, children: [
    cell([`**${title}**`], wL + wR, { span: 2, fill: UNGU_MUDA }) ] })];
  pairs.forEach(([k, v]) => trs.push(new TableRow({ cantSplit: true,
    children: [cell(`**${k}**`, wL), cell(v, wR)] })));
  return [new Paragraph({ keepNext: true, spacing: { before: 140, after: 60 },
    children: runs(owner, { size: 19, color: UNGU, bold: true }) }),
    new Table({ width: { size: W, type: WidthType.DXA }, columnWidths: [wL, wR], rows: trs })];
}
// kotak prompt (satu sel berbayang, teks monospace kecil)
function promptBox(lines) {
  return new Table({ width: { size: W, type: WidthType.DXA }, columnWidths: [W], rows: [new TableRow({ children: [
    new TableCell({ width: { size: W, type: WidthType.DXA }, borders,
      shading: { fill: "F7F3F8", type: ShadingType.CLEAR, color: "auto" },
      margins: { top: 100, bottom: 100, left: 140, right: 140 },
      children: lines.map(l => new Paragraph({ spacing: { after: l ? 50 : 20 }, children: runs(l, { size: 18 }) })) }),
  ] })] });
}

// =====================================================================
// ISI
// =====================================================================
const anggota = [
  ["Rafan", "A", "Database DW dan data dummy; koordinasi dan integrasi akhir"],
  ["Irma", "B1", "Pentaho: koneksi, variabel, tiga transformasi dimensi (satu per DW), dan job utama"],
  ["Isma", "B2", "Pentaho: tiga transformasi fakta (DW 1, DW 2, DW 3) dan validasi"],
  ["Vincent", "C1", "Dashboard: lapisan data (view), filter, halaman ringkasan dan DW 1"],
  ["Mako", "C2", "Dashboard: halaman DW 2 dan DW 3; dokumentasi dan laporan implementasi"],
];

const isi = [];
const add = (...xs) => xs.flat().forEach(x => isi.push(x));

// ---------- judul ----------
add(
  new Paragraph({ spacing: { after: 40 }, children: [new TextRun({ text: "Panduan Pengerjaan Implementasi Data Warehouse", font: FONT, size: 40, bold: true, color: UNGU })] }),
  new Paragraph({ spacing: { after: 160 }, children: runs(`Smart Reading Room · Kelompok G · Mata Kuliah Basis Data · ${VERSI}`, { size: 21, color: ABU }) }),
  table(null, [
    ["**Lingkup**", "(A) database khusus data warehouse dengan data dummy · (B) ETL tiga data warehouse di Pentaho Data Integration · (C) dashboard analitikal untuk admin"],
    ["**Acuan rancangan**", "`01_ddl_data_warehouse.sql` sampai `04_query_analisis.sql` dan gambar skema fact constellation di `docs/data-warehouse/`"],
    ["**Alat**", "MySQL 8 (XAMPP) · Pentaho Data Integration (Spoon, Kitchen) · dashboard di halaman admin Laravel dengan Chart.js (D7)"],
    ["**Pembagian**", anggota.map(a => `**${a[0]}** (20%) · ${a[1]} · ${a[2]}`)],
  ], [1900, W - 1900]),
  spacer(60),
  p("**Tujuan akhir:** satu alur yang dapat dijalankan ulang dari nol: database sumber berisi data dummy → ETL Pentaho mengisi `basdat_dw` → dashboard admin membaca `basdat_dw`. Angka di setiap tahap harus sama dengan acuan ETL SQL yang sudah teruji. Panduan ini meminjam sistematika panduan esai kelompok lain; isi dan rancangan sepenuhnya milik Kelompok G."),
);

// ---------- 0. alur besar ----------
add(h1("0. Alur besar"));
const tahap = [
  ["1 Persiapan", "salin berkas revisi, instal Pentaho versi yang sama (D2), siapkan halaman admin dashboard"],
  ["2 Database", "database sumber dummy, `basdat_dw`, angka acuan"],
  ["3 ETL Pentaho", "dimensi → fakta → job"],
  ["4 Validasi", "rekonsiliasi Pentaho vs acuan SQL"],
  ["5 Dashboard", "view agregasi, halaman DW 1–3"],
  ["6 Integrasi", "demo dari nol, laporan, presentasi"],
];
const wT = Math.floor(W / 6);
add(new Table({ width: { size: wT * 6, type: WidthType.DXA }, columnWidths: Array(6).fill(wT), rows: [
  new TableRow({ children: tahap.map(t => cell(`**${t[0]}**`, wT, { fill: UNGU_MUDA, align: AlignmentType.CENTER })) }),
  new TableRow({ children: tahap.map(t => cell(t[1], wT, { align: AlignmentType.CENTER })) }),
] }));
add(p("**Kerja paralel:** tabel `basdat_dw` hasil ETL SQL (Tahap 2) strukturnya sama persis dengan hasil Pentaho. Karena itu Vincent dan Mako dapat mulai membangun dashboard di atas data acuan SQL tanpa menunggu Pentaho selesai. Saat Pentaho siap, dashboard cukup diarahkan ulang ke hasil Pentaho.", { after: 140 }));

add(h2("5 risiko yang bisa menjatuhkan nilai (wajib dipahami semua anggota)"));
[
  "**Hasil Pentaho tidak sama dengan rancangan.** Transformasi dibuat dengan logika sendiri sehingga jumlah baris atau nilai ukuran berbeda. Solusi: `03_etl_data_warehouse.sql` adalah acuan; setiap transformasi wajib lolos rekonsiliasi jumlah baris dan total ukuran (Bagian 4, Tahap 4).",
  "**Baris hilang diam-diam saat lookup.** Step Database lookup menghasilkan key kosong bila `book_id`, `user_id`, atau `category_id` tidak ditemukan di dimensi DW yang bersangkutan, lalu baris terbuang atau ETL gagal karena foreign key. Solusi: periksa Step Metrics setiap step, arahkan baris gagal lookup ke log, dan catat alasannya.",
  "**Data dummy mencemari database aplikasi.** Saat ini dummy dimasukkan ke `basdat_github` lalu dihapus dengan `02b`. Satu kelalaian dapat merusak data aplikasi. Solusi: database sumber terpisah khusus DW (keputusan D3).",
  "**Hanya jalan di laptop pembuatnya.** Path absolut, nama koneksi berbeda, password di dalam file, atau berkas yang tidak di-commit. Solusi: semua berkas di repo, koneksi memakai variabel, dan diuji di laptop anggota lain.",
  "**Dashboard tidak membaca data warehouse.** Grafik mengambil data dari `basdat_github` atau angka disalin manual. Ini menggugurkan tujuan DW. Solusi: dashboard hanya terhubung ke `basdat_dw`, dan setiap grafik dapat ditelusuri ke satu query atau view.",
].forEach(t => add(num(t, "risiko")));

// ---------- 1. kesepakatan ----------
add(h1("1. Kesepakatan kelompok (berlaku untuk semua)"));
add(table(["Struktur folder di repo (`docs/data-warehouse/`)", "Aturan teknis"], [[
  ["`sql/` 01–04 (sudah ada) + `05_view_dashboard.sql`", "`sumber-dummy/` skrip database sumber dan data dummy",
   "`pentaho/` `tr_*.ktr`, `job_etl_dw.kjb`, `kettle.properties.contoh`", "`dashboard/` berkas atau kode dashboard",
   "`laporan/` laporan implementasi, tangkapan layar", "`log-uji.xlsx` log uji bersama"],
  ["• Rancangan DW (tabel, kolom, grain, X/Y/Z) tidak diubah tanpa persetujuan kelompok; perubahan dicatat di log.",
   "• Koneksi bersama di Pentaho: `src_sumber` dan `dw_target`.",
   "• Variabel: `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `SRC_DB`, `DW_DB`, `TGL_ETL`, `TARIF_DENDA`.",
   "• Password hanya di `kettle.properties` lokal, tidak di repo.",
   "• Penamaan: `tr_dim_dw1`–`tr_dim_dw3`, `tr_fact_*`, `job_etl_dw`; setiap step diberi nama yang bermakna.",
   "• Commit setelah berhasil dijalankan; satu commit untuk satu perubahan.",
   "• AI hanya membantu; setiap angka di dashboard dan laporan berasal dari query yang benar-benar dijalankan."],
]], [W / 2, W / 2]));

add(h2("Log uji standar (satu baris per pengujian, diisi pemilik komponen)"));
add(table(["Kolom", "Isi"], [
  ["id_uji, komponen", "Nomor unik dan komponen (A, B1, B2, C1, C2)"],
  ["tanggal_dan_penguji", "Tanggal uji dan nama penguji; penguji sebaiknya bukan pembuat"],
  ["langkah", "Yang dijalankan (mis. `job_etl_dw` di laptop Mako dengan `TGL_ETL = 2026-11-19`)"],
  ["diharapkan · aktual", "Nilai acuan (mis. `fact_ulasan` 95 baris) dan nilai yang muncul"],
  ["status", "lolos / gagal + tindak lanjut"],
], [2600, W - 2600]));

add(h2("Daftar istilah baku"));
add(table(["Istilah", "Definisi kerja"], [
  ["grain", "Arti satu baris tabel fakta (mis. 1 pengajuan peminjaman)"],
  ["surrogate key · natural key", "Kunci buatan DW (`buku_key`) · kunci asli aplikasi (`book_id`)"],
  ["dimensi khas", "Dimensi yang hanya dimiliki satu DW: `dim_status_pinjam` (DW 1), `dim_rating` (DW 2), `dim_status_kembali` (DW 3)"],
  ["fact constellation", "Beberapa tabel fakta yang berbagi minimal satu tabel dimensi. Di sini ketiga fakta berbagi `dim_waktu`; dimensi lain milik masing-masing DW"],
  ["role-playing dimension", "Satu tabel dimensi yang dipakai dengan peran berbeda: `dim_waktu` sebagai tanggal pengajuan (DW 1), tanggal ulasan (DW 2), dan tanggal jatuh tempo (DW 3)"],
  ["transformasi · job", "Berkas `.ktr` (aliran data satu tabel) · berkas `.kjb` (urutan menjalankan transformasi)"],
  ["acuan SQL", "Isi `basdat_dw` hasil `03_etl_data_warehouse.sql` pada database dan `TGL_ETL` yang sama"],
  ["rekonsiliasi", "Membandingkan jumlah baris dan total ukuran antara sumber, acuan SQL, dan hasil Pentaho"],
  ["tingkat · kelas", "Hierarki sumbu Z (siswa): tingkat Kelas 10/11/12 → kelas/rombel (mis. X-1, XII IPA 2). Peminjam yang dianalisis hanya siswa"],
], [2600, W - 2600]));

add(h2("Keputusan kelompok (D1–D8)"));
add(table(["No", "Pertanyaan", "Keputusan", "Status", "PIC"], [
  ["D1", "Ketentuan tugas: bentuk pengumpulan, tenggat, presentasi, rubrik", "Ditunda karena waktu dari dosen belum pasti. Sementara tenggat tiap tahap memakai perkiraan kelompok", "Belum", "Semua"],
  ["D2", "Versi Pentaho dan Java", "Semua anggota memakai versi yang sama: satu versi PDI Community dan JDK 11. Nomor versi dicatat Irma di panduan instalasi", "Disepakati", "Irma"],
  ["D3", "Database sumber untuk ETL", "Database baru `basdat_sumber` (salinan struktur `basdat_github` + data dummy); `basdat_github` tidak disentuh", "Disepakati", "Rafan"],
  ["D4", "Volume dan periode data dummy", "Januari 2025–Desember 2026 agar ada pembanding antartahun; ±60 siswa, ±1.000 peminjaman, ±400 ulasan", "Disepakati", "Rafan"],
  ["D5", "Peminjam yang dianalisis (sumbu Z)", "Hanya siswa, dengan hierarki tingkat → kelas; transaksi tenaga pendidik tidak dimuat ke DW. Tersisa: apakah pilihan Tenaga Pendidik di aplikasi Laravel juga dihapus", "Disepakati", "Semua"],
  ["D6", "Normalisasi dimensi (snowflake)", "Tidak dinormalisasi: fact constellation dengan dimensi datar (rancangan `01_ddl` sekarang). Gambar versi snowflake (`gambar/normalisasi-*.png`) hanya pembanding", "Disepakati", "Semua"],
  ["D7", "Alat dashboard", "Halaman admin di aplikasi Laravel (Chart.js, koneksi kedua ke `basdat_dw`) sehingga hanya dapat dibuka setelah login admin", "Disepakati", "Vincent, Mako"],
  ["D8", "Cara menjalankan ETL", "Manual lewat Spoon saat pengembangan; Kitchen (baris perintah) untuk demo", "Disepakati", "Irma"],
], [600, 2300, W - 600 - 2300 - 1250 - 1200, 1250, 1200]));
add(p("D2–D8 disepakati pada 22 November 2026 dan berlaku untuk seluruh tahap; perubahan berikutnya harus disetujui kelompok. D1 diisi begitu dosen mengumumkan ketentuan dan tenggat.", { after: 60 }));

// ---------- 2. evaluasi ----------
add(h1("2. Evaluasi kondisi saat ini (dasar seluruh panduan ini)"));
add(p("**Yang sudah ada dan dipertahankan:** rancangan tiga DW sebagai fact constellation (ketiga fakta berbagi `dim_waktu`; buku, peminjam, dan dimensi khas milik tiap DW; PK fakta berupa surrogate key); skrip `01`–`04` yang sudah diuji pada salinan database tanggal 22 November 2026 (13 tabel terbentuk, jumlah baris DW sama dengan sumber); gambar skema. Kelemahan berikut perlu ditangani:"));
add(table(["No", "Temuan", "Mengapa penting", "Ditangani di", "PIC"], [
  ["E1", "Berkas revisi DW masih di folder worktree, belum di folder proyek utama dan GitHub", "Anggota lain akan memakai rancangan lama (snowflake, DW Analisis Peminjam)", "Tahap 1", "Rafan"],
  ["E2", "Data dummy dimasukkan ke `basdat_github` (database aplikasi)", "Berisiko merusak data aplikasi; ETL bergantung pada pembersihan manual", "D3, Tahap 2", "Rafan"],
  ["E3", "Data dummy hanya Januari–September 2026; Q4 berisi data asli saja", "Grafik tren timpang; tidak ada pembanding antartahun di dashboard", "D4, Tahap 2", "Rafan"],
  ["E4", "Aplikasi masih menerima peminjam Tenaga Pendidik walau DW hanya memuat siswa", "Transaksi tendik di aplikasi tidak muncul di dashboard", "Sisa D5", "Semua"],
  ["E5", "`fact_keterlambatan` bergantung pada tanggal ETL (peminjaman yang belum kembali)", "Jumlah baris dan denda berubah setiap hari; rekonsiliasi bisa tampak gagal", "Variabel `TGL_ETL`", "Isma"],
  ["E6", "Dua akun uji (kelas `s` dan `QA`) masuk tingkat \"Lainnya\"", "Kolom tambahan membingungkan di dashboard", "Data dummy baru", "Rafan"],
  ["E7", "Belum ada view agregasi untuk dashboard; query `04` memakai ROLLUP untuk tabel laporan", "Grafik butuh data per bulan/kategori yang siap pakai", "`05_view_dashboard.sql`", "Vincent"],
  ["E8", "Belum ada berkas Pentaho dan dashboard sama sekali", "Inti progres yang diminta", "Tahap 3–5", "Irma, Isma, Vincent, Mako"],
  ["E9", "Laporan DW revisi belum mengikuti fact constellation (masih tiga star terpisah)", "Isi laporan berbeda dengan DDL dan gambar skema terbaru", "Setelah Tahap 4", "Mako"],
], [600, 2500, 3000, 1700, W - 600 - 2500 - 3000 - 1700]));

// ---------- 3. panduan teknis ----------
add(h1("3. Panduan teknis dan peta keterkaitan"));
add(h2("Rancangan yang wajib diikuti (tidak diubah tanpa keputusan kelompok)"));
add(table(["", "DW 1 Peminjaman Buku", "DW 2 Kepuasan Pembaca", "DW 3 Keterlambatan"], [
  ["Tabel fakta", "`fact_peminjaman`", "`fact_ulasan`", "`fact_keterlambatan`"],
  ["Sumber", "`loans` (semua status)", "`reviews` (rating 1–5)", "`loans` yang sudah jatuh tempo"],
  ["Grain", "1 pengajuan peminjaman", "1 ulasan", "1 kewajiban pengembalian"],
  ["PK fakta", "`peminjaman_key` (+ `loan_id` unik)", "`ulasan_key` (+ `review_id` unik)", "`keterlambatan_key` (+ `loan_id` unik)"],
  ["X", "`dim_buku_pinjam`", "`dim_buku_ulasan`", "`dim_waktu` (peran jatuh tempo)"],
  ["Y", "`dim_waktu` (peran pengajuan)", "`dim_waktu` (peran ulasan)", "`dim_kategori_buku`"],
  ["FK waktu di fakta", "`waktu_pengajuan_key`", "`waktu_ulasan_key`", "`waktu_jatuh_tempo_key`"],
  ["Z", "`dim_peminjam_pinjam`", "`dim_pengulas`", "`dim_peminjam_kembali`"],
  ["Dimensi khas", "`dim_status_pinjam`", "`dim_rating`", "`dim_status_kembali`"],
  ["Ukuran", "jumlah_pengajuan, jumlah_berhasil, jumlah_gagal", "rating, jumlah_ulasan, flag_rating_tinggi, flag_rating_rendah", "flag_terlambat, flag_tepat_waktu, hari_terlambat, nominal_denda"],
], [1500, (W - 1500) / 3, (W - 1500) / 3, (W - 1500) / 3]));
add(p("Skema keseluruhan adalah **fact constellation**: `dim_waktu` satu-satunya dimensi bersama dan dimuat sekali untuk ketiga fakta. Nama FK di fakta tetap menyebut perannya. Gambar: `gambar/skema-fact-constellation.png` dan skema per DW di folder yang sama.", { after: 60 }));

add(h2("Aturan transformasi (rumus harus identik dengan 03_etl_data_warehouse.sql)"));
add(table(["Aturan", "Rumus"], [
  ["Kunci waktu", "`waktu_key` = tanggal dalam format `yyyyMMdd` sebagai angka; tanggal kosong → 0"],
  ["Siswa saja", "Hanya `users` dengan role Peminjam dan `borrower_type` Siswa (atau kosong). Tenaga pendidik tidak masuk dimensi peminjam, sehingga peminjaman/ulasannya otomatis tidak masuk fakta (INNER JOIN); jumlahnya dicatat di rekonsiliasi"],
  ["tingkat · kelas", "kelas = `users.class` (kosong → Tidak diketahui); tingkat: kelas diawali XII/12 → Kelas 12; XI/11 → Kelas 11; X/10 → Kelas 10 (XII dicek lebih dulu); selain itu Lainnya. `nis` diambil dari `users.nim_nip`"],
  ["DW 1", "berhasil = status Dipinjam atau Dikembalikan; gagal = status Gagal; pengajuan = 1"],
  ["DW 2", "hanya rating 1–5; tinggi = rating ≥ 3; rendah = rating ≤ 2; jumlah_ulasan = 1"],
  ["DW 3 (filter)", "status Dikembalikan dengan `return_date` terisi, atau status Dipinjam dengan `due_date` < `TGL_ETL`"],
  ["Dimensi khas", "status pinjam: 1 Menunggu, 2 Dikonfirmasi, 3 Dipinjam, 4 Dikembalikan, 5 Gagal · rating_key = nilai rating · status kembali: 1 tepat waktu; 2/3 terlambat sudah kembali (lunas/belum); 4/5 terlambat belum kembali (belum lunas/lunas)"],
  ["DW 3 (ukuran)", "hari = maks(0, (`return_date` atau `TGL_ETL`) − `due_date`); terlambat = hari > 0; tepat waktu = hari = 0; denda = hari × `TARIF_DENDA` (1.000)"],
  ["Rata-rata rating", "Tidak disimpan; dihitung saat query sebagai SUM(rating) / SUM(jumlah_ulasan)"],
], [2300, W - 2300]));

add(h2("Pertanyaan pemandu: apa yang harus dijawab setiap komponen"));
add(table(["Komponen", "Pertanyaan yang dijawab", "Menyambung ke"], [
  ["A Database", "Apakah DW dapat dibangun dari nol dengan urutan perintah yang tertulis, tanpa menyentuh database aplikasi?", "B1, B2, C1"],
  ["B1 Dimensi dan job", "Apakah setiap dimensi terisi lengkap dengan surrogate key yang benar, dan satu job menjalankan semuanya berurutan?", "B2"],
  ["B2 Fakta", "Apakah setiap fakta memiliki jumlah baris dan total ukuran yang sama dengan acuan SQL?", "C1, C2"],
  ["C1 Dashboard (data, DW 1)", "Apakah admin dapat melihat buku/kategori paling diminati per tingkat dan kelas dan per periode, dengan angka yang dapat ditelusuri?", "C2"],
  ["C2 Dashboard (DW 2–3), laporan", "Apakah admin dapat melihat kepuasan pembaca dan pola keterlambatan, dan apakah seluruh alur terdokumentasi?", "Integrasi"],
], [2400, W - 2400 - 1500, 1500]));

add(h2("Peta keterkaitan antaranggota"));
add(table(["Dari", "Ke", "Yang harus selaras"], [
  ["Rafan (A)", "Irma (B1), Isma (B2)", "Nama database, nama kolom sumber, dan data dummy siap sebelum Pentaho dijalankan; angka acuan dibagikan"],
  ["Rafan (A)", "Vincent (C1)", "`basdat_dw` acuan SQL tersedia lebih awal agar dashboard bisa dimulai paralel"],
  ["Irma (B1)", "Isma (B2)", "Koneksi dan variabel bersama; dimensi terisi sebelum fakta; nama kolom key sama dengan DDL"],
  ["Isma (B2)", "Vincent (C1), Mako (C2)", "Tabel fakta hasil Pentaho lolos rekonsiliasi sebelum dashboard dialihkan ke hasil Pentaho"],
  ["Vincent (C1)", "Mako (C2)", "View, filter global (tahun, kuartal, kategori, kelompok), dan gaya visual dipakai bersama"],
  ["Semua", "Mako (C2), Rafan", "Tangkapan layar dan angka di laporan diambil dari hasil terakhir; Rafan memeriksa integrasi"],
], [1800, 2200, W - 4000]));

// ---------- 4. langkah per tahap ----------
add(h1("4. Langkah per tahap"));
add(p("Format: siapa · tugas · selesai kalau. Tenggat tiap tahap ditetapkan setelah D1 pasti; sementara memakai perkiraan kelompok."));
const tahapTabel = (judul, rows) => { add(h2(judul)); add(table(["PIC", "Tugas", "Selesai kalau"], rows, [1300, W - 1300 - 3000, 3000])); };
tahapTabel("Tahap 1 – Persiapan", [
  ["Rafan", "Salin berkas DW revisi ke folder proyek utama, commit dan push; buat `log-uji.xlsx`; rapikan struktur folder sesuai Bagian 1.", "Semua anggota pull dan melihat `01`–`04` versi revisi"],
  ["Irma", "Instal Pentaho (D2), JDK 11, dan MySQL Connector/J (salin `.jar` ke folder `lib`); uji koneksi; tulis panduan instalasi satu halaman.", "Test connection berhasil di laptop Irma dan satu laptop lain"],
  ["Isma", "Pelajari bagian B `03_etl_data_warehouse.sql`; petakan setiap rumus SQL ke step Pentaho.", "Tabel pemetaan rumus SQL → step Pentaho"],
  ["Vincent", "Siapkan kerangka dashboard di halaman admin Laravel (D7): koneksi database kedua ke `basdat_dw`, route khusus admin, dan Chart.js; susun daftar indikator per DW (Bagian 5, C1).", "Halaman dashboard kosong hanya terbuka untuk admin; daftar indikator disetujui"],
  ["Mako", "Buat sketsa (wireframe) dashboard: halaman ringkasan, DW 1, DW 2, DW 3, dan filter.", "Sketsa disetujui kelompok"],
  ["Semua", "Pahami keputusan D2–D8; pantau pengumuman dosen untuk D1; sepakati daftar istilah baku.", "Semua anggota memakai keputusan yang sama; D1 diisi begitu ada"],
]);
tahapTabel("Tahap 2 – Database DW dan data dummy", [
  ["Rafan", "Buat database sumber dummy (D3) dan skrip data dummy baru (D4); jalankan `01_ddl` lalu `03_etl` SQL; simpan angka acuan (jumlah baris dan total ukuran) beserta `TGL_ETL`.", "Satu skrip membangun database dari nol; tabel angka acuan dibagikan"],
  ["Vincent", "Setelah `basdat_dw` acuan terisi, mulai menulis `05_view_dashboard.sql` (paralel dengan Tahap 3).", "View dapat dijalankan di `basdat_dw` acuan"],
]);
tahapTabel("Tahap 3 – ETL di Pentaho", [
  ["Irma", "Buat koneksi bersama dan variabel; buat `tr_dim_waktu` (dimensi bersama) serta `tr_dim_dw1`, `tr_dim_dw2`, `tr_dim_dw3` (masing-masing memuat tiga dimensi milik satu DW).", "Jumlah baris kesepuluh dimensi sama dengan acuan; tingkat dan kelas tiap siswa sama di ketiga DW; tidak ada tenaga pendidik di dimensi peminjam"],
  ["Isma", "Buat `tr_fact_peminjaman`, `tr_fact_ulasan`, `tr_fact_keterlambatan` (Bagian 5, B2).", "Jumlah baris dan SUM setiap ukuran sama dengan acuan"],
  ["Irma", "Buat `job_etl_dw.kjb`: kosongkan tabel → dimensi → fakta → query rekonsiliasi; uji lewat Spoon dan Kitchen.", "Satu perintah menjalankan semua transformasi tanpa error"],
]);
tahapTabel("Tahap 4 – Validasi", [
  ["Isma, Irma", "Jalankan bagian C `03_etl` (rekonsiliasi) dan `04_query_analisis.sql` pada hasil Pentaho; bandingkan dengan acuan SQL.", "Semua baris log uji berstatus lolos"],
  ["Rafan", "Jalankan job di laptop anggota lain dengan `kettle.properties` lokal.", "Job berhasil di laptop selain pembuatnya"],
]);
tahapTabel("Tahap 5 – Dashboard analitikal admin", [
  ["Vincent", "Hubungkan halaman admin Laravel ke `basdat_dw` (koneksi kedua); buat filter global, halaman ringkasan (KPI), dan halaman DW 1.", "Angka setiap panel sama dengan query `04`; hanya admin yang dapat membuka"],
  ["Mako", "Buat halaman DW 2 dan DW 3 memakai view dan filter dari Vincent.", "Angka setiap panel sama dengan query `04`"],
  ["Vincent, Mako", "Uji silang: masing-masing memeriksa panel milik yang lain.", "Log uji dashboard lolos"],
]);
tahapTabel("Tahap 6 – Integrasi, dokumentasi, presentasi", [
  ["Mako", "Tulis laporan implementasi: alur ETL Pentaho (tangkapan layar job dan transformasi), hasil rekonsiliasi, dan dashboard.", "Laporan lengkap sesuai D1"],
  ["Rafan", "Demo dari nol: database kosong → data dummy → job Pentaho → dashboard; periksa delapan pertanyaan integrasi di bawah.", "Delapan jawaban \"ya\""],
  ["Semua", "Latihan presentasi; setiap anggota mampu menjelaskan bagian anggota lain secara singkat.", "Gladi bersih selesai"],
]);
add(h2("Pertanyaan integrasi"));
add(table(["No", "Pertanyaan integrasi", "Periksa di"], [
  ["I1", "DW dapat dibangun dari nol dengan urutan perintah yang tertulis?", "A ↔ B1"],
  ["I2", "Struktur `basdat_dw` hasil Pentaho identik dengan `01_ddl`?", "A ↔ B1, B2"],
  ["I3", "Jumlah baris dan total ukuran hasil Pentaho sama dengan acuan SQL?", "B2 ↔ A"],
  ["I4", "Tingkat dan kelas siswa sama di `dim_peminjam_pinjam`, `dim_pengulas`, dan `dim_peminjam_kembali`, serta sama dengan SQL?", "B1, B2"],
  ["I5", "Dashboard hanya membaca `basdat_dw`?", "C1, C2"],
  ["I6", "Setiap angka di dashboard dapat ditelusuri ke satu view atau query?", "C1, C2 ↔ `04`"],
  ["I7", "Nama DW, istilah, dan label sama di Pentaho, dashboard, dan laporan?", "Semua"],
  ["I8", "Job dan dashboard berjalan di laptop selain pembuatnya?", "B1, C1"],
], [600, W - 600 - 1800, 1800]));

// ---------- 5. kerangka per tugas ----------
add(h1("5. Kerangka per tugas"));
add(p("Isi bagian ini adalah rancangan awal; pemilik tugas boleh menyempurnakannya pada Tahap 1, tetapi kolom \"Terhubung\" tidak diubah sepihak."));

add(spec("A · Database khusus data warehouse dan data dummy", "Rafan", [
  ["Fungsi", "Menyediakan sumber data yang realistis dan aman untuk ETL, serta angka acuan untuk validasi."],
  ["Isi", ["1) Database sumber baru `basdat_sumber` (D3) dengan struktur sama seperti `basdat_github` (`users`, `books`, `categories`, `loans`, `reviews`).",
    "2) Skrip data dummy baru (D4: Januari 2025–Desember 2026, ±60 siswa, ±1.000 peminjaman, ±400 ulasan): semua peminjam siswa (tanpa tenaga pendidik), tersebar merata di beberapa kelas per tingkat, nilai kelas yang dapat dikenali (tidak ada \"Lainnya\"), status peminjaman lengkap (Menunggu, Dikonfirmasi, Dipinjam, Dikembalikan, Gagal), sebagian terlambat dan belum kembali, rating 1–5. Deterministik (tanpa RAND) agar hasil sama di semua laptop.",
    "3) `basdat_dw` dari `01_ddl`; acuan diisi dengan `03_etl` SQL (ganti `basdat_github` menjadi `basdat_sumber`).",
    "4) Tabel angka acuan: jumlah baris semua tabel DW dan SUM setiap ukuran, dengan `TGL_ETL` tertulis."]],
  ["Terhubung", "Nama database dan kolom dipakai Irma dan Isma; `basdat_dw` acuan dipakai Vincent sejak Tahap 2."],
  ["Selesai kalau", "Skrip dapat dijalankan dua kali tanpa error; `basdat_github` tidak berubah; angka acuan tercatat di log uji."],
]));

add(spec("B1 · Pentaho: koneksi, dimensi, dan job", "Irma", [
  ["Fungsi", "Menyiapkan lingkungan Pentaho, memuat sepuluh tabel dimensi (`dim_waktu` bersama + tiga dimensi milik tiap DW), lalu merangkai seluruh transformasi dalam satu job."],
  ["Urutan step", [
    "`tr_dim_waktu` (dimensi bersama, dijalankan sekali): Generate rows (1.461 baris) → Add sequence (mulai 0) → Calculator (`2025-01-01` + n hari; tahun, bulan, kuartal, hari) → Value mapper (nama hari, nama bulan) → Select values (key = `yyyyMMdd`) → Table output `dim_waktu`; tambahkan satu baris key 0 (Data grid: Tidak diketahui) untuk tanggal kosong.",
    "Siswa: Table input (`users` dengan role Peminjam dan `borrower_type` Siswa atau kosong) → Formula (`tingkat` dari kelas, Bagian 3; `nim_nip` → `nis`) → Select values (buang email, password, telepon, jabatan).",
    "`tr_dim_dw1`: Table input (`books` LEFT JOIN `categories`) → Value mapper (`is_active`) → Table output `dim_buku_pinjam`; siswa → Table output `dim_peminjam_pinjam`; Data grid (5 status) → Table output `dim_status_pinjam`.",
    "`tr_dim_dw2`: Table input (`books` + `categories` + penulis digabung) → `dim_buku_ulasan`; siswa → `dim_pengulas`; Data grid (rating 1–5, label, kelompok) → `dim_rating`.",
    "`tr_dim_dw3`: Table input (`categories`) + baris Tanpa Kategori → `dim_kategori_buku`; siswa → `dim_peminjam_kembali`; Data grid (5 status kembali) → `dim_status_kembali`.",
    "`job_etl_dw.kjb`: Start → SQL (`SET FOREIGN_KEY_CHECKS=0`; TRUNCATE tiga belas tabel; `SET FOREIGN_KEY_CHECKS=1`) → `tr_dim_waktu` → `tr_dim_dw1`–`tr_dim_dw3` → tiga transformasi fakta → SQL rekonsiliasi → Success. Error di step mana pun → Write to log → Abort."]],
  ["Terhubung", "Koneksi, variabel, dan dimensi dipakai Isma; versi dan cara instalasi dipakai semua anggota."],
  ["Selesai kalau", "`dim_waktu` 1.462 baris (1.461 tanggal + key 0); dimensi buku, kategori, dan peminjam di ketiga DW sama jumlahnya dengan acuan; job berjalan lewat Spoon dan Kitchen; tidak ada path atau password tertulis di berkas."],
]));

add(spec("B2 · Pentaho: tiga transformasi fakta", "Isma", [
  ["Fungsi", "Memuat tabel fakta ketiga DW dengan rumus yang identik dengan acuan SQL. Setiap fakta mencari key di dimensi DW-nya sendiri, kecuali waktu yang selalu memakai `dim_waktu` bersama."],
  ["Urutan step", [
    "Pola umum: Table input (sumber) → Get variables (`TGL_ETL`, `TARIF_DENDA`) → Formula/Calculator (ukuran, key waktu `yyyyMMdd` yang langsung cocok dengan `dim_waktu.waktu_key`, key dimensi khas) → Database lookup ke dimensi DW tersebut → Filter rows (key kosong → log) → Select values → Table output (PK fakta dibiarkan diisi AUTO_INCREMENT).",
    "`tr_fact_peminjaman`: sumber `loans`; lookup `dim_buku_pinjam` (`book_id`), `dim_peminjam_pinjam` (`user_id`), `dim_status_pinjam` (`status`); ukuran pengajuan, berhasil, gagal; `waktu_pengajuan_key` dari `request_date`.",
    "`tr_fact_ulasan`: sumber `reviews` dengan rating 1–5; lookup `dim_buku_ulasan`, `dim_pengulas`; `rating_key` = rating; flag tinggi ≥ 3 dan rendah ≤ 2; `waktu_ulasan_key` dari `created_at` (kosong → 0).",
    "`tr_fact_keterlambatan`: sumber `loans` JOIN `books` dengan filter DW 3 (Bagian 3); lookup `dim_kategori_buku` (`category_id`), `dim_peminjam_kembali`; `status_kembali_key` dari hari terlambat, `return_date`, dan `fine_paid_at`; hari terlambat, flag, denda; `waktu_jatuh_tempo_key` dari `due_date`."]],
  ["Terhubung", "Bergantung pada dimensi dari Irma; hasilnya dipakai dashboard Vincent dan Mako."],
  ["Selesai kalau", "Jumlah baris dan SUM setiap ukuran sama dengan acuan pada `TGL_ETL` yang sama; sebaran dimensi khas (query 1.5, 2.5, 3.5) sama dengan acuan; tidak ada FK waktu yang tidak ditemukan di `dim_waktu`; jumlah baris yang terbuang di Filter rows = 0, atau dicatat alasannya."],
]));

add(spec("C1 · Dashboard: lapisan data, filter, ringkasan, dan DW 1", "Vincent", [
  ["Fungsi", "Menyediakan data siap pakai untuk grafik dan halaman utama dashboard admin."],
  ["Isi", ["1) `05_view_dashboard.sql`: view per DW, misalnya `v_peminjaman_bulanan`, `v_peminjaman_kategori_tingkat`, `v_ulasan_buku`, `v_keterlambatan_bulanan`; rata-rata rating dihitung SUM/SUM.",
    "2) Filter global: tahun, kuartal (dari `dim_waktu`, berlaku sama untuk ketiga DW), kategori buku, tingkat dan kelas siswa.",
    "3) Halaman ringkasan (KPI): total peminjaman berhasil, persentase berhasil, rata-rata rating, persentase terlambat, total denda; ditambah grafik gabungan per bulan (pengajuan DW 1, ulasan DW 2, keterlambatan DW 3) lewat `dim_waktu` bersama, dengan view `v_ringkasan_bulanan`.",
    "4) Halaman DW 1: tren peminjaman per bulan (garis), peminjaman per kategori × tingkat (batang bertumpuk, dapat dirinci per kelas), 10 buku terpopuler (tabel), kategori favorit tiap kelompok."]],
  ["Terhubung", "View dan filter dipakai Mako; angka dicocokkan dengan query `04` bagian 1."],
  ["Selesai kalau", "Setiap panel punya judul, periode, dan satuan; angka sama dengan query `04`; dashboard hanya terhubung ke `basdat_dw`."],
]));

add(spec("C2 · Dashboard DW 2 dan DW 3; dokumentasi", "Mako", [
  ["Fungsi", "Menampilkan kepuasan pembaca dan pola keterlambatan, lalu mendokumentasikan seluruh implementasi."],
  ["Isi", ["1) Halaman DW 2: rata-rata rating per kategori, proporsi rating tinggi/rendah per tingkat dan kelas, buku dengan rating tertinggi (minimal 3 ulasan), buku dengan rating rendah terbanyak, dan perbandingan \"sering dipinjam vs rating\" (hasil DW 1 dan DW 2 diringkas per buku lalu dicocokkan lewat `book_id`); sebaran rating per label (`dim_rating`).",
    "2) Halaman DW 3: persentase terlambat per bulan jatuh tempo, keterlambatan per kategori dan per kelompok, total denda, rata-rata hari terlambat.",
    "3) Laporan implementasi: alur ETL dengan tangkapan layar job dan transformasi, tabel rekonsiliasi, tangkapan layar dashboard, cara menjalankan."]],
  ["Terhubung", "Memakai view dan filter dari Vincent; tangkapan layar dari hasil akhir Irma dan Isma."],
  ["Selesai kalau", "Angka panel sama dengan query `04` bagian 2 dan 3; laporan memuat semua komponen A–C; tidak ada klaim \"tren nyata\" dari data dummy."],
]));

// ---------- 6. kriteria gagal ----------
add(h1("6. Kriteria gagal dan kesalahan yang dilarang"));
add(table(["Kriteria gagal: berhenti dan perbaiki dulu", "Kesalahan amatir: jangan dilakukan"], [[
  ["• Jumlah baris atau total ukuran hasil Pentaho berbeda dari acuan SQL.",
   "• Dashboard membaca `basdat_github` atau angka disalin manual.",
   "• Struktur tabel DW berbeda dari `01_ddl` tanpa keputusan kelompok.",
   "• Job hanya berjalan di laptop pembuatnya.",
   "• Data dummy tertinggal di database aplikasi.",
   "• Ada password di berkas yang di-commit.",
   "• Panel dashboard tanpa keterangan periode atau sumber."],
  ["• Table output tanpa pengosongan tabel, sehingga data dobel setiap job dijalankan.",
   "• Menulis nama database atau path langsung di step, bukan lewat variabel.",
   "• Mengabaikan baris yang gagal lookup.",
   "• Merata-ratakan rata-rata rating (harus SUM/SUM).",
   "• Diagram lingkaran untuk tren waktu.",
   "• Menyebut pola dari data dummy sebagai kondisi perpustakaan sebenarnya.",
   "• Menyalin angka dari keluaran AI tanpa menjalankan query.",
   "• Mengubah berkas anggota lain tanpa memberi tahu."],
]], [W / 2, W / 2]));

// ---------- 7. prompt ----------
add(h1("7. Prompt siap-paste per anggota"));
add(p("Cara pakai: buka sesi AI baru, lampirkan panduan ini, gambar skema, dan berkas `01`–`04`, lalu tempel blok milikmu. Ganti bagian dalam [ ]."));
const umum = [
  "Berperanlah sebagai pendamping teknis untuk proyek data warehouse mahasiswa.",
  "Proyek: Smart Reading Room (aplikasi perpustakaan sekolah, Laravel + MySQL), Kelompok G: Rafan, Irma, Isma, Vincent, Mako. Ada tiga DW yang membentuk fact constellation: ketiga fakta berbagi satu dimensi dim_waktu (role-playing), dimensi lain milik masing-masing DW. DW 1 fact_peminjaman (grain 1 pengajuan; dim_buku_pinjam, dim_waktu lewat waktu_pengajuan_key, dim_peminjam_pinjam, dim_status_pinjam), DW 2 fact_ulasan (grain 1 ulasan; dim_buku_ulasan, dim_waktu lewat waktu_ulasan_key, dim_pengulas, dim_rating), DW 3 fact_keterlambatan (grain 1 kewajiban pengembalian; dim_waktu lewat waktu_jatuh_tempo_key, dim_kategori_buku, dim_peminjam_kembali, dim_status_kembali). Peminjam yang dianalisis hanya siswa, dengan hierarki tingkat (Kelas 10/11/12) → kelas. PK fakta berupa surrogate key AUTO_INCREMENT. Rancangan lengkap ada di lampiran; 03_etl_data_warehouse.sql adalah acuan rumus.",
  "Lampiran: (a) panduan pengerjaan ini, (b) gambar skema di folder gambar/, (c) 01–04 SQL. Baca semuanya dahulu. Jika ada lampiran yang tidak terbaca, katakan terus terang.",
  "Keputusan kelompok yang berlaku: D2 semua anggota memakai versi Pentaho dan JDK 11 yang sama; D3 database sumber baru basdat_sumber; D4 data dummy Januari 2025–Desember 2026 (±60 siswa, ±1.000 peminjaman, ±400 ulasan); D5 peminjam hanya siswa (tingkat → kelas); D6 dimensi tidak dinormalisasi; D7 dashboard di halaman admin aplikasi Laravel dengan Chart.js; D8 ETL lewat Spoon saat pengembangan dan Kitchen untuk demo. D1 (ketentuan dan tenggat dosen) belum pasti.",
  "Aturan wajib: (1) Jangan mengubah rancangan DW (tabel, kolom, grain, rumus) tanpa saya minta. (2) Jangan mengarang angka; setiap angka berasal dari query yang saya jalankan. (3) Jelaskan setiap step dengan nama step Pentaho atau kode yang sebenarnya; jika ragu nama atau letak menu di versi saya, katakan. (4) Kerjakan satu langkah per respons, lalu berhenti dan tunggu hasil dari saya. (5) Jangan menyentuh bagian anggota lain; minta saya menempelkan hasil mereka bila diperlukan.",
];
const prompts = [
  ["Rafan", "A · database sumber dummy, basdat_dw, angka acuan, koordinasi.", [
    "Langkah 1: Tulis pemahamanmu tentang bagian saya (maks. 6 kalimat) dan daftar kolom sumber yang dibutuhkan ketiga DW. Berhenti.",
    "Langkah 2: Usulkan rancangan data dummy sesuai D4 (jumlah, periode, sebaran kelas, status, rating, keterlambatan) dalam tabel. Tunggu persetujuan.",
    "Langkah 3: Tulis skrip SQL database sumber dan data dummy yang deterministik. Saya jalankan, lalu tempel hasilnya.",
    "Langkah 4: Bantu menyusun tabel angka acuan dari hasil 03_etl SQL (jumlah baris dan SUM ukuran) dan daftar pertanyaan integrasi I1–I8."]],
  ["Irma", "B1 · koneksi, variabel, tr_dim_dw1, tr_dim_dw2, tr_dim_dw3, job_etl_dw.", [
    "Langkah 1: Jelaskan cara memasang Pentaho versi [D2], JDK, dan MySQL Connector/J di Windows, serta cara membuat variabel di kettle.properties. Berhenti.",
    "Langkah 2: Pandu saya membuat koneksi bersama src_sumber dan dw_target memakai variabel. Tunggu hasil Test connection.",
    "Langkah 3: Pandu tr_dim_dw1, lalu tr_dim_dw2, lalu tr_dim_dw3, satu per respons, sesuai Bagian 5 B1. Setelah tiap transformasi, saya tempel Step Metrics untuk dicocokkan dengan acuan.",
    "Langkah 4: Pandu job_etl_dw.kjb dan cara menjalankannya dengan Kitchen."]],
  ["Isma", "B2 · tr_fact_peminjaman, tr_fact_ulasan, tr_fact_keterlambatan, validasi.", [
    "Langkah 1: Buat tabel pemetaan setiap rumus di bagian B 03_etl_data_warehouse.sql ke step Pentaho. Berhenti.",
    "Langkah 2: Pandu tr_fact_peminjaman sesuai Bagian 5 B2. Saya tempel Step Metrics dan hasil rekonsiliasi.",
    "Langkah 3: Lanjutkan tr_fact_ulasan, lalu tr_fact_keterlambatan (perhatikan TGL_ETL), satu per respons.",
    "Langkah 4: Bantu membaca hasil rekonsiliasi; jika ada selisih, cari penyebabnya step demi step."]],
  ["Vincent", "C1 · 05_view_dashboard.sql, filter global, halaman ringkasan dan DW 1.", [
    "Langkah 1: Pandu saya menambah koneksi database kedua ke basdat_dw di Laravel (config/database.php dan .env), membuat route dan controller dashboard yang hanya bisa dibuka admin, serta memasang Chart.js. Berhenti.",
    "Langkah 2: Usulkan daftar view untuk ketiga DW beserta kolomnya. Tunggu persetujuan, lalu tulis 05_view_dashboard.sql.",
    "Langkah 3: Bangun filter global dan halaman ringkasan; saya tempel tangkapan layar dan angka untuk dicocokkan dengan query 04.",
    "Langkah 4: Bangun halaman DW 1 sesuai Bagian 5 C1."]],
  ["Mako", "C2 · halaman DW 2 dan DW 3, laporan implementasi.", [
    "Langkah 1: Bantu saya membuat sketsa dashboard (ringkasan, DW 1, DW 2, DW 3, filter) dan pilih jenis grafik yang tepat untuk tiap indikator. Berhenti.",
    "Langkah 2: Setelah view dari Vincent tersedia [tempel], bangun halaman DW 2 lalu DW 3 sesuai Bagian 5 C2, satu per respons.",
    "Langkah 3: Susun kerangka laporan implementasi; isi bagian demi bagian dari hasil yang saya tempel.",
    "Langkah 4: Periksa laporan: setiap angka bersumber dari query, tidak ada klaim tentang kondisi perpustakaan nyata dari data dummy."]],
];
prompts.forEach(([nama, bagian, langkah]) => {
  add(new Paragraph({ keepNext: true, spacing: { before: 160, after: 60 }, children: runs(nama, { size: 22, bold: true, color: UNGU }) }));
  add(promptBox([...umum, `Bagian saya: ${nama} · ${bagian}`, ...langkah, "Mulai dari Langkah 1 sekarang."]));
});

// ---------- 8. referensi ----------
add(h1("8. Referensi dan berkas acuan"));
add(h2("A. Berkas di repo"));
add(table(["Berkas", "Isi", "Dipakai oleh"], [
  ["`01_ddl_data_warehouse.sql`", "Struktur `basdat_dw` (3 dimensi, 3 fakta)", "Semua"],
  ["`sumber-dummy/02_basdat_sumber.sql`, `angka-acuan.md`", "Database sumber dummy `basdat_sumber` (D3–D4) dan angka acuan", "Rafan"],
  ["`03_etl_data_warehouse.sql`", "ETL SQL: acuan rumus dan rekonsiliasi", "Rafan, Irma, Isma"],
  ["`04_query_analisis.sql`", "Query information package; pembanding angka dashboard", "Isma, Vincent, Mako"],
  ["`gambar/`, `01_ddl_data_warehouse.sql`", "Rancangan fact constellation, X/Y/Z, gambar skema", "Semua"],
], [3600, W - 3600 - 2000, 2000]));
add(h2("B. Perangkat"));
[
  "Pentaho Data Integration edisi Community, beserta dokumentasi resminya: unduh dari situs resmi dan catat versinya (D2).",
  "Java Development Kit 11 dan MySQL Connector/J (berkas `.jar` disalin ke folder `lib` Pentaho).",
  "MySQL 8.0 (XAMPP); fitur yang dipakai: CTE rekursif, window function, ROLLUP, REGEXP.",
].forEach(t => add(bullet(t)));
add(h2("C. Literatur (lengkapi keterangan terbit dari halaman resmi sebelum dikutip)"));
[
  "Kimball, R., & Ross, M. (2013). *The data warehouse toolkit: The definitive guide to dimensional modeling* (3rd ed.). Wiley. Dipakai untuk: grain, dimensi, star schema, fact constellation, role-playing dimension.",
  "Casters, M., Bouman, R., & van Dongen, J. (2010). *Pentaho Kettle solutions: Building open source ETL solutions with Pentaho Data Integration*. Wiley. Dipakai untuk: transformasi, job, variabel.",
].forEach(t => add(bullet(t.replace(/\*/g, ""))));
add(h2("D. Angka acuan saat ini (sebelum data dummy baru)"));
add(p("Hasil uji 22 November 2026: ETL SQL dengan `TGL_ETL` 19 November 2026 pada data asli + data dummy lama. Angka ini berubah setelah Tahap 2 dan hanya menjadi contoh format tabel acuan."));
add(table(["Tabel", "Jumlah baris", "Catatan"], [
  ["dim_waktu (bersama)", "1.462", "1 Jan 2025–31 Des 2028 + key 0"],
  ["dim_buku_pinjam · dim_buku_ulasan", "15 · 15", "sama dengan jumlah buku"],
  ["dim_kategori_buku", "8", "7 kategori + Tanpa Kategori"],
  ["dim_peminjam_pinjam · dim_pengulas · dim_peminjam_kembali", "37 · 37 · 37", "siswa saja (2 tenaga pendidik tidak dimuat); termasuk 2 akun \"Lainnya\""],
  ["dim_status_pinjam · dim_rating · dim_status_kembali", "5 · 5 · 5", "baris statis"],
  ["fact_peminjaman", "275", "243 berhasil, 31 gagal; 1 peminjaman tenaga pendidik tidak dimuat"],
  ["fact_ulasan", "95", "87 rating tinggi, 8 rendah; rata-rata 3,89"],
  ["fact_keterlambatan", "243", "43 terlambat (17,7%), denda Rp334.000; bergantung pada `TGL_ETL`"],
], [3000, 1600, W - 4600]));

// =====================================================================
const doc = new Document({
  creator: "Kelompok G",
  title: "Panduan Pengerjaan Implementasi Data Warehouse",
  styles: {
    default: { document: { run: { font: FONT, size: 21 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { font: FONT, size: 28, bold: true, color: UNGU },
        paragraph: { spacing: { before: 280, after: 120 }, keepNext: true, outlineLevel: 0,
          border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: "D9B8E0", space: 2 } } } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { font: FONT, size: 23, bold: true, color: INK },
        paragraph: { spacing: { before: 200, after: 80 }, keepNext: true, outlineLevel: 1 } },
    ],
  },
  numbering: { config: [
    { reference: "poin", levels: [{ level: 0, format: LevelFormat.BULLET, text: "•", alignment: AlignmentType.LEFT,
      style: { paragraph: { indent: { left: 400, hanging: 220 } } } }] },
    { reference: "angka", levels: [{ level: 0, format: LevelFormat.DECIMAL, text: "%1.", alignment: AlignmentType.LEFT,
      style: { paragraph: { indent: { left: 400, hanging: 300 } } } }] },
    { reference: "risiko", levels: [{ level: 0, format: LevelFormat.DECIMAL, text: "%1.", alignment: AlignmentType.LEFT,
      style: { paragraph: { indent: { left: 400, hanging: 300 } } } }] },
  ] },
  sections: [{
    properties: { page: { size: { width: 11906, height: 16838 }, margin: { top: 1300, bottom: 1100, left: 1134, right: 1134 } } },
    headers: { default: new Header({ children: [new Paragraph({ alignment: AlignmentType.LEFT,
      children: [new TextRun({ text: `Panduan Pengerjaan Implementasi DW Smart Reading Room · ${VERSI}`, font: FONT, size: 16, color: ABU })] })] }) },
    footers: { default: new Footer({ children: [new Paragraph({ alignment: AlignmentType.RIGHT,
      children: [new TextRun({ children: ["Halaman ", PageNumber.CURRENT, " dari ", PageNumber.TOTAL_PAGES], font: FONT, size: 16, color: ABU })] })] }) },
    children: isi,
  }],
});

const out = path.join(__dirname, "Panduan-Pengerjaan-Implementasi-DW.docx");
Packer.toBuffer(doc).then(b => { fs.writeFileSync(out, b); console.log("dibuat:", out); });
