"""Flowchart Smart Reading Room (gaya flowchart draw.io seperti di laporan), dibagi dua:
flowchart-peminjam (alur menuju halaman peminjam) dan flowchart-admin (alur menuju dashboard admin).

Jalankan:  python docs/flowchart/buat_flowchart.py docs/flowchart
Hasil:     flowchart-peminjam.svg/.drawio dan flowchart-admin.svg/.drawio
           (svg untuk dirender ke PNG, drawio bisa diedit di app.diagrams.net)
"""
import math
import os
import sys
from xml.sax.saxutils import escape

FONT = 'Helvetica, Arial, sans-serif'
UKURAN_HURUF = 13
TINGGI_BARIS = 16
MIRING = 20  # kemiringan jajaran genjang (input/output)

# ---------------------------------------------------------------
# simpul: id -> (bentuk, x tengah, y tengah, lebar, tinggi, teks)
#   term = Start/End, proc = proses, dec = keputusan, io = input/output
# panah: (asal, sisi asal, tujuan, sisi tujuan, titik belok, label, posisi label)
#   sisi: t = atas, b = bawah, l = kiri, r = kanan; "r:0.25" = sisi kanan, 1/4 dari atas
#   posisi label: 0..1 sepanjang garis, atau negatif = sekian px sebelum ujung panah
# ---------------------------------------------------------------

# ===================== FLOWCHART PEMINJAM =====================
# kolom: 180 daftar, 245 cabang kiri, 450 alur utama, 660-880 lupa password, 750 cabang kanan
N_PEMINJAM = {
    # awal: perkenalan, daftar, login (+ lupa password)
    'start':    ('term', 450, 40, 120, 50, 'Start'),
    'landing':  ('proc', 450, 120, 180, 56, 'Halaman perkenalan\n(landing page)'),
    'akun':     ('dec', 450, 225, 150, 90, 'Sudah punya\nakun?'),
    'daftar':   ('proc', 180, 225, 180, 56, 'Daftar sebagai Siswa /\nTenaga Pendidik'),
    'isi':      ('proc', 180, 330, 190, 64, 'Isi data diri\n(NIS/NIP, kelas/jabatan,\nno. telepon, password)'),
    'login':    ('proc', 450, 330, 180, 56, 'Login\n(NIS/NIP + password)'),
    'berhasil': ('dec', 450, 435, 150, 90, 'Berhasil\nlogin?'),
    'lupa':     ('dec', 660, 435, 150, 90, 'Lupa\npassword?'),
    'reset':    ('proc', 880, 435, 190, 56, 'Reset password\n(NIS/NIP + email)'),

    # peminjaman
    'katalog':  ('proc', 450, 545, 190, 56, 'Halaman katalog buku\n(cari & filter)'),
    'detail':   ('proc', 450, 640, 190, 56, 'Pilih buku →\nhalaman detail buku'),
    'stok':     ('dec', 450, 740, 150, 90, 'Stok\ntersedia?'),
    'form':     ('proc', 450, 850, 190, 56, 'Isi form peminjaman\n(durasi 1–14 hari)'),
    'valid':    ('dec', 450, 955, 150, 90, 'Sesuai aturan\npeminjaman?'),
    'pesan':    ('io', 245, 955, 160, 56, 'Tampil pesan\nkesalahan'),
    'simpan':   ('proc', 450, 1060, 190, 56, 'Pengajuan tersimpan\n(status Menunggu)'),
    'tunggu':   ('proc', 450, 1165, 190, 64, 'Tunggu konfirmasi admin\n(halaman Peminjaman\nSaya & Notifikasi)'),
    'batal':    ('dec', 450, 1275, 150, 90, 'Batalkan\npengajuan?'),
    'dibatal':  ('proc', 750, 1275, 200, 56, 'Pengajuan dibatalkan\n(data dihapus)'),
    'setuju':   ('dec', 450, 1385, 150, 90, 'Disetujui?'),
    'gagal':    ('io', 750, 1385, 230, 64, 'Status Gagal\n(ditolak, atau tidak diambil\n→ stok dikembalikan)'),
    'notif':    ('io', 450, 1495, 210, 56, 'Notifikasi: ambil buku\nmaks. 2 hari'),
    'diambil':  ('dec', 450, 1605, 150, 90, 'Diambil dalam\n2 hari?'),
    'dipinjam': ('proc', 450, 1715, 210, 56, 'Ambil buku di perpustakaan\n(admin menandai Dipinjam)'),
    'kembali':  ('proc', 450, 1815, 190, 56, 'Kembalikan buku\nke perpustakaan'),
    'telat':    ('dec', 450, 1915, 150, 90, 'Terlambat?'),
    'denda':    ('proc', 245, 1915, 160, 56, 'Bayar denda\nRp1.000/hari'),
    'selesai':  ('proc', 450, 2025, 190, 56, 'Status Dikembalikan\n(ditandai admin)'),
    'ulasan':   ('proc', 450, 2125, 190, 56, 'Beri ulasan & rating\n(opsional, kapan saja)'),
    'end':      ('term', 450, 2225, 120, 50, 'End'),
}

E_PEMINJAM = [
    ('start', 'b', 'landing', 't', [], '', .5),
    ('landing', 'b', 'akun', 't', [], '', .5),
    ('akun', 'l', 'daftar', 'r', [], 'tidak', .5),
    ('akun', 'b', 'login', 't', [], 'ya', .45),
    ('daftar', 'b', 'isi', 't', [], '', .5),
    ('isi', 'r', 'login', 'l', [], '', .5),
    ('login', 'b', 'berhasil', 't', [], '', .5),
    ('berhasil', 'r', 'lupa', 'l', [], 'tidak', .5),
    ('lupa', 't', 'login', 'r:0.75', [(660, 344)], 'tidak', .3),
    ('lupa', 'r', 'reset', 'l', [], 'ya', .5),
    ('reset', 't', 'login', 'r:0.25', [(880, 316)], '', .5),
    ('berhasil', 'b', 'katalog', 't', [], 'ya', .45),

    ('katalog', 'b', 'detail', 't', [], '', .5),
    ('detail', 'b', 'stok', 't', [], '', .5),
    ('stok', 'l', 'katalog', 'l', [(260, 740), (260, 545)], 'tidak', .2),
    ('stok', 'b', 'form', 't', [], 'ya', .45),
    ('form', 'b', 'valid', 't', [], '', .5),
    ('valid', 'l', 'pesan', 'r', [], 'tidak', .5),
    ('pesan', 't', 'form', 'l', [(245, 850)], '', .5),
    ('valid', 'b', 'simpan', 't', [], 'ya', .45),
    ('simpan', 'b', 'tunggu', 't', [], '', .5),
    ('tunggu', 'b', 'batal', 't', [], '', .5),
    ('batal', 'r', 'dibatal', 'l', [], 'ya', .5),
    ('dibatal', 'r', 'end', 'r', [(910, 1275), (910, 2225)], '', .5),
    ('batal', 'b', 'setuju', 't', [], 'tidak', .45),
    ('setuju', 'r', 'gagal', 'l', [], 'tidak', .5),
    ('setuju', 'b', 'notif', 't', [], 'ya', .45),
    ('notif', 'b', 'diambil', 't', [], '', .5),
    ('diambil', 'r', 'gagal', 'b', [(750, 1605)], 'tidak', .3),
    ('gagal', 'r', 'end', 'r', [(910, 1385), (910, 2225)], '', .5),
    ('diambil', 'b', 'dipinjam', 't', [], 'ya', .45),
    ('dipinjam', 'b', 'kembali', 't', [], '', .5),
    ('kembali', 'b', 'telat', 't', [], '', .5),
    ('telat', 'l', 'denda', 'r', [], 'ya', .5),
    ('denda', 'b', 'selesai', 'l', [(245, 2025)], '', .5),
    ('telat', 'b', 'selesai', 't', [], 'tidak', .45),
    ('selesai', 'b', 'ulasan', 't', [], '', .5),
    ('ulasan', 'b', 'end', 't', [], '', .5),
]

# ======================= FLOWCHART ADMIN =======================
# akun admin tidak bisa didaftarkan dari aplikasi (ditambahkan lewat database), jadi alurnya langsung login.
# menu dashboard bercabang ke 4 kolom: 320 Request Masuk (110 tolak), 680 Riwayat (480 denda, 880 gagal),
# 1070 Kelola data, 1260 Data Peminjam; semua cabang bertemu lagi di y = 1270 sebelum "Selesai?"
BUS_MENU, BUS_AKHIR = 605, 1270
N_ADMIN = {
    'start':    ('term', 680, 40, 120, 50, 'Start'),
    'landing':  ('proc', 680, 120, 180, 56, 'Halaman perkenalan\n(landing page)'),
    'login':    ('proc', 680, 220, 180, 56, 'Login admin\n(NIS/NIP + password)'),
    'berhasil': ('dec', 680, 325, 150, 90, 'Berhasil\nlogin?'),
    'lupa':     ('dec', 900, 325, 150, 90, 'Lupa\npassword?'),
    'reset':    ('proc', 1120, 325, 190, 56, 'Reset password\n(NIS/NIP + email)'),
    'dash':     ('proc', 680, 430, 200, 56, 'Dashboard admin\n(ringkasan & terlambat)'),
    'menu':     ('dec', 680, 540, 150, 90, 'Pilih menu'),

    # Request Masuk
    'request':  ('proc', 320, 670, 190, 56, 'Halaman Request Masuk\n(status Menunggu)'),
    'putus':    ('dec', 320, 790, 170, 96, 'Stok tersedia\n& disetujui?'),
    'tolak':    ('proc', 110, 790, 160, 64, 'Tolak: status Gagal\n+ kirim notifikasi'),
    'konfirm':  ('proc', 320, 905, 190, 64, 'Konfirmasi: stok −1,\nstatus Dikonfirmasi\n+ kirim notifikasi'),

    # Riwayat
    'riwayat':  ('proc', 680, 670, 200, 56, 'Halaman Riwayat\nPeminjaman'),
    'diambil':  ('dec', 680, 790, 150, 90, 'Diambil dalam\n2 hari?'),
    'gagal':    ('proc', 880, 790, 160, 64, 'Otomatis Gagal\n(stok +1)\n+ kirim notifikasi'),
    'dipinjam': ('proc', 680, 900, 200, 64, 'Tandai Dipinjam\n(tanggal pinjam diisi)\n+ kirim notifikasi'),
    'telat':    ('dec', 680, 1010, 150, 90, 'Dikembalikan\nterlambat?'),
    'denda':    ('proc', 480, 1010, 160, 56, 'Kembalikan & lunasi\ndenda Rp1.000/hari'),
    'kembali':  ('proc', 680, 1115, 200, 56, 'Tandai Kembali'),
    'tutup':    ('proc', 680, 1220, 200, 64, 'Status Dikembalikan\n(stok +1)\n+ kirim notifikasi'),

    # Kelola data & Data Peminjam
    'kelola':   ('proc', 1070, 670, 180, 56, 'Kelola buku, kategori,\npenulis & ulasan'),
    'simpan':   ('proc', 1070, 780, 180, 56, 'Tambah / ubah / hapus\n(tercatat di admin_logs)'),
    'peminjam': ('proc', 1260, 674, 170, 64, 'Data Peminjam\n(cari & lihat pinjaman\naktif/terlambat)'),

    'selesai':  ('dec', 680, 1330, 150, 90, 'Selesai?'),
    'logout':   ('proc', 680, 1440, 170, 56, 'Logout'),
    'end':      ('term', 680, 1530, 120, 50, 'End'),
}


def ke_menu(x, tujuan, label):
    """cabang dari 'Pilih menu': turun ke garis bersama, lalu ke kolom menu tujuan"""
    return ('menu', 'b', tujuan, 't', [(680, BUS_MENU), (x, BUS_MENU)], label, -20)


def ke_akhir(asal, x):
    """akhir cabang: turun ke garis bersama di bawah, lalu masuk ke keputusan 'Selesai?'"""
    return (asal, 'b', 'selesai', 't', [(x, BUS_AKHIR), (680, BUS_AKHIR)], '', .5)


E_ADMIN = [
    ('start', 'b', 'landing', 't', [], '', .5),
    ('landing', 'b', 'login', 't', [], '', .5),
    ('login', 'b', 'berhasil', 't', [], '', .5),
    ('berhasil', 'r', 'lupa', 'l', [], 'tidak', .5),
    ('lupa', 't', 'login', 'r:0.75', [(900, 234)], 'tidak', .3),
    ('lupa', 'r', 'reset', 'l', [], 'ya', .5),
    ('reset', 't', 'login', 'r:0.25', [(1120, 206)], '', .5),
    ('berhasil', 'b', 'dash', 't', [], 'ya', .45),
    ('dash', 'b', 'menu', 't', [], '', .5),

    ke_menu(320, 'request', 'Request Masuk'),
    ('menu', 'b', 'riwayat', 't', [], 'Riwayat', -20),
    ke_menu(1070, 'kelola', 'Kelola data'),
    ke_menu(1260, 'peminjam', 'Data Peminjam'),

    ('request', 'b', 'putus', 't', [], '', .5),
    ('putus', 'l', 'tolak', 'r', [], 'tidak', .5),
    ('putus', 'b', 'konfirm', 't', [], 'ya', .45),
    ke_akhir('tolak', 110),
    ke_akhir('konfirm', 320),

    ('riwayat', 'b', 'diambil', 't', [], '', .5),
    ('diambil', 'r', 'gagal', 'l', [], 'tidak', .5),
    ('diambil', 'b', 'dipinjam', 't', [], 'ya', .45),
    ke_akhir('gagal', 880),
    ('dipinjam', 'b', 'telat', 't', [], '', .5),
    ('telat', 'l', 'denda', 'r', [], 'ya', .5),
    ('telat', 'b', 'kembali', 't', [], 'tidak', .45),
    ('denda', 'b', 'tutup', 'l', [(480, 1220)], '', .5),
    ('kembali', 'b', 'tutup', 't', [], '', .5),
    ('tutup', 'b', 'selesai', 't', [], '', .5),

    ('kelola', 'b', 'simpan', 't', [], '', .5),
    ke_akhir('simpan', 1070),
    ke_akhir('peminjam', 1260),

    ('selesai', 'l', 'dash', 'l', [(12, 1330), (12, 430)], 'tidak', .03),
    ('selesai', 'b', 'logout', 't', [], 'ya', .45),
    ('logout', 'b', 'end', 't', [], '', .5),
]

FLOWCHART = {
    # nama file: (simpul, panah, lebar, tinggi, nama halaman di draw.io)
    'flowchart-peminjam': (N_PEMINJAM, E_PEMINJAM, 1000, 2275, 'Flowchart Peminjam'),
    'flowchart-admin':    (N_ADMIN, E_ADMIN, 1370, 1580, 'Flowchart Admin'),
}


def posisi_sisi(sisi):
    """sisi -> (x, y) relatif terhadap kotak (0..1), seperti exitX/exitY di draw.io; "r:0.25" = kanan, 1/4 dari atas"""
    nama, _, fraksi = sisi.partition(':')
    f = float(fraksi) if fraksi else 0.5
    return {'t': (f, 0), 'b': (f, 1), 'l': (0, f), 'r': (1, f)}[nama]


def titik_sisi(N, nama, sisi):
    """titik pada tepi bentuk tempat panah keluar / masuk"""
    bentuk, x, y, w, h, _ = N[nama]
    fx, fy = posisi_sisi(sisi)
    geser = MIRING / 2 if bentuk == 'io' and fx in (0, 1) else 0
    return x - w / 2 + fx * w + (geser if fx == 0 else -geser), y - h / 2 + fy * h


def jalur(N, e):
    asal, sa, tujuan, st, belok, *_ = e
    return [titik_sisi(N, asal, sa)] + list(belok) + [titik_sisi(N, tujuan, st)]


def fraksi_label(N, e):
    """posisi label sebagai fraksi 0..1 panjang garis (negatif = sekian px sebelum ujung panah)"""
    titik, posisi = jalur(N, e), e[6]
    if posisi >= 0:
        return posisi
    panjang = sum(math.dist(a, b) for a, b in zip(titik, titik[1:]))
    return max(0.0, 1 + posisi / panjang)


def titik_sepanjang(titik, t):
    """titik pada fraksi t dari panjang garis (sama dengan cara draw.io menaruh label)"""
    seg = [(a, b, math.dist(a, b)) for a, b in zip(titik, titik[1:])]
    sisa = sum(s[2] for s in seg) * t
    for a, b, d in seg:
        if sisa <= d and d > 0:
            f = sisa / d
            return a[0] + (b[0] - a[0]) * f, a[1] + (b[1] - a[1]) * f
        sisa -= d
    return titik[-1]


def teks_svg(x, y, teks, tebal=False):
    baris = teks.split('\n')
    y0 = y - (len(baris) - 1) * TINGGI_BARIS / 2 + UKURAN_HURUF * 0.35
    tspan = ''.join(
        f'<tspan x="{x:.1f}" y="{y0 + i * TINGGI_BARIS:.1f}">{escape(b)}</tspan>' for i, b in enumerate(baris)
    )
    return (f'<text text-anchor="middle" font-family="{FONT}" font-size="{UKURAN_HURUF}"'
            + (' font-weight="bold"' if tebal else '') + f'>{tspan}</text>')


def buat_svg(N, E, lebar_kanvas, tinggi_kanvas):
    out = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{lebar_kanvas}" height="{tinggi_kanvas}" '
           f'viewBox="0 0 {lebar_kanvas} {tinggi_kanvas}">',
           '<defs><marker id="panah" viewBox="0 0 10 10" refX="10" refY="5" markerWidth="9" markerHeight="9" '
           'orient="auto-start-reverse"><path d="M0,1 L10,5 L0,9 z" fill="#000"/></marker></defs>',
           f'<rect width="{lebar_kanvas}" height="{tinggi_kanvas}" fill="#fff"/>']
    garis = 'fill="none" stroke="#000" stroke-width="1.3"'

    for e in E:
        d = ' '.join(f'{x:.1f},{y:.1f}' for x, y in jalur(N, e))
        out.append(f'<polyline points="{d}" {garis} marker-end="url(#panah)"/>')

    for bentuk, x, y, w, h, teks in N.values():
        if bentuk == 'term':
            out.append(f'<ellipse cx="{x}" cy="{y}" rx="{w / 2}" ry="{h / 2}" fill="#fff" stroke="#000" stroke-width="1.3"/>')
        elif bentuk == 'dec':
            out.append(f'<polygon points="{x},{y - h / 2} {x + w / 2},{y} {x},{y + h / 2} {x - w / 2},{y}" '
                       f'fill="#fff" stroke="#000" stroke-width="1.3"/>')
        elif bentuk == 'io':
            x0, x1, y0, y1 = x - w / 2, x + w / 2, y - h / 2, y + h / 2
            out.append(f'<polygon points="{x0 + MIRING},{y0} {x1},{y0} {x1 - MIRING},{y1} {x0},{y1}" '
                       f'fill="#fff" stroke="#000" stroke-width="1.3"/>')
        else:
            out.append(f'<rect x="{x - w / 2}" y="{y - h / 2}" width="{w}" height="{h}" fill="#fff" stroke="#000" stroke-width="1.3"/>')
        out.append(teks_svg(x, y, teks))

    # label panah (ya / tidak / nama menu) dengan latar putih agar tidak tertimpa garis
    for e in E:
        label = e[5]
        if not label:
            continue
        x, y = titik_sepanjang(jalur(N, e), fraksi_label(N, e))
        lebar = len(label) * 6.6 + 8
        out.append(f'<rect x="{x - lebar / 2:.1f}" y="{y - 9:.1f}" width="{lebar:.1f}" height="18" fill="#fff"/>')
        out.append(teks_svg(x, y, label))

    out.append('</svg>')
    return '\n'.join(out)


def buat_drawio(N, E, lebar_kanvas, tinggi_kanvas, judul):
    gaya = {
        'term': 'ellipse;whiteSpace=wrap;html=1;',
        'proc': 'rounded=0;whiteSpace=wrap;html=1;',
        'dec': 'rhombus;whiteSpace=wrap;html=1;',
        'io': f'shape=parallelogram;perimeter=parallelogramPerimeter;whiteSpace=wrap;html=1;fixedSize=1;size={MIRING};',
    }
    sel = ['<mxCell id="0"/>', '<mxCell id="1" parent="0"/>']

    for nama, (bentuk, x, y, w, h, teks) in N.items():
        sel.append(f'<mxCell id="{nama}" value="{escape(teks.replace(chr(10), "<br>"))}" '
                   f'style="{gaya[bentuk]}fontSize={UKURAN_HURUF};" vertex="1" parent="1">'
                   f'<mxGeometry x="{x - w / 2}" y="{y - h / 2}" width="{w}" height="{h}" as="geometry"/></mxCell>')

    for i, e in enumerate(E):
        asal, sa, tujuan, st, belok, label, _ = e
        (ex, ey), (nx, ny) = posisi_sisi(sa), posisi_sisi(st)
        style = ('edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;endArrow=classic;'
                 f'fontSize={UKURAN_HURUF};exitX={ex};exitY={ey};exitDx=0;exitDy=0;entryX={nx};entryY={ny};entryDx=0;entryDy=0;')
        titik = ''.join(f'<mxPoint x="{px}" y="{py}"/>' for px, py in belok)
        sel.append(f'<mxCell id="e{i}" value="{escape(label)}" style="{style}" edge="1" parent="1" '
                   f'source="{asal}" target="{tujuan}"><mxGeometry x="{fraksi_label(N, e) * 2 - 1:.2f}" relative="1" as="geometry">'
                   + ('<Array as="points">' + titik + '</Array>' if belok else '') + '</mxGeometry></mxCell>')

    return (f'<mxfile host="app.diagrams.net"><diagram name="{escape(judul)}" id="{escape(judul.lower().replace(" ", "-"))}">'
            f'<mxGraphModel dx="{lebar_kanvas}" dy="{tinggi_kanvas}" grid="1" gridSize="10" guides="1" tooltips="1" '
            f'connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="{lebar_kanvas}" pageHeight="{tinggi_kanvas}" '
            'math="0" shadow="0"><root>' + ''.join(sel) + '</root></mxGraphModel></diagram></mxfile>\n')


if __name__ == '__main__':
    folder = sys.argv[1] if len(sys.argv) > 1 else os.path.dirname(os.path.abspath(__file__))
    for nama, (N, E, lebar_kanvas, tinggi_kanvas, judul) in FLOWCHART.items():
        with open(os.path.join(folder, nama + '.svg'), 'w', encoding='utf-8') as f:
            f.write(buat_svg(N, E, lebar_kanvas, tinggi_kanvas))
        with open(os.path.join(folder, nama + '.drawio'), 'w', encoding='utf-8') as f:
            f.write(buat_drawio(N, E, lebar_kanvas, tinggi_kanvas, judul))
        print(f'{nama}.svg dan {nama}.drawio ({lebar_kanvas}x{tinggi_kanvas}) dibuat di', folder)
