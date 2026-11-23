"""Membuat gambar design schema data warehouse Smart Reading Room.

Jalankan:  python buat_gambar_skema.py
Hasil   :  folder gambar/ (3 skema DW + 1 skema fact constellation); MODE=bersih -> gambar-tanpa-judul/
Struktur tabel mengikuti 01_ddl_data_warehouse.sql.
"""
import os
from PIL import Image, ImageDraw, ImageFont

SS = 2  # supersampling agar garis halus
FONT_DIR = "C:/Windows/Fonts/"
BERSIH = os.environ.get("MODE") == "bersih"  # tanpa judul, subjudul, dan catatan
OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "gambar-tanpa-judul" if BERSIH else "gambar")

FACT = "#4D0057"      # ungu (warna proyek)
DIM = "#2F5FA7"       # biru: dimensi
SNOW = "#C8731A"      # oranye: dimensi snowflake / bridge
INK = "#1F1F2B"
MUTED = "#767686"
LINE = "#D9D9E3"
MEASURE = "#7A1C8C"


def font(size, bold=False):
    return ImageFont.truetype(FONT_DIR + ("calibrib.ttf" if bold else "calibri.ttf"), int(size * SS))


class Table:
    HEAD, ROW, PAD = 50, 36, 10

    def __init__(self, name, rows, x, y, w, color=DIM, measures=(), sub=None, dashed=False):
        self.name, self.rows, self.x, self.y, self.w = name, rows, x, y, w
        self.color, self.measures, self.sub, self.dashed = color, set(measures), sub or [], dashed
        body = len(rows) * self.ROW if rows else len(self.sub) * 30 + 14
        self.h = self.HEAD + body + self.PAD

    # titik sambung pada sisi kotak; frac = 0..1 atau nama atribut (untuk sisi kiri/kanan)
    def anchor(self, side, frac=0.5):
        if isinstance(frac, str):
            idx = [r[1] for r in self.rows].index(frac)
            ay = self.y + self.HEAD + idx * self.ROW + self.ROW / 2
        else:
            ay = self.y + self.h * frac
        ax = self.x + self.w * (frac if not isinstance(frac, str) else 0.5)
        return {"l": (self.x, ay), "r": (self.x + self.w, ay),
                "t": (ax, self.y), "b": (ax, self.y + self.h)}[side]

    def draw(self, d):
        x, y, w, h = self.x, self.y, self.w, self.h
        d.rounded_rectangle([x * SS + 5, y * SS + 7, (x + w) * SS + 5, (y + h) * SS + 7], 14 * SS, fill="#E6E6EE")
        d.rounded_rectangle([x * SS, y * SS, (x + w) * SS, (y + h) * SS], 14 * SS, fill="white",
                            outline=self.color, width=3 * SS)
        d.rounded_rectangle([x * SS, y * SS, (x + w) * SS, (y + self.HEAD) * SS], 14 * SS, fill=self.color)
        d.rectangle([x * SS, (y + self.HEAD - 14) * SS, (x + w) * SS, (y + self.HEAD) * SS], fill=self.color)
        d.text(((x + w / 2) * SS, (y + self.HEAD / 2) * SS), self.name, font=font(25, True), fill="white", anchor="mm")
        ty = y + self.HEAD
        if not self.rows and not self.sub:
            d.rounded_rectangle([x * SS, y * SS, (x + w) * SS, (y + h) * SS], 14 * SS, fill=self.color)
            d.text(((x + w / 2) * SS, (y + h / 2) * SS), self.name, font=font(25, True), fill="white", anchor="mm")
            return
        for i, (key, name, typ) in enumerate(self.rows):
            ry = ty + i * self.ROW
            if i % 2 == 1:
                d.rectangle([(x + 3) * SS, ry * SS, (x + w - 3) * SS, (ry + self.ROW) * SS], fill="#F6F6FA")
            mid = (ry + self.ROW / 2) * SS
            if key:
                d.text(((x + 14) * SS, mid), key, font=font(16, True), fill=self.color, anchor="lm")
            colr = MEASURE if name in self.measures else INK
            d.text(((x + 74) * SS, mid), name, font=font(21, name in self.measures), fill=colr, anchor="lm")
            d.text(((x + w - 14) * SS, mid), typ, font=font(16), fill=MUTED, anchor="rm")
        for j, line in enumerate(self.sub):
            d.text(((x + 16) * SS, (ty + 22 + j * 30) * SS), line, font=font(19), fill=INK, anchor="lm")


DEFER = []


def flush(d):
    """Gambar penanda 1/N dan label di atas semua kotak."""
    for fn, args in DEFER:
        fn(d, *args)
    DEFER.clear()


def pill(d, cx, cy, text, color):
    r = 15
    d.ellipse([(cx - r) * SS, (cy - r) * SS, (cx + r) * SS, (cy + r) * SS], fill="white", outline=color, width=2 * SS)
    d.text((cx * SS, cy * SS), text, font=font(18, True), fill=color, anchor="mm")


def label(d, cx, cy, text, color=MUTED):
    f = font(18)
    box = d.textbbox((cx * SS, cy * SS), text, font=f, anchor="mm")
    d.rectangle([box[0] - 8, box[1] - 4, box[2] + 8, box[3] + 4], fill="white")
    d.text((cx * SS, cy * SS), text, font=f, fill=color, anchor="mm")


def dashed_line(d, pts, color, width):
    for (x1, y1), (x2, y2) in zip(pts, pts[1:]):
        dist = max(((x2 - x1) ** 2 + (y2 - y1) ** 2) ** 0.5, 1)
        n = int(dist / 18)
        for k in range(0, n, 2):
            a, b = k / n, min((k + 1) / n, 1)
            d.line([(x1 + (x2 - x1) * a) * SS, (y1 + (y2 - y1) * a) * SS,
                    (x1 + (x2 - x1) * b) * SS, (y1 + (y2 - y1) * b) * SS], fill=color, width=width * SS)


def connect(d, a, sa, fa, b, sb, fb, ma="1", mb="N", color=INK, dashed=False, mid=None,
            elbow=True, text=None, text_at=0.5):
    """Garis dari tabel a ke b. ma/mb = kardinalitas di ujung a/b."""
    p1, p2 = a.anchor(sa, fa), b.anchor(sb, fb)
    horiz = sa in "lr"
    if not elbow or (horiz and abs(p1[1] - p2[1]) < 2) or (not horiz and abs(p1[0] - p2[0]) < 2):
        pts = [p1, p2]
    elif horiz:
        xm = mid if mid is not None else (p1[0] + p2[0]) / 2
        pts = [p1, (xm, p1[1]), (xm, p2[1]), p2]
    else:
        ym = mid if mid is not None else (p1[1] + p2[1]) / 2
        pts = [p1, (p1[0], ym), (p2[0], ym), p2]
    if dashed:
        dashed_line(d, pts, color, 3)
    else:
        d.line([(x * SS, y * SS) for x, y in pts], fill=color, width=3 * SS, joint="curve")

    def toward(p, q, k=24):
        dx, dy = q[0] - p[0], q[1] - p[1]
        n = max((dx * dx + dy * dy) ** 0.5, 1)
        return p[0] + dx / n * k, p[1] + dy / n * k
    if ma:
        c = toward(pts[0], pts[1]); DEFER.append((pill, (c[0], c[1], ma, color)))
    if mb:
        c = toward(pts[-1], pts[-2]); DEFER.append((pill, (c[0], c[1], mb, color)))
    if text:
        seg = (pts[-2], pts[-1])  # segmen terakhir (menuju tabel kedua)
        DEFER.append((label, (seg[0][0] + (seg[1][0] - seg[0][0]) * text_at,
                              seg[0][1] + (seg[1][1] - seg[0][1]) * text_at, text, color)))


def canvas(w, h, title, subtitle):
    img = Image.new("RGB", (w * SS, h * SS), "white")
    d = ImageDraw.Draw(img)
    if not BERSIH:
        d.text((60 * SS, 48 * SS), title, font=font(42, True), fill=FACT, anchor="lm")
        d.text((60 * SS, 98 * SS), subtitle, font=font(23), fill=MUTED, anchor="lm")
        d.line([60 * SS, 130 * SS, (w - 60) * SS, 130 * SS], fill=LINE, width=2 * SS)
    return img, d


def legend(d, x, y, items):
    for i, (color, text, kind) in enumerate(items):
        yy = y + i * 34
        if kind == "box":
            d.rounded_rectangle([x * SS, yy * SS, (x + 36) * SS, (yy + 22) * SS], 5 * SS, fill=color)
        elif kind == "dash":
            dashed_line(d, [(x, yy + 11), (x + 36, yy + 11)], color, 3)
        else:
            d.text(((x + 18) * SS, (yy + 11) * SS), kind, font=font(18, True), fill=color, anchor="mm")
        d.text(((x + 52) * SS, (yy + 11) * SS), text, font=font(19), fill=INK, anchor="lm")


def save(img, name):
    os.makedirs(OUT, exist_ok=True)
    if BERSIH:  # potong ruang kosong dan beri margin rapi
        from PIL import ImageChops
        bg = Image.new("RGB", img.size, "white")
        box = ImageChops.difference(img, bg).getbbox()
        pad = 40 * SS
        img = img.crop((max(box[0] - pad, 0), max(box[1] - pad, 0), min(box[2] + pad, img.size[0]), min(box[3] + pad, img.size[1])))
    w, h = img.size
    img.resize((w // SS, h // SS), Image.LANCZOS).save(os.path.join(OUT, name), optimize=True)
    print("dibuat:", name, w // SS, "x", h // SS)


# ---------------------------------------------------------------------
# Definisi tabel (sesuai 01_ddl_data_warehouse.sql: fact constellation,
# dim_waktu dipakai bersama ketiga fakta, dimensi lain milik tiap DW)
# ---------------------------------------------------------------------
KHAS = "#C8731A"     # oranye: dimensi khas DW
BERSAMA = "#127A6E"  # hijau tosca: dimensi bersama (dim_waktu)


def warna(jenis):
    return KHAS if jenis is True else BERSAMA if jenis == "bersama" else DIM

LEGEND_KEYS = [(FACT, "Tabel fakta", "box"), (DIM, "Tabel dimensi milik DW ini", "box"),
               (KHAS, "Dimensi khas (hanya ada di DW ini)", "box"),
               (BERSAMA, "Dimensi bersama (dipakai ketiga fakta)", "box"),
               (INK, "1 = satu, N = banyak", "N"), (MEASURE, "Teks ungu tebal = ukuran (measure)", "M")]

AXIS = "#B8461B"  # warna label peran dimensi


def axis_label(d, t, text, posisi="atas"):
    """Label peran dimensi: di atas kotak, atau di kiri/kanan kotak (untuk kotak bawah)."""
    if posisi == "kiri":
        d.text(((t.x - 24) * SS, (t.y + 25) * SS), text, font=font(22, True), fill=AXIS, anchor="rm")
    elif posisi == "kanan":
        d.text(((t.x + t.w + 24) * SS, (t.y + 25) * SS), text, font=font(22, True), fill=AXIS, anchor="lm")
    else:
        d.text((t.x * SS, (t.y - 24) * SS), text, font=font(22, True), fill=AXIS, anchor="lm")


def waktu_rows(key, hari=True):
    rows = [("PK", key, "INT"), ("", "tanggal", "DATE")]
    if hari:
        rows.append(("", "nama_hari", "VARCHAR"))
    return rows + [("", "bulan", "TINYINT"), ("", "nama_bulan", "VARCHAR"),
                   ("", "kuartal", "CHAR(2)"), ("", "tahun", "SMALLINT")]


def star4(file, title, subtitle, fact_name, fact_rows, measures, dims, note=None):
    """dims = 4 tuple (nama, baris, fk_di_fakta, label, khas?) untuk posisi:
    kiri, kanan, kiri-bawah, kanan-bawah."""
    W = 2200
    fh = Table.HEAD + len(fact_rows) * Table.ROW + Table.PAD
    t_left = Table(dims[0][0], dims[0][1], 90, 230, 430, warna(dims[0][4]))
    t_right = Table(dims[1][0], dims[1][1], 1680, 230, 430, warna(dims[1][4]))
    yb = 230 + max(fh, t_left.h, t_right.h) + 110
    t_bl = Table(dims[2][0], dims[2][1], 560, yb, 430, warna(dims[2][4]))
    t_br = Table(dims[3][0], dims[3][1], 1210, yb, 430, warna(dims[3][4]))
    H = yb + max(t_bl.h, t_br.h) + 50 + 45 + 144 + 40
    img, d = canvas(W, H, title, subtitle)
    fact = Table(fact_name, fact_rows, 820, 230, 560, FACT, measures=measures)
    for t, dm in ((t_left, dims[0]), (t_right, dims[1]), (t_bl, dims[2]), (t_br, dims[3])):
        t.color = warna(dm[4])
    connect(d, t_left, "r", t_left.rows[0][1], fact, "l", dims[0][2], "1", "N", t_left.color)
    connect(d, t_right, "l", t_right.rows[0][1], fact, "r", dims[1][2], "1", "N", t_right.color)
    connect(d, t_bl, "t", 0.5, fact, "b", 0.25, "1", "N", t_bl.color)
    connect(d, t_br, "t", 0.5, fact, "b", 0.75, "1", "N", t_br.color)
    for t in (t_left, t_right, t_bl, t_br, fact):
        t.draw(d)
    flush(d)
    axis_label(d, t_left, dims[0][3])
    axis_label(d, t_right, dims[1][3])
    axis_label(d, t_bl, dims[2][3], "kiri")
    axis_label(d, t_br, dims[3][3], "kanan")
    ly = max(t_bl.y + t_bl.h, t_br.y + t_br.h) + 50
    if note and not BERSIH:
        d.text((90 * SS, ly * SS), note, font=font(20), fill=INK, anchor="lm")
        ly += 45
    legend(d, 90, ly, LEGEND_KEYS[:4])
    legend(d, 760, ly, LEGEND_KEYS[4:])
    save(img, file)


# ---------------------------------------------------------------------
# 1. DW 1: Analisis Peminjaman Buku
# ---------------------------------------------------------------------
def gambar_dw1():
    star4("skema-dw1-peminjaman-buku.png", "DW 1: Analisis Peminjaman Buku",
          "Bagian dari fact constellation | grain: 1 baris = 1 pengajuan peminjaman | dim_waktu dipakai bersama",
          "fact_peminjaman",
          [("PK", "peminjaman_key", "INT"), ("", "loan_id", "INT"), ("FK", "buku_pinjam_key", "INT"),
           ("FK", "waktu_pengajuan_key", "INT"), ("FK", "peminjam_pinjam_key", "INT"),
           ("FK", "status_pinjam_key", "TINYINT"), ("", "jumlah_pengajuan", "TINYINT"),
           ("", "jumlah_berhasil", "TINYINT"), ("", "jumlah_gagal", "TINYINT")],
          ["jumlah_pengajuan", "jumlah_berhasil", "jumlah_gagal"],
          [("dim_buku_pinjam", [("PK", "buku_pinjam_key", "INT"), ("", "book_id", "INT"), ("", "judul", "VARCHAR"),
                                ("", "nama_kategori", "VARCHAR"), ("", "status_aktif", "VARCHAR")],
            "buku_pinjam_key", "X: Buku (kategori sebagai atribut)", False),
           ("dim_peminjam_pinjam", [("PK", "peminjam_pinjam_key", "INT"), ("", "user_id", "INT"),
                                    ("", "nis", "VARCHAR"), ("", "nama", "VARCHAR"),
                                    ("", "kelas", "VARCHAR"), ("", "tingkat", "VARCHAR")],
            "peminjam_pinjam_key", "Z: Siswa (tingkat > kelas)", False),
           ("dim_waktu", waktu_rows("waktu_key"), None, "Y: Waktu pengajuan (dimensi bersama)", "bersama"),
           ("dim_status_pinjam", [("PK", "status_pinjam_key", "TINYINT"), ("", "nama_status", "VARCHAR"),
                                  ("", "kelompok_status", "VARCHAR")], None, "Dimensi khas: Status peminjaman", True)],
          "peminjaman_key = surrogate key fakta; loan_id disimpan hanya untuk menelusuri data ke database sumber.")


# ---------------------------------------------------------------------
# 2. DW 2: Analisis Kepuasan Pembaca
# ---------------------------------------------------------------------
def gambar_dw2():
    star4("skema-dw2-kepuasan-pembaca.png", "DW 2: Analisis Kepuasan Pembaca",
          "Bagian dari fact constellation | grain: 1 baris = 1 ulasan | dim_waktu dipakai bersama",
          "fact_ulasan",
          [("PK", "ulasan_key", "INT"), ("", "review_id", "INT"), ("FK", "buku_ulasan_key", "INT"),
           ("FK", "waktu_ulasan_key", "INT"), ("FK", "pengulas_key", "INT"), ("FK", "rating_key", "TINYINT"),
           ("", "rating", "TINYINT"), ("", "jumlah_ulasan", "TINYINT"),
           ("", "flag_rating_tinggi", "TINYINT"), ("", "flag_rating_rendah", "TINYINT")],
          ["rating", "jumlah_ulasan", "flag_rating_tinggi", "flag_rating_rendah"],
          [("dim_buku_ulasan", [("PK", "buku_ulasan_key", "INT"), ("", "book_id", "INT"), ("", "judul", "VARCHAR"),
                                ("", "penulis", "VARCHAR"), ("", "penerbit", "VARCHAR"),
                                ("", "tahun_terbit", "SMALLINT"), ("", "nama_kategori", "VARCHAR")],
            "buku_ulasan_key", "X: Buku (judul dan penulis)", False),
           ("dim_pengulas", [("PK", "pengulas_key", "INT"), ("", "user_id", "INT"), ("", "nama", "VARCHAR"),
                             ("", "kelas", "VARCHAR"), ("", "tingkat", "VARCHAR")],
            "pengulas_key", "Z: Siswa pengulas (tingkat > kelas)", False),
           ("dim_waktu", waktu_rows("waktu_key"), None, "Y: Waktu ulasan (dimensi bersama)", "bersama"),
           ("dim_rating", [("PK", "rating_key", "TINYINT"), ("", "nilai", "TINYINT"), ("", "label_rating", "VARCHAR"),
                           ("", "kelompok_rating", "VARCHAR")], None, "Dimensi khas: Rating", True)],
          "Rata-rata rating = SUM(rating) / SUM(jumlah_ulasan); rating tinggi = rating >= 3, rating rendah = rating <= 2.")


# ---------------------------------------------------------------------
# 3. DW 3: Analisis Keterlambatan Pengembalian
# ---------------------------------------------------------------------
def gambar_dw3():
    star4("skema-dw3-keterlambatan.png", "DW 3: Analisis Keterlambatan Pengembalian",
          "Bagian dari fact constellation | grain: 1 baris = 1 kewajiban pengembalian yang sudah jatuh tempo | dim_waktu dipakai bersama",
          "fact_keterlambatan",
          [("PK", "keterlambatan_key", "INT"), ("", "loan_id", "INT"), ("FK", "waktu_jatuh_tempo_key", "INT"),
           ("FK", "kategori_key", "INT"), ("FK", "peminjam_kembali_key", "INT"),
           ("FK", "status_kembali_key", "TINYINT"), ("", "flag_terlambat", "TINYINT"),
           ("", "flag_tepat_waktu", "TINYINT"), ("", "hari_terlambat", "SMALLINT"), ("", "nominal_denda", "INT")],
          ["flag_terlambat", "flag_tepat_waktu", "hari_terlambat", "nominal_denda"],
          [("dim_kategori_buku", [("PK", "kategori_key", "INT"), ("", "category_id", "INT"),
                                  ("", "nama_kategori", "VARCHAR")],
            "kategori_key", "Y: Kategori buku", False),
           ("dim_peminjam_kembali", [("PK", "peminjam_kembali_key", "INT"), ("", "user_id", "INT"),
                                     ("", "nis", "VARCHAR"), ("", "nama", "VARCHAR"),
                                     ("", "kelas", "VARCHAR"), ("", "tingkat", "VARCHAR")],
            "peminjam_kembali_key", "Z: Siswa (tingkat > kelas)", False),
           ("dim_waktu", waktu_rows("waktu_key"), None, "X: Waktu jatuh tempo (dimensi bersama)", "bersama"),
           ("dim_status_kembali", [("PK", "status_kembali_key", "TINYINT"), ("", "status_pengembalian", "VARCHAR"),
                                   ("", "posisi_buku", "VARCHAR"), ("", "status_denda", "VARCHAR")],
            None, "Dimensi khas: Status pengembalian", True)],
          "Baris tepat waktu ikut disimpan (hari_terlambat = 0) agar persentase keterlambatan dapat dihitung.")


# ---------------------------------------------------------------------
# 4. Fact constellation: tiga fakta berbagi dim_waktu
# ---------------------------------------------------------------------
def gambar_constellation():
    W = 2300
    waktu = Table("dim_waktu", waktu_rows("waktu_key"), 890, 190, 520, BERSAMA)
    fy = waktu.y + waktu.h + 150
    kol = [
        ("fact_peminjaman (DW 1)", "Grain: 1 pengajuan", "waktu_pengajuan_key",
         ["dim_buku_pinjam", "dim_peminjam_pinjam"], "dim_status_pinjam"),
        ("fact_ulasan (DW 2)", "Grain: 1 ulasan", "waktu_ulasan_key",
         ["dim_buku_ulasan", "dim_pengulas"], "dim_rating"),
        ("fact_keterlambatan (DW 3)", "Grain: 1 kewajiban kembali", "waktu_jatuh_tempo_key",
         ["dim_kategori_buku", "dim_peminjam_kembali"], "dim_status_kembali"),
    ]
    by_last = 0
    tables = [waktu]
    img, d = None, None
    plan = []
    for i, (fname, grain, fk, dims, khas) in enumerate(kol):
        x0 = 90 + i * 740
        f = Table(fname, [], x0, fy, 640, FACT, sub=[grain])
        boxes = []
        for j, nm in enumerate(dims + [khas]):
            t = Table(nm, [], x0 + 150, fy + 150 + j * 100, 490, KHAS if nm == khas else DIM)
            t.h = 62
            boxes.append(t)
        by_last = max(by_last, boxes[-1].y + boxes[-1].h)
        plan.append((f, fk, boxes, 0.2 + 0.3 * i))
    H = by_last + 60 + 144 + 50
    img, d = canvas(W, H, "Fact Constellation: Tiga DW dengan Satu Dimensi Bersama",
                    "dim_waktu dipakai ketiga tabel fakta (role-playing: pengajuan, ulasan, jatuh tempo); dimensi lain milik masing-masing DW")
    ym = waktu.y + waktu.h + 60
    for f, fk, boxes, frac in plan:
        connect(d, waktu, "b", frac, f, "t", 0.5, "1", "N", BERSAMA, mid=ym, text=fk, text_at=0.45)
        for j, t in enumerate(boxes):
            connect(d, t, "l", 0.5, f, "b", 70 / 640, "1", "N" if j == 0 else "", DIM, mid=f.x + 70)
    for f, fk, boxes, frac in plan:
        for t in [f] + boxes:
            t.draw(d)
    waktu.draw(d)
    flush(d)
    ly = by_last + 60
    legend(d, 90, ly, LEGEND_KEYS[:4])
    legend(d, 760, ly, [(INK, "1 = satu, N = banyak", "N"), (MUTED, "Teks pada garis = nama FK di tabel fakta", "ab")])
    save(img, "skema-fact-constellation.png")


if __name__ == "__main__":
    gambar_dw1()
    gambar_dw2()
    gambar_dw3()
    gambar_constellation()
