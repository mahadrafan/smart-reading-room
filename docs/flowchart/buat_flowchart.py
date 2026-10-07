"""Flowchart sistem peminjaman Smart Reading Room (gaya flowchart draw.io seperti di laporan).

Jalankan:  python docs/flowchart/buat_flowchart.py docs/flowchart
Hasil:     flowchart.svg (untuk dirender ke PNG) dan flowchart.drawio (bisa diedit di app.diagrams.net)
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
# kolom: 95-110 cabang kiri, 300 peminjam, 450 awal, 600-660 cabang tengah, 880 admin, 1110 cabang admin
# ---------------------------------------------------------------
N = {
    # awal: perkenalan, daftar, login (+ lupa password), cek role
    'start':    ('term', 450, 40, 120, 50, 'Start'),
    'landing':  ('proc', 450, 120, 180, 56, 'Halaman perkenalan\n(landing page)'),
    'akun':     ('dec', 450, 225, 150, 90, 'Sudah punya\nakun?'),
    'daftar':   ('proc', 180, 225, 180, 56, 'Daftar sebagai Siswa /\nTenaga Pendidik'),
    'isi':      ('proc', 180, 330, 190, 64, 'Isi data diri\n(NIS/NIP, kelas/jabatan,\nno. telepon, password)'),
    'login':    ('proc', 450, 330, 180, 56, 'Login\n(NIS/NIP + password)'),
    'berhasil': ('dec', 450, 435, 150, 90, 'Berhasil\nlogin?'),
    'lupa':     ('dec', 660, 435, 150, 90, 'Lupa\npassword?'),
    'reset':    ('proc', 880, 435, 190, 56, 'Reset password\n(NIS/NIP + email)'),
    'role':     ('dec', 450, 545, 150, 90, 'Role?'),

    # jalur peminjam
    'katalog':  ('proc', 300, 640, 190, 56, 'Halaman katalog buku\n(cari & filter)'),
    'detail':   ('proc', 300, 735, 190, 56, 'Pilih buku →\nhalaman detail buku'),
    'stok':     ('dec', 300, 835, 150, 90, 'Stok\ntersedia?'),
    'form':     ('proc', 300, 945, 190, 56, 'Isi form peminjaman\n(durasi 1–14 hari)'),
    'valid':    ('dec', 300, 1050, 150, 90, 'Sesuai aturan\npeminjaman?'),
    'pesan':    ('io', 95, 1050, 160, 56, 'Tampil pesan\nkesalahan'),
    'simpan':   ('proc', 300, 1155, 190, 56, 'Pengajuan tersimpan\n(status Menunggu)'),
    'tunggu':   ('proc', 300, 1260, 190, 64, 'Tunggu konfirmasi admin\n(halaman Peminjaman\nSaya & Notifikasi)'),
    'batal':    ('dec', 300, 1370, 150, 90, 'Batalkan\npengajuan?'),
    'dibatal':  ('proc', 600, 1370, 200, 56, 'Pengajuan dibatalkan\n(data dihapus)'),
    'setuju':   ('dec', 300, 1480, 150, 90, 'Disetujui?'),
    'gagal':    ('io', 600, 1480, 230, 64, 'Status Gagal\n(ditolak, atau tidak diambil\n→ stok dikembalikan)'),
    'notif':    ('io', 300, 1590, 210, 56, 'Notifikasi: ambil buku\nmaks. 2 hari'),
    'diambil':  ('dec', 300, 1700, 150, 90, 'Diambil dalam\n2 hari?'),
    'dipinjam': ('proc', 300, 1810, 210, 56, 'Ambil buku di perpustakaan\n(admin menandai Dipinjam)'),
    'kembali':  ('proc', 300, 1910, 190, 56, 'Kembalikan buku\nke perpustakaan'),
    'telat':    ('dec', 300, 2010, 150, 90, 'Terlambat?'),
    'denda':    ('proc', 95, 2010, 160, 56, 'Bayar denda\nRp1.000/hari'),
    'selesai':  ('proc', 300, 2120, 190, 56, 'Status Dikembalikan\n(ditandai admin)'),
    'ulasan':   ('proc', 300, 2220, 190, 56, 'Beri ulasan & rating\n(opsional, kapan saja)'),
    'end':      ('term', 300, 2320, 120, 50, 'End'),

    # jalur admin
    'dash':     ('proc', 880, 640, 170, 56, 'Dashboard admin'),
    'menu':     ('dec', 880, 745, 150, 90, 'Pilih menu'),
    'kelola':   ('proc', 660, 745, 180, 64, 'Kelola buku, kategori,\npenulis, ulasan &\nakun admin'),
    'riwayat':  ('proc', 1110, 745, 180, 56, 'Halaman Riwayat\nPeminjaman'),
    'tandai':   ('proc', 1110, 870, 200, 72, 'Tandai buku diambil\n(Dipinjam) atau dikembalikan\n& lunasi denda'),
    'request':  ('proc', 880, 850, 170, 56, 'Halaman Request Masuk'),
    'putus':    ('dec', 880, 960, 150, 90, 'Setujui\npengajuan?'),
    'tolak':    ('proc', 660, 960, 170, 56, 'Tolak\n(status Gagal)'),
    'konfirm':  ('proc', 880, 1070, 180, 56, 'Konfirmasi: stok −1\n(status Dikonfirmasi)'),
    'kirim':    ('io', 880, 1170, 190, 56, 'Kirim notifikasi\nke peminjam'),
}

# ---------------------------------------------------------------
# panah: (asal, sisi asal, tujuan, sisi tujuan, titik belok, label, posisi label 0..1 sepanjang garis)
#   sisi: t = atas, b = bawah, l = kiri, r = kanan; "r:0.25" = sisi kanan, 1/4 dari atas
# ---------------------------------------------------------------
E = [
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
    ('berhasil', 'b', 'role', 't', [], 'ya', .45),
    ('role', 'l', 'katalog', 't', [(300, 545)], 'Peminjam', .3),
    ('role', 'r', 'dash', 't', [(880, 545)], 'Admin', .35),

    # peminjam
    ('katalog', 'b', 'detail', 't', [], '', .5),
    ('detail', 'b', 'stok', 't', [], '', .5),
    ('stok', 'l', 'katalog', 'l', [(110, 835), (110, 640)], 'tidak', .2),
    ('stok', 'b', 'form', 't', [], 'ya', .45),
    ('form', 'b', 'valid', 't', [], '', .5),
    ('valid', 'l', 'pesan', 'r', [], 'tidak', .5),
    ('pesan', 't', 'form', 'l', [(95, 945)], '', .5),
    ('valid', 'b', 'simpan', 't', [], 'ya', .45),
    ('simpan', 'b', 'tunggu', 't', [], '', .5),
    ('tunggu', 'b', 'batal', 't', [], '', .5),
    ('batal', 'r', 'dibatal', 'l', [], 'ya', .5),
    ('dibatal', 'r', 'end', 'r', [(760, 1370), (760, 2320)], '', .5),
    ('batal', 'b', 'setuju', 't', [], 'tidak', .45),
    ('setuju', 'r', 'gagal', 'l', [], 'tidak', .5),
    ('setuju', 'b', 'notif', 't', [], 'ya', .45),
    ('notif', 'b', 'diambil', 't', [], '', .5),
    ('diambil', 'r', 'gagal', 'b', [(600, 1700)], 'tidak', .3),
    ('gagal', 'r', 'end', 'r', [(760, 1480), (760, 2320)], '', .5),
    ('diambil', 'b', 'dipinjam', 't', [], 'ya', .45),
    ('dipinjam', 'b', 'kembali', 't', [], '', .5),
    ('kembali', 'b', 'telat', 't', [], '', .5),
    ('telat', 'l', 'denda', 'r', [], 'ya', .5),
    ('denda', 'b', 'selesai', 'l', [(95, 2120)], '', .5),
    ('telat', 'b', 'selesai', 't', [], 'tidak', .45),
    ('selesai', 'b', 'ulasan', 't', [], '', .5),
    ('ulasan', 'b', 'end', 't', [], '', .5),

    # admin
    ('dash', 'b', 'menu', 't', [], '', .5),
    ('menu', 'l', 'kelola', 'r', [], '', .5),
    ('kelola', 't', 'dash', 'l', [(660, 640)], '', .5),
    ('menu', 'r', 'riwayat', 'l', [], '', .5),
    ('riwayat', 'b', 'tandai', 't', [], '', .5),
    ('tandai', 'b', 'kirim', 'r', [(1110, 1170)], '', .5),
    ('menu', 'b', 'request', 't', [], '', .5),
    ('request', 'b', 'putus', 't', [], '', .5),
    ('putus', 'l', 'tolak', 'r', [], 'tidak', .5),
    ('putus', 'b', 'konfirm', 't', [], 'ya', .45),
    ('tolak', 'b', 'kirim', 'l', [(660, 1170)], '', .5),
    ('konfirm', 'b', 'kirim', 't', [], '', .5),
    ('kirim', 'b', 'tunggu', 'r', [(880, 1260)], '', .5),
]

LEBAR, TINGGI = 1240, 2370


def titik_sisi(nama, sisi):
    """titik pada tepi bentuk tempat panah keluar / masuk"""
    bentuk, x, y, w, h, _ = N[nama]
    fx, fy = posisi_sisi(sisi)
    geser = MIRING / 2 if bentuk == 'io' and fx in (0, 1) else 0
    return x - w / 2 + fx * w + (geser if fx == 0 else -geser), y - h / 2 + fy * h


def posisi_sisi(sisi):
    """sisi -> (x, y) relatif terhadap kotak (0..1), seperti exitX/exitY di draw.io; "r:0.25" = kanan, 1/4 dari atas"""
    nama, _, fraksi = sisi.partition(':')
    f = float(fraksi) if fraksi else 0.5
    return {'t': (f, 0), 'b': (f, 1), 'l': (0, f), 'r': (1, f)}[nama]


def jalur(e):
    asal, sa, tujuan, st, belok, *_ = e
    return [titik_sisi(asal, sa)] + list(belok) + [titik_sisi(tujuan, st)]


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


def buat_svg():
    out = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{LEBAR}" height="{TINGGI}" viewBox="0 0 {LEBAR} {TINGGI}">',
           '<defs><marker id="panah" viewBox="0 0 10 10" refX="10" refY="5" markerWidth="9" markerHeight="9" '
           'orient="auto-start-reverse"><path d="M0,1 L10,5 L0,9 z" fill="#000"/></marker></defs>',
           f'<rect width="{LEBAR}" height="{TINGGI}" fill="#fff"/>']
    garis = 'fill="none" stroke="#000" stroke-width="1.3"'

    for e in E:
        titik = jalur(e)
        d = ' '.join(f'{x:.1f},{y:.1f}' for x, y in titik)
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

    # label panah (ya / tidak / nama cabang) dengan latar putih agar tidak tertimpa garis
    for e in E:
        label, posisi = e[5], e[6]
        if not label:
            continue
        x, y = titik_sepanjang(jalur(e), posisi)
        lebar = len(label) * 6.6 + 8
        out.append(f'<rect x="{x - lebar / 2:.1f}" y="{y - 9:.1f}" width="{lebar:.1f}" height="18" fill="#fff"/>')
        out.append(teks_svg(x, y, label))

    out.append('</svg>')
    return '\n'.join(out)


def buat_drawio():
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
        asal, sa, tujuan, st, belok, label, posisi = e
        (ex, ey), (nx, ny) = posisi_sisi(sa), posisi_sisi(st)
        style = ('edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;endArrow=classic;'
                 f'fontSize={UKURAN_HURUF};exitX={ex};exitY={ey};exitDx=0;exitDy=0;entryX={nx};entryY={ny};entryDx=0;entryDy=0;')
        titik = ''.join(f'<mxPoint x="{px}" y="{py}"/>' for px, py in belok)
        sel.append(f'<mxCell id="e{i}" value="{escape(label)}" style="{style}" edge="1" parent="1" '
                   f'source="{asal}" target="{tujuan}"><mxGeometry x="{posisi * 2 - 1:.2f}" relative="1" as="geometry">'
                   + ('<Array as="points">' + titik + '</Array>' if belok else '') + '</mxGeometry></mxCell>')

    return ('<mxfile host="app.diagrams.net"><diagram name="Flowchart" id="flowchart-srr">'
            f'<mxGraphModel dx="{LEBAR}" dy="{TINGGI}" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" '
            f'fold="1" page="1" pageScale="1" pageWidth="{LEBAR}" pageHeight="{TINGGI}" math="0" shadow="0"><root>'
            + ''.join(sel) + '</root></mxGraphModel></diagram></mxfile>\n')


if __name__ == '__main__':
    folder = sys.argv[1] if len(sys.argv) > 1 else os.path.dirname(os.path.abspath(__file__))
    with open(os.path.join(folder, 'flowchart.svg'), 'w', encoding='utf-8') as f:
        f.write(buat_svg())
    with open(os.path.join(folder, 'flowchart.drawio'), 'w', encoding='utf-8') as f:
        f.write(buat_drawio())
    print('flowchart.svg dan flowchart.drawio dibuat di', folder)
