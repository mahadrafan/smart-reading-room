"""Generator database sumber dummy `basdat_sumber` (keputusan D3 dan D4).

Jalankan:  python buat_basdat_sumber.py
Hasil   :  02_basdat_sumber.sql  (struktur sama dengan basdat_github + seluruh data dummy)

Ciri data (deterministik, seed tetap -> hasil sama di semua laptop):
  * periode 1 Januari 2025 s.d. 21 November 2026 (tanggal setelahnya belum terjadi)
  * 60 siswa (20 per tingkat, 9 kelas), 2 admin, tanpa tenaga pendidik (D5)
  * 8 kategori, 40 buku fiktif (2 nonaktif sejak 2026), 30 penulis fiktif
  * +-1.000 peminjaman dengan semua status, sebagian terlambat / belum kembali / denda belum lunas
  * +-400 ulasan rating 1-5 (beberapa tanpa rating, untuk menguji aturan ETL)
Semua nama orang, judul buku, dan penerbit adalah fiktif.
"""
import os
import random
from datetime import date, datetime, timedelta

SEED = 20261122
AWAL, AKHIR = date(2025, 1, 1), date(2026, 11, 21)   # data terakhir = 21 Nov 2026
TARGET_PINJAM = 1000
OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "02_basdat_sumber.sql")
rng = random.Random(SEED)

# ---------------------------------------------------------------------------
# Master data
# ---------------------------------------------------------------------------
KATEGORI = ["Fiksi & Sastra", "Romance & Drama", "Komputer & Teknologi", "Ekonomi & Akuntansi",
            "Sains & Matematika", "Sejarah & Sosial", "Agama & Filsafat", "Pengembangan Diri"]
JUDUL = [
    ["Senja di Pelabuhan Tua", "Jejak Langkah di Tanah Merah", "Rumah di Ujung Gang",
     "Hikayat Pulau Seribu Kabut", "Surat untuk Arunika"],
    ["Dua Musim di Kota Hujan", "Sampai Jumpa di Stasiun Terakhir", "Kopi Pahit dan Janji Manis",
     "Sebelum Lampu Kota Padam", "Catatan Hati Kelas Dua Belas"],
    ["Dasar Pemrograman Python untuk SMA", "Belajar Basis Data dari Nol", "Jaringan Komputer untuk Pemula",
     "Membangun Website dengan HTML dan CSS", "Pengantar Kecerdasan Buatan"],
    ["Akuntansi Dasar untuk Siswa", "Ekonomi Mikro dalam Kehidupan Sehari-hari", "Mengenal Pasar Modal",
     "Kewirausahaan Remaja", "Manajemen Keuangan Pribadi"],
    ["Fisika Seru: Gerak dan Energi", "Kimia Dasar untuk Kelas X", "Biologi Sel dan Genetika",
     "Matematika Asyik: Aljabar dan Fungsi", "Persiapan UTBK Saintek"],
    ["Sejarah Nusantara dari Masa ke Masa", "Jejak Kerajaan Maritim", "Peristiwa Penting Abad ke-20",
     "Geografi Indonesia Bergambar", "Sosiologi untuk Remaja"],
    ["Filsafat untuk Pemula", "Berpikir Jernih ala Stoik", "Etika dalam Kehidupan Sehari-hari",
     "Toleransi dalam Keberagaman", "Mengenal Logika Dasar"],
    ["Kebiasaan Kecil Siswa Berprestasi", "Manajemen Waktu untuk Pelajar", "Public Speaking Tanpa Takut",
     "Menulis Esai yang Menarik", "Siap Kuliah: Panduan Memilih Jurusan"],
]
PENERBIT = ["Penerbit Cakrawala", "Pustaka Nusantara", "Lentera Ilmu", "Graha Edukasi", "Sinar Pena",
            "Bumi Aksara Muda"]
PENULIS = ["Andika Pratama", "Ayu Lestari", "Bagus Wicaksono", "Citra Maharani", "Dimas Saputra",
           "Dewi Anggraini", "Eko Prasetyo", "Fajar Nugroho", "Fitri Handayani", "Gilang Ramadhan",
           "Hana Safitri", "Indra Kurniawan", "Intan Permata", "Joko Santoso", "Kartika Sari",
           "Lukman Hakim", "Maya Puspita", "Nanda Firmansyah", "Nurul Hidayah", "Oki Setiawan",
           "Putri Amelia", "Rangga Mahendra", "Ratna Wulandari", "Rizky Aditya", "Sari Rahayu",
           "Taufik Hidayat", "Utami Dewanti", "Wahyu Hermawan", "Yoga Pradana", "Zahra Aulia"]
NONAKTIF = {22, 34}          # book_id yang dinonaktifkan mulai 1 Jan 2026
RENDAH = {9, 18, 27}          # buku dengan kualitas rendah (banyak rating jelek)

buku = []                    # (book_id, cat_id, judul, penerbit, tahun, stok, aktif, lokasi, kualitas)
bid = 0
for c, judul_kat in enumerate(JUDUL, start=1):
    for j in judul_kat:
        bid += 1
        q = rng.uniform(2.0, 2.5) if bid in RENDAH else rng.uniform(3.0, 4.7)
        buku.append(dict(id=bid, cat=c, judul=j, penerbit=PENERBIT[(bid * 5) % len(PENERBIT)],
                         tahun=rng.randint(2015, 2024), stok=rng.randint(4, 9),
                         aktif=0 if bid in NONAKTIF else 1,
                         lokasi=f"Rak {'FRKEMSAP'[c - 1]}-{(bid - 1) % 5 + 1:02d}", q=q))
buku_penulis = []            # (book_id, author_id, urutan)
for b in buku:
    a1 = (b["id"] * 7) % len(PENULIS) + 1
    buku_penulis.append((b["id"], a1, 1))
    if b["id"] % 6 == 0:     # sebagian buku ditulis dua orang
        a2 = (a1 + 10) % len(PENULIS) + 1
        buku_penulis.append((b["id"], a2, 2))

# ---------------------------------------------------------------------------
# Pengguna: 2 admin + 60 siswa
# ---------------------------------------------------------------------------
DEPAN = ["Adit", "Alya", "Bima", "Bunga", "Cahya", "Dafa", "Dinda", "Elang", "Farah", "Galih",
         "Hesti", "Ilham", "Jihan", "Kevin", "Laras", "Raka", "Nadia", "Naufal", "Olivia", "Pandu",
         "Qori", "Rafi", "Salsa", "Tegar", "Ulfa", "Vino", "Wulan", "Yusuf", "Zaki", "Anisa"]
BELAKANG = ["Saputra", "Lestari", "Pratama", "Wijaya", "Kusuma", "Ramadhani", "Hidayat", "Maharani",
            "Nugraha", "Permana"]
KELAS = {12: ["XII IPA 1", "XII IPA 2", "XII IPS 1"], 11: ["XI IPA 1", "XI IPA 2", "XI IPS 1"],
         10: ["X-1", "X-2", "X-3"]}
FAVORIT = {10: 1, 11: 3, 12: 5}   # kategori favorit tiap tingkat
TELAT = {10: 0.13, 11: 0.17, 12: 0.24}

admin = [(1, "ADM001", "Admin Perpustakaan 1"), (2, "ADM002", "Admin Perpustakaan 2")]
siswa = []
uid, k = 100, 0
for tingkat in (12, 11, 10):
    angkatan = {12: 2023, 11: 2024, 10: 2025}[tingkat]
    for i in range(20):
        uid += 1
        nama = f"{DEPAN[(k * 7) % 30]} {BELAKANG[(k * 3 + k // 10) % 10]}"
        k += 1
        daftar = (datetime(2024, 12, 16, 8) if tingkat != 10 else datetime(2025, 7, 14, 8)) \
            + timedelta(days=rng.randint(0, 9), minutes=rng.randint(0, 400))
        siswa.append(dict(id=uid, nis=f"{angkatan}{i + 1:04d}", nama=nama, tingkat=tingkat,
                          kelas=KELAS[tingkat][i % 3], daftar=daftar,
                          aktif=rng.choice([0.25, 0.6, 1.0, 1.0, 1.4, 2.0])))
nama_dipakai = [s["nama"] for s in siswa]
assert len(set(nama_dipakai)) == len(nama_dipakai), "nama siswa ganda"

# ---------------------------------------------------------------------------
# Peminjaman
# ---------------------------------------------------------------------------
BULAN_W = {1: 12, 2: 10, 3: 10, 4: 9, 5: 11, 6: 4, 7: 5, 8: 12, 9: 11, 10: 11, 11: 10, 12: 4}
hari = []
d = AWAL
while d <= AKHIR:
    w = (10 if d.weekday() < 5 else 3 if d.weekday() == 5 else 0) * BULAN_W[d.month] * (5 if d.year == 2025 else 6)
    hari.append((d, w))
    d += timedelta(days=1)
total_w = sum(w for _, w in hari)
populer = {b["id"]: rng.uniform(0.4, 2.2) for b in buku}

def pilih_buku(s, tgl):
    kandidat = [b for b in buku if b["aktif"] or tgl < date(2026, 1, 1)]
    if rng.random() < 0.4:
        kandidat = [b for b in kandidat if b["cat"] == FAVORIT[s["tingkat"]]]
    return rng.choices(kandidat, weights=[populer[b["id"]] for b in kandidat])[0]

akhir_dt = datetime(AKHIR.year, AKHIR.month, AKHIR.day, 23, 59)
loans, lid = [], 0
for tgl, w in hari:
    n = int(TARGET_PINJAM * w / total_w + rng.random())
    for _ in range(n):
        req = datetime(tgl.year, tgl.month, tgl.day, 7) + timedelta(minutes=rng.randint(0, 510))
        bisa = [s for s in siswa if s["daftar"] <= req]
        s = rng.choices(bisa, weights=[x["aktif"] for x in bisa])[0]
        b = pilih_buku(s, tgl)
        lid += 1
        L = dict(id=lid, user=s["id"], book=b["id"], req=req, loan=None, due=None, ret=None,
                 status="Menunggu", appr_by=None, appr_at=None, paid_at=None, paid_by=None, tingkat=s["tingkat"])
        appr = req + timedelta(hours=rng.randint(1, 30))
        baru = tgl >= AKHIR - timedelta(days=4)          # pengajuan beberapa hari terakhir
        if appr > akhir_dt or (baru and rng.random() < 0.4):
            loans.append(L); continue                     # masih Menunggu
        L["appr_at"], L["appr_by"] = appr, rng.choice([1, 2])
        if rng.random() < 0.08:                       # dikonfirmasi tetapi tidak diambil
            L["status"] = "Gagal"; loans.append(L); continue
        ambil = appr.date() + timedelta(days=rng.choice([0, 0, 1, 1, 2]))
        if ambil > AKHIR or (baru and rng.random() < 0.5):
            L["status"] = "Dikonfirmasi"; loans.append(L); continue
        durasi = 7 if rng.random() < 0.75 else 14
        L["loan"], L["due"] = ambil, ambil + timedelta(days=durasi)
        r = rng.random()
        if r < 0.015:                                 # tidak pernah dikembalikan
            kembali = None
        elif r < 0.015 + TELAT[s["tingkat"]]:
            telat = rng.choices([1, 2, 3, 4, 5, 6, 7, 9, 12, 16, 21], weights=[18, 15, 13, 10, 9, 7, 6, 5, 4, 3, 2])[0]
            kembali = L["due"] + timedelta(days=telat)
        else:
            kembali = ambil + timedelta(days=rng.randint(1, durasi))
        if kembali is None or kembali > AKHIR:
            L["status"] = "Dipinjam"
        else:
            L["status"], L["ret"] = "Dikembalikan", kembali
            if kembali > L["due"] and rng.random() < 0.85:
                bayar = datetime(kembali.year, kembali.month, kembali.day, 9) + \
                    timedelta(days=rng.randint(0, 4), minutes=rng.randint(0, 420))
                if bayar <= akhir_dt:
                    L["paid_at"], L["paid_by"] = bayar, rng.choice([1, 2])
        loans.append(L)

# ---------------------------------------------------------------------------
# Ulasan (satu per pasangan siswa-buku; dari peminjaman yang sudah dikembalikan)
# ---------------------------------------------------------------------------
TEKS = {1: ["Tidak sesuai harapan, sulit dipahami.", "Isinya membosankan dan banyak salah ketik."],
        2: ["Kurang menarik, penjelasannya terlalu singkat.", "Ada beberapa bagian bagus, tapi kebanyakan biasa saja."],
        3: ["Lumayan, cukup membantu.", "Bukunya standar, bisa dibaca saat senggang."],
        4: ["Bagus dan mudah dipahami.", "Isinya menarik, saya rekomendasikan ke teman."],
        5: ["Sangat bagus, salah satu buku favorit saya!", "Luar biasa, ingin baca lagi."]}
reviews, sudah, rid = [], set(), 0
for L in loans:
    if L["status"] != "Dikembalikan" or (L["user"], L["book"]) in sudah or rng.random() > 0.6:
        continue
    sudah.add((L["user"], L["book"]))
    q = next(b["q"] for b in buku if b["id"] == L["book"])
    rating = max(1, min(5, round(q + rng.choice([-2, -1, -1, 0, 0, 0, 0, 1, 1]) * 0.8)))
    waktu = datetime(L["ret"].year, L["ret"].month, L["ret"].day, 15) + \
        timedelta(days=rng.randint(0, 3), minutes=rng.randint(0, 300))
    if waktu > akhir_dt:
        continue
    rid += 1
    tanpa = rid % 97 == 0                             # beberapa ulasan tanpa rating
    reviews.append((rid, L["user"], L["book"], None if tanpa else rating,
                    "Belum sempat memberi nilai." if tanpa else rng.choice(TEKS[rating]), waktu))

# stok tersedia = stok - buku yang sedang dipinjam
dipinjam = {}
for L in loans:
    if L["status"] == "Dipinjam":
        dipinjam[L["book"]] = dipinjam.get(L["book"], 0) + 1
for b in buku:
    b["stok"] = max(b["stok"], dipinjam.get(b["id"], 0) + 1)
    b["tersedia"] = b["stok"] - dipinjam.get(b["id"], 0)

# ---------------------------------------------------------------------------
# Tulis SQL
# ---------------------------------------------------------------------------
def v(x):
    if x is None:
        return "NULL"
    if isinstance(x, (int, float)):
        return str(x)
    if isinstance(x, datetime):
        return f"'{x:%Y-%m-%d %H:%M:%S}'"
    if isinstance(x, date):
        return f"'{x:%Y-%m-%d}'"
    return "'" + str(x).replace("\\", "\\\\").replace("'", "''") + "'"

def insert(tabel, kolom, baris, per=200):
    out = []
    for i in range(0, len(baris), per):
        isi = ",\n".join("(" + ", ".join(v(x) for x in r) + ")" for r in baris[i:i + per])
        out.append(f"INSERT INTO {tabel} ({', '.join(kolom)}) VALUES\n{isi};")
    return "\n".join(out)

DDL = """
CREATE TABLE users (
  user_id int NOT NULL AUTO_INCREMENT,
  role enum('Peminjam','Admin') NOT NULL DEFAULT 'Peminjam',
  borrower_type enum('Siswa','Tenaga Pendidik') DEFAULT NULL,
  nim_nip varchar(20) NOT NULL,
  name varchar(100) NOT NULL,
  class varchar(20) DEFAULT NULL,
  job_title varchar(50) DEFAULT NULL,
  email varchar(100) NOT NULL,
  password varchar(255) NOT NULL,
  phone varchar(20) DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  remember_token varchar(100) DEFAULT NULL,
  PRIMARY KEY (user_id),
  UNIQUE KEY email (email),
  UNIQUE KEY uk_users_nim_nip (nim_nip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE categories (
  category_id int NOT NULL AUTO_INCREMENT,
  category_name varchar(100) NOT NULL,
  PRIMARY KEY (category_id),
  UNIQUE KEY uk_categories_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE authors (
  author_id int NOT NULL AUTO_INCREMENT,
  author_name varchar(100) NOT NULL,
  PRIMARY KEY (author_id),
  UNIQUE KEY uk_authors_name (author_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE books (
  book_id int NOT NULL AUTO_INCREMENT,
  category_id int NOT NULL,
  title varchar(200) NOT NULL,
  publisher varchar(100) DEFAULT NULL,
  publication_year year DEFAULT NULL,
  description text,
  cover_image varchar(255) DEFAULT NULL,
  location varchar(100) DEFAULT NULL,
  stock int DEFAULT '0',
  available_stock int DEFAULT '0',
  is_active tinyint(1) NOT NULL DEFAULT '1',
  created_by int DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by int DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (book_id),
  KEY category_id (category_id),
  CONSTRAINT fk_books_category_id FOREIGN KEY (category_id) REFERENCES categories (category_id) ON DELETE RESTRICT,
  CONSTRAINT fk_books_created_by FOREIGN KEY (created_by) REFERENCES users (user_id),
  CONSTRAINT fk_books_updated_by FOREIGN KEY (updated_by) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE book_authors (
  book_id int NOT NULL,
  author_id int NOT NULL,
  urutan tinyint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (book_id, author_id),
  CONSTRAINT book_authors_author_id_foreign FOREIGN KEY (author_id) REFERENCES authors (author_id) ON DELETE RESTRICT,
  CONSTRAINT book_authors_book_id_foreign FOREIGN KEY (book_id) REFERENCES books (book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE loans (
  loan_id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  book_id int NOT NULL,
  request_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  loan_date date DEFAULT NULL,
  due_date date DEFAULT NULL,
  return_date date DEFAULT NULL,
  status enum('Menunggu','Dikonfirmasi','Dipinjam','Gagal','Dikembalikan') NOT NULL DEFAULT 'Menunggu',
  approved_by int DEFAULT NULL,
  approved_at datetime DEFAULT NULL,
  fine_paid_at timestamp NULL DEFAULT NULL,
  fine_paid_by int DEFAULT NULL,
  PRIMARY KEY (loan_id),
  KEY user_id (user_id),
  KEY book_id (book_id),
  CONSTRAINT fk_loans_approved_by FOREIGN KEY (approved_by) REFERENCES users (user_id),
  CONSTRAINT fk_loans_fine_paid_by FOREIGN KEY (fine_paid_by) REFERENCES users (user_id) ON DELETE RESTRICT,
  CONSTRAINT loans_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id),
  CONSTRAINT loans_ibfk_2 FOREIGN KEY (book_id) REFERENCES books (book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE reviews (
  review_id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  book_id int NOT NULL,
  rating int DEFAULT NULL,
  review_text text,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id),
  UNIQUE KEY uk_reviews_user_book (user_id, book_id),
  KEY book_id (book_id),
  CONSTRAINT reviews_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id),
  CONSTRAINT reviews_ibfk_2 FOREIGN KEY (book_id) REFERENCES books (book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE notifications (
  notification_id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  loan_id int DEFAULT NULL,
  type varchar(50) DEFAULT NULL,
  message text,
  sent_at datetime DEFAULT CURRENT_TIMESTAMP,
  status varchar(20) DEFAULT NULL,
  PRIMARY KEY (notification_id),
  CONSTRAINT notifications_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id),
  CONSTRAINT notifications_ibfk_2 FOREIGN KEY (loan_id) REFERENCES loans (loan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE admin_logs (
  log_id int NOT NULL AUTO_INCREMENT,
  admin_id int NOT NULL,
  action varchar(50) NOT NULL,
  table_name varchar(50) NOT NULL,
  record_id int DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (log_id),
  CONSTRAINT fk_logs_admin FOREIGN KEY (admin_id) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
"""

HEADER = f"""-- =====================================================================
-- SMART READING ROOM - DATA WAREHOUSE (Kelompok G)
-- File 2 dari 4 : DATABASE SUMBER DUMMY `basdat_sumber` (keputusan D3 dan D4)
--
-- Struktur tabel sama dengan database aplikasi `basdat_github`, tetapi
-- seluruh isinya data dummy FIKTIF. `basdat_github` tidak disentuh.
-- Dibuat otomatis oleh buat_basdat_sumber.py (seed {SEED}); jangan diedit manual.
--
--   Periode   : {AWAL:%d %B %Y} s.d. {AKHIR:%d %B %Y}
--   Isi       : {len(KATEGORI)} kategori, {len(buku)} buku, {len(PENULIS)} penulis, {len(admin)} admin,
--               {len(siswa)} siswa, {len(loans)} peminjaman, {len(reviews)} ulasan
--   Catatan   : notifications dan admin_logs hanya strukturnya (tidak dipakai DW).
--               Akun dummy tidak bisa login (password tidak valid).
--
-- Cara pakai: phpMyAdmin > Import (atau: mysql -u root < 02_basdat_sumber.sql).
-- PERINGATAN: database basdat_sumber dihapus lalu dibuat ulang.
-- Urutan: 01_ddl -> 02_basdat_sumber -> 03_etl -> 04_query_analisis (01 dan 02 boleh tertukar)
-- =====================================================================

DROP DATABASE IF EXISTS basdat_sumber;
CREATE DATABASE basdat_sumber CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE basdat_sumber;
SET FOREIGN_KEY_CHECKS = 0;
"""

parts = [HEADER, DDL]
parts.append(insert("users", ["user_id", "role", "borrower_type", "nim_nip", "name", "class", "job_title",
                              "email", "password", "phone", "created_at"],
                    [(a, "Admin", None, n, nm, None, None, f"{n.lower()}@srr.test", "!DUMMY-TIDAK-BISA-LOGIN",
                      None, datetime(2024, 12, 20, 8)) for a, n, nm in admin] +
                    [(s["id"], "Peminjam", "Siswa", s["nis"], s["nama"], s["kelas"], None,
                      f"siswa{s['nis']}@srr.test", "!DUMMY-TIDAK-BISA-LOGIN", None, s["daftar"]) for s in siswa]))
parts.append(insert("categories", ["category_id", "category_name"], list(enumerate(KATEGORI, start=1))))
parts.append(insert("authors", ["author_id", "author_name"], list(enumerate(PENULIS, start=1))))
parts.append(insert("books", ["book_id", "category_id", "title", "publisher", "publication_year", "description",
                              "location", "stock", "available_stock", "is_active", "created_by", "created_at",
                              "updated_by", "updated_at"],
                    [(b["id"], b["cat"], b["judul"], b["penerbit"], b["tahun"], "Buku fiktif untuk data dummy.",
                      b["lokasi"], b["stok"], b["tersedia"], b["aktif"], 1, datetime(2024, 12, 20, 9),
                      None if b["aktif"] else 1, None if b["aktif"] else datetime(2026, 1, 1, 8)) for b in buku]))
parts.append(insert("book_authors", ["book_id", "author_id", "urutan"], buku_penulis))
parts.append(insert("loans", ["loan_id", "user_id", "book_id", "request_date", "loan_date", "due_date",
                              "return_date", "status", "approved_by", "approved_at", "fine_paid_at", "fine_paid_by"],
                    [(L["id"], L["user"], L["book"], L["req"], L["loan"], L["due"], L["ret"], L["status"],
                      L["appr_by"], L["appr_at"], L["paid_at"], L["paid_by"]) for L in loans]))
parts.append(insert("reviews", ["review_id", "user_id", "book_id", "rating", "review_text", "created_at"], reviews))
parts.append("""
SET FOREIGN_KEY_CHECKS = 1;

-- Ringkasan isi
SELECT 'users' AS tabel, COUNT(*) AS jumlah FROM users
UNION ALL SELECT 'books', COUNT(*) FROM books
UNION ALL SELECT 'loans', COUNT(*) FROM loans
UNION ALL SELECT 'reviews', COUNT(*) FROM reviews;
SELECT status, COUNT(*) AS jumlah FROM loans GROUP BY status;
""")
with open(OUT, "w", encoding="utf-8", newline="\n") as f:
    f.write("\n".join(parts))

st = {}
for L in loans:
    st[L["status"]] = st.get(L["status"], 0) + 1
print("dibuat:", OUT)
print("siswa", len(siswa), "| peminjaman", len(loans), st, "| ulasan", len(reviews))
