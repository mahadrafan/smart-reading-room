"""Gambar PEMBANDING: rancangan DW jika dimensinya dinormalisasi (snowflake).

Jalankan:  python buat_gambar_normalisasi.py
Hasil   :  gambar/normalisasi-dw1-peminjaman-buku.png, -dw2-kepuasan-pembaca.png,
           -dw3-keterlambatan.png (MODE=bersih -> gambar-tanpa-judul/)

Ini hanya rancangan pembanding; DDL/ETL yang berlaku tetap versi tidak
dinormalisasi (01_ddl_data_warehouse.sql). Yang dinormalisasi:
  * buku -> kategori        : nama_kategori dipindah ke dim_kategori_buku (dipakai DW 1-3)
  * siswa -> kelas -> tingkat: kelas & tingkat dipindah ke dim_kelas (dipakai DW 1-3)
  * waktu -> bulan          : bulan, nama_bulan, kuartal, tahun dipindah ke dim_bulan (dipakai DW 1-3)
  * buku -> penulis (DW 2)  : penulis yang digabung jadi teks dipecah ke
                              bridge_buku_penulis + dim_penulis (relasi banyak-ke-banyak)
"""
from buat_gambar_skema import (Table, connect, canvas, legend, save, flush, axis_label,
                               FACT, DIM, KHAS, BERSAMA, INK, MEASURE)

SUB = "#5C88C4"  # biru muda: sub-dimensi hasil normalisasi milik satu DW

LEGEND_N = [(FACT, "Tabel fakta", "box"), (DIM, "Tabel dimensi milik DW ini", "box"),
            (KHAS, "Dimensi khas (hanya ada di DW ini)", "box"),
            (BERSAMA, "Dimensi / sub-dimensi bersama (dipakai DW 1-3)", "box"),
            (SUB, "Sub-dimensi hasil normalisasi (hanya DW ini)", "box")]
LEGEND_R = [(INK, "1 = satu, N = banyak", "N"), (MEASURE, "Teks ungu tebal = ukuran (measure)", "M")]

W = 2600
TOP = 230


def waktu_norm():
    return Table("dim_waktu", [("PK", "waktu_key", "INT"), ("", "tanggal", "DATE"),
                               ("", "nama_hari", "VARCHAR"), ("FK", "bulan_key", "INT")], 0, 0, 430, BERSAMA)


def bulan():
    return Table("dim_bulan", [("PK", "bulan_key", "INT"), ("", "tahun", "SMALLINT"), ("", "bulan", "TINYINT"),
                               ("", "nama_bulan", "VARCHAR"), ("", "kuartal", "CHAR(2)")], 40, 0, 400, BERSAMA)


def kategori(x, w=380):
    return Table("dim_kategori_buku", [("PK", "kategori_key", "INT"), ("", "category_id", "INT"),
                                       ("", "nama_kategori", "VARCHAR")], x, TOP, w, BERSAMA)


def kelas():
    return Table("dim_kelas", [("PK", "kelas_key", "INT"), ("", "nama_kelas", "VARCHAR"),
                               ("", "tingkat", "VARCHAR")], 2180, TOP, 380, BERSAMA)


def gambar(file, title, subtitle, fact, left, right, khas, labels, extra=(), extra_links=(),
           y_bawah=None, note=None, left_sub=None, sub=False):
    """left/right = dimensi di kiri/kanan fakta; left_sub = sub-dimensi di ujung kiri (atau None)."""
    fact.x, fact.y = 1020, TOP
    left.x, left.y, right.x, right.y = 470, TOP, 1700, TOP
    yb = y_bawah or TOP + max(fact.h, left.h, right.h) + 110
    w_, b_ = waktu_norm(), bulan()
    w_.x, w_.y, b_.y = 560, yb, yb
    khas.x, khas.y = 1460, yb
    kls = kelas()
    tabs = [fact, left, right, w_, b_, khas, kls] + list(extra) + ([left_sub] if left_sub else [])
    bottom = max(t.y + t.h for t in tabs)
    H = bottom + 50 + (45 if note else 0) + (5 if sub else 4) * 34 + 50
    img, d = canvas(W, H, title, subtitle)
    # fakta -> dimensi
    connect(d, left, "r", left.rows[0][1], fact, "l", labels["fk_left"], "1", "N", left.color)
    connect(d, right, "l", right.rows[0][1], fact, "r", labels["fk_right"], "1", "N", right.color)
    connect(d, w_, "t", 0.85, fact, "b", 0.25, "1", "N", BERSAMA)
    connect(d, khas, "t", 0.5, fact, "b", 0.75, "1", "N", KHAS)
    # normalisasi (dimensi -> sub-dimensi)
    connect(d, b_, "r", "bulan_key", w_, "l", "bulan_key", "1", "N", BERSAMA)
    connect(d, kls, "l", "kelas_key", right, "r", "kelas_key", "1", "N", BERSAMA)
    if left_sub:
        connect(d, left_sub, "r", "kategori_key", left, "l", "kategori_key", "1", "N", BERSAMA)
    for args in extra_links:
        connect(d, *args)
    for t in tabs:
        t.draw(d)
    flush(d)
    axis_label(d, left, labels["left"])
    axis_label(d, right, labels["right"])
    axis_label(d, w_, labels["waktu"])
    axis_label(d, khas, labels["khas"], "kanan")
    axis_label(d, b_, "Sub-dimensi bersama")
    axis_label(d, kls, "Sub-dimensi bersama")
    if left_sub:
        axis_label(d, left_sub, "Sub-dimensi bersama")
    for t, text in labels.get("extra", []):
        axis_label(d, t, text)
    ly = bottom + 50
    if note:
        from buat_gambar_skema import BERSIH, font
        if not BERSIH:
            d.text((90 * 2, ly * 2), note, font=font(20), fill=INK, anchor="lm")
        ly += 45
    legend(d, 90, ly, LEGEND_N if sub else LEGEND_N[:4])
    legend(d, 900, ly, LEGEND_R)
    save(img, file)


def peminjam(name, key, nis=True):
    rows = [("PK", key, "INT"), ("", "user_id", "INT")]
    if nis:
        rows.append(("", "nis", "VARCHAR"))
    return Table(name, rows + [("", "nama", "VARCHAR"), ("FK", "kelas_key", "INT")], 0, 0, 430, DIM)


# ---------------------------------------------------------------------
def dw1():
    fact = Table("fact_peminjaman",
                 [("PK", "peminjaman_key", "INT"), ("", "loan_id", "INT"), ("FK", "buku_pinjam_key", "INT"),
                  ("FK", "waktu_pengajuan_key", "INT"), ("FK", "peminjam_pinjam_key", "INT"),
                  ("FK", "status_pinjam_key", "TINYINT"), ("", "jumlah_pengajuan", "TINYINT"),
                  ("", "jumlah_berhasil", "TINYINT"), ("", "jumlah_gagal", "TINYINT")], 0, 0, 560, FACT,
                 measures=["jumlah_pengajuan", "jumlah_berhasil", "jumlah_gagal"])
    buku = Table("dim_buku_pinjam", [("PK", "buku_pinjam_key", "INT"), ("", "book_id", "INT"),
                                     ("", "judul", "VARCHAR"), ("FK", "kategori_key", "INT"),
                                     ("", "status_aktif", "VARCHAR")], 0, 0, 430, DIM)
    khas = Table("dim_status_pinjam", [("PK", "status_pinjam_key", "TINYINT"), ("", "nama_status", "VARCHAR"),
                                       ("", "kelompok_status", "VARCHAR")], 0, 0, 430, KHAS)
    gambar("normalisasi-dw1-peminjaman-buku.png", "DW 1 (dinormalisasi): Analisis Peminjaman Buku",
           "Pembanding snowflake | buku -> kategori, siswa -> kelas, waktu -> bulan | sub-dimensi dipakai bersama DW 1-3",
           fact, buku, peminjam("dim_peminjam_pinjam", "peminjam_pinjam_key"), khas,
           {"fk_left": "buku_pinjam_key", "fk_right": "peminjam_pinjam_key", "left": "X: Buku",
            "right": "Z: Siswa", "waktu": "Y: Waktu pengajuan", "khas": "Dimensi khas: Status peminjaman"},
           left_sub=kategori(40),
           note="Query per kategori / tingkat / kuartal kini butuh dua kali join (fakta -> dimensi -> sub-dimensi).")


def dw2():
    fact = Table("fact_ulasan",
                 [("PK", "ulasan_key", "INT"), ("", "review_id", "INT"), ("FK", "buku_ulasan_key", "INT"),
                  ("FK", "waktu_ulasan_key", "INT"), ("FK", "pengulas_key", "INT"), ("FK", "rating_key", "TINYINT"),
                  ("", "rating", "TINYINT"), ("", "jumlah_ulasan", "TINYINT"),
                  ("", "flag_rating_tinggi", "TINYINT"), ("", "flag_rating_rendah", "TINYINT")], 0, 0, 560, FACT,
                 measures=["rating", "jumlah_ulasan", "flag_rating_tinggi", "flag_rating_rendah"])
    buku = Table("dim_buku_ulasan", [("PK", "buku_ulasan_key", "INT"), ("", "book_id", "INT"),
                                     ("", "judul", "VARCHAR"), ("", "penerbit", "VARCHAR"),
                                     ("", "tahun_terbit", "SMALLINT"), ("FK", "kategori_key", "INT")], 0, 0, 430, DIM)
    khas = Table("dim_rating", [("PK", "rating_key", "TINYINT"), ("", "nilai", "TINYINT"),
                                ("", "label_rating", "VARCHAR"), ("", "kelompok_rating", "VARCHAR")], 0, 0, 430, KHAS)
    by = TOP + buku.h + 110
    bridge = Table("bridge_buku_penulis", [("PK", "buku_ulasan_key", "INT"), ("PK", "penulis_key", "INT"),
                                           ("", "urutan", "TINYINT")], 470, by, 430, SUB)
    penulis = Table("dim_penulis", [("PK", "penulis_key", "INT"), ("", "author_id", "INT"),
                                    ("", "nama_penulis", "VARCHAR")], 40, by, 380, SUB)
    yb = max(TOP + fact.h + 110, by + bridge.h + 90)
    gambar("normalisasi-dw2-kepuasan-pembaca.png", "DW 2 (dinormalisasi): Analisis Kepuasan Pembaca",
           "Pembanding snowflake | buku -> kategori & penulis (bridge), siswa -> kelas, waktu -> bulan",
           fact, buku, peminjam("dim_pengulas", "pengulas_key", nis=False), khas,
           {"fk_left": "buku_ulasan_key", "fk_right": "pengulas_key", "left": "X: Buku",
            "right": "Z: Siswa pengulas", "waktu": "Y: Waktu ulasan", "khas": "Dimensi khas: Rating"},
           extra=[bridge, penulis],
           extra_links=[(buku, "b", 0.5, bridge, "t", 0.5, "1", "N", SUB),
                        (penulis, "r", "penulis_key", bridge, "l", "penulis_key", "1", "N", SUB)],
           y_bawah=yb, left_sub=kategori(40), sub=True,
           note="bridge_buku_penulis = tabel jembatan buku-penulis (banyak-ke-banyak): kolom penulis yang semula teks gabungan (\"A, B\") dipecah jadi satu baris per penulis (1NF).")


def dw3():
    fact = Table("fact_keterlambatan",
                 [("PK", "keterlambatan_key", "INT"), ("", "loan_id", "INT"), ("FK", "waktu_jatuh_tempo_key", "INT"),
                  ("FK", "kategori_key", "INT"), ("FK", "peminjam_kembali_key", "INT"),
                  ("FK", "status_kembali_key", "TINYINT"), ("", "flag_terlambat", "TINYINT"),
                  ("", "flag_tepat_waktu", "TINYINT"), ("", "hari_terlambat", "SMALLINT"), ("", "nominal_denda", "INT")],
                 0, 0, 560, FACT, measures=["flag_terlambat", "flag_tepat_waktu", "hari_terlambat", "nominal_denda"])
    kat = kategori(0, 430)
    khas = Table("dim_status_kembali", [("PK", "status_kembali_key", "TINYINT"), ("", "status_pengembalian", "VARCHAR"),
                                        ("", "posisi_buku", "VARCHAR"), ("", "status_denda", "VARCHAR")], 0, 0, 430, KHAS)
    gambar("normalisasi-dw3-keterlambatan.png", "DW 3 (dinormalisasi): Analisis Keterlambatan Pengembalian",
           "Pembanding snowflake | siswa -> kelas, waktu -> bulan | dim_kategori_buku kini dipakai bersama DW 1-3",
           fact, kat, peminjam("dim_peminjam_kembali", "peminjam_kembali_key"), khas,
           {"fk_left": "kategori_key", "fk_right": "peminjam_kembali_key", "left": "Y: Kategori buku (bersama)",
            "right": "Z: Siswa", "waktu": "X: Waktu jatuh tempo", "khas": "Dimensi khas: Status pengembalian"},
           note="dim_kategori_buku tidak berubah isinya; bedanya kini juga dirujuk dim_buku_pinjam (DW 1) dan dim_buku_ulasan (DW 2).")


if __name__ == "__main__":
    dw1()
    dw2()
    dw3()
