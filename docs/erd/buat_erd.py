# ERD konseptual Smart Reading Room (notasi Chen, gaya mengikuti ERD di proposal referensi).
# Satu sumber tata letak -> SVG (untuk dicek & laporan) dan .drawio (bisa diedit di draw.io).
import math, sys, os, html

OUT = sys.argv[1] if len(sys.argv) > 1 else '.'
W, H = 3350, 2480
FONT = 'Helvetica, Arial, sans-serif'
EW, EH = 150, 60          # ukuran kotak entitas
DW, DH = 170, 84          # ukuran belah ketupat relasi

# ---------- entitas ----------
E = {
    'users':         (1150, 820),
    'admin_logs':    (400, 820),
    'loans':         (2350, 820),
    'notifications': (1750, 1300),
    'reviews':       (1750, 1650),
    'books':         (2350, 1650),
    'authors':       (3220, 1650),
    'categories':    (2350, 2330),
}

# ---------- relasi: nama, pusat, lalu dua kaki (entitas, kardinalitas, titik belok dari belah ketupat ke entitas) ----------
R = [
    ('mencatat',              (750, 820),   [('users', '1', []), ('admin_logs', 'N', [])]),
    ('mengajukan',            (1750, 800),  [('users', '1', [(1260, 800)]), ('loans', 'N', [(2240, 800)])]),
    ('menyetujui',            (1750, 960),  [('users', '1', [(1400, 960), (1400, 820)]), ('loans', 'N', [(2100, 960), (2100, 820)])]),
    ('mencatat\ndenda lunas', (1750, 1100), [('users', '1', [(1340, 1100), (1340, 840)]), ('loans', 'N', [(2160, 1100), (2160, 840)])]),
    ('menerima',              (1450, 1300), [('users', '1', [(1180, 1300)]), ('notifications', 'N', [])]),
    ('memicu',                (2050, 1300), [('loans', '1', [(2300, 1300)]), ('notifications', 'N', [])]),
    ('dipinjam',              (2350, 1220), [('loans', 'N', []), ('books', '1', [])]),
    ('menulis',               (1450, 1650), [('users', '1', [(1150, 1650)]), ('reviews', 'N', [])]),
    ('diulas',                (2050, 1650), [('books', '1', []), ('reviews', 'N', [])]),
    ('ditulis oleh',          (2920, 1650), [('books', 'M', []), ('authors', 'N', [])]),
    ('mengelompokkan',        (2350, 2080), [('categories', '1', []), ('books', 'N', [])]),
    ('menambah /\nmengubah',  (1700, 1950), [('users', '1', [(1110, 1950)]), ('books', 'N', [(2250, 1950)])]),
]
# titik masuk ke entitas digeser agar garis sejajar tidak menumpuk: (relasi, entitas) -> (dx, dy) dari pusat entitas
MASUK = {
    ('mengajukan', 'users'): (0, -20), ('mengajukan', 'loans'): (0, -20),
    ('menyetujui', 'users'): (0, 0),   ('menyetujui', 'loans'): (0, 0),
    ('mencatat\ndenda lunas', 'users'): (0, 20), ('mencatat\ndenda lunas', 'loans'): (0, 20),
    ('menerima', 'users'): (30, 0), ('menulis', 'users'): (0, 0), ('menambah /\nmengubah', 'users'): (-40, 0),
    ('memicu', 'loans'): (-50, 0), ('menambah /\nmengubah', 'books'): (-100, 0),
}

# ---------- atribut: pemilik, label, jenis (pk / biasa / turunan), sudut awal (derajat), jari-jari ----------
def busur(pemilik, labels, a0, a1, rx, ry):
    n = len(labels)
    hasil = []
    for i, (lab, jenis) in enumerate(labels):
        a = math.radians(a0 + (a1 - a0) * (i / (n - 1) if n > 1 else 0.5))
        hasil.append([pemilik, lab, jenis, E.get(pemilik, (0, 0))[0] + rx * math.cos(a), E.get(pemilik, (0, 0))[1] - ry * math.sin(a)])
    return hasil

A = []
A += busur('users', [('user_id', 'pk'), ('role', ''), ('borrower_type', ''), ('nim_nip', ''), ('name', ''), ('class', ''),
                     ('job_title', ''), ('email', ''), ('password', ''), ('phone', ''), ('created_at', '')], 15, 160, 430, 330)
A += busur('admin_logs', [('log_id', 'pk'), ('admin_id', ''), ('action', ''), ('table_name', ''), ('record_id', ''),
                          ('description', ''), ('created_at', '')], 45, 310, 300, 260)
A += busur('loans', [('loan_id', 'pk'), ('user_id', ''), ('book_id', ''), ('request_date', ''), ('loan_date', ''), ('due_date', ''),
                     ('return_date', ''), ('status', ''), ('approved_by', ''), ('approved_at', ''), ('fine_paid_at', ''),
                     ('fine_paid_by', ''), ('jumlah_denda', 'turunan'), ('tenggat_pengambilan', 'turunan')], 80, -72, 440, 340)
A += busur('notifications', [('notification_id', 'pk'), ('user_id', ''), ('loan_id', ''), ('type', ''), ('message', ''),
                             ('sent_at', ''), ('status', '')], 212, 328, 330, 170)
A += busur('reviews', [('review_id', 'pk'), ('user_id', ''), ('book_id', ''), ('rating', ''), ('review_text', ''),
                       ('created_at', '')], 205, 335, 300, 150)
A += busur('books', [('book_id', 'pk'), ('category_id', ''), ('title', ''), ('publisher', ''), ('publication_year', ''),
                     ('description', ''), ('cover_image', ''), ('location', ''), ('stock', ''), ('available_stock', '')],
           78, 18, 440, 340)
A += busur('books', [('is_active', ''), ('created_by', ''), ('updated_by', ''), ('created_at', ''), ('updated_at', ''),
                     ('jumlah_dipinjam', 'turunan'), ('rata_rating', 'turunan')], -18, -78, 440, 330)
A += busur('authors', [('author_id', 'pk'), ('author_name', '')], 100, -100, 60, 120)
A += busur('categories', [('category_id', 'pk'), ('category_name', '')], 180, 0, 260, 0)
# atribut relasi M:N (menjadi kolom urutan di tabel book_authors)
d = dict((r[0], r[1]) for r in R)
A.append(['rel:ditulis oleh', 'urutan', '', d['ditulis oleh'][0], d['ditulis oleh'][1] - 120])

def lebar_attr(lab):
    return max(92, len(lab) * 7.4 + 30)
AH = 36

# ---------- geometri bantu ----------
def kaki_titik(rel, kaki):
    """titik garis dari pusat belah ketupat ke titik masuk entitas"""
    nama, pusat, _ = rel
    ent, kard, belok = kaki
    ex, ey = E[ent]
    dx, dy = MASUK.get((nama, ent), (0, 0))
    return [pusat] + belok + [(ex + dx, ey + dy)]

def potong_kotak(p_luar, p_dalam, cx, cy, w, h):
    """titik potong segmen (p_luar -> p_dalam) dengan tepi kotak berpusat (cx, cy)"""
    (x1, y1), (x2, y2) = p_luar, p_dalam
    best = None
    for t_i in range(0, 1001):
        t = t_i / 1000
        x, y = x1 + (x2 - x1) * t, y1 + (y2 - y1) * t
        if abs(x - cx) <= w / 2 and abs(y - cy) <= h / 2:
            best = (x, y); break
    return best or p_dalam

segmen = []   # semua segmen garis relasi (untuk menjauhkan atribut dari garis)
for rel in R:
    for kaki in rel[2]:
        pts = kaki_titik(rel, kaki)
        for a, b in zip(pts, pts[1:]):
            segmen.append((a, b))

def jarak_titik_segmen(px, py, a, b):
    (x1, y1), (x2, y2) = a, b
    dx, dy = x2 - x1, y2 - y1
    L = dx * dx + dy * dy or 1
    t = max(0, min(1, ((px - x1) * dx + (py - y1) * dy) / L))
    qx, qy = x1 + t * dx, y1 + t * dy
    return math.hypot(px - qx, py - qy), qx, qy

def pemilik_xy(attr):
    p = attr[0]
    if p.startswith('rel:'):
        return d[p[4:]]
    return E[p]

# ---------- relaksasi: atribut saling menjauh, menjauh dari garis & bentuk, tetap dekat pemiliknya ----------
kotak_tetap = [(x, y, EW + 30, EH + 30) for (x, y) in E.values()] + [(x, y, DW + 20, DH + 20) for (_, (x, y), _) in R]
for it in range(400):
    for i, a in enumerate(A):
        w = lebar_attr(a[1]); fx = fy = 0
        # antar atribut
        for j, b in enumerate(A):
            if i == j: continue
            wb = lebar_attr(b[1])
            ox = (w + wb) / 2 + 14 - abs(a[3] - b[3]); oy = (AH + AH) / 2 + 10 - abs(a[4] - b[4])
            if ox > 0 and oy > 0:
                if ox < oy * 2.2: fx += math.copysign(ox, a[3] - b[3] or 1) * 0.5
                else:             fy += math.copysign(oy, a[4] - b[4] or 1) * 0.5
        # entitas & belah ketupat
        for (bx, by, bw, bh) in kotak_tetap:
            ox = (w + bw) / 2 - abs(a[3] - bx); oy = (AH + bh) / 2 - abs(a[4] - by)
            if ox > 0 and oy > 0:
                if ox < oy * 2.2: fx += math.copysign(ox, a[3] - bx or 1) * 0.6
                else:             fy += math.copysign(oy, a[4] - by or 1) * 0.6
        # garis relasi
        for sa, sb in segmen:
            dist, qx, qy = jarak_titik_segmen(a[3], a[4], sa, sb)
            batas = AH / 2 + 14 if abs(sa[1] - sb[1]) < 1 else w / 2 + 14
            if dist < batas:
                vx, vy = a[3] - qx, a[4] - qy
                n = math.hypot(vx, vy) or 1
                fx += vx / n * (batas - dist) * 0.6; fy += vy / n * (batas - dist) * 0.6
        # tetap dekat pemilik
        px, py = pemilik_xy(a)
        jarak = math.hypot(a[3] - px, a[4] - py)
        if jarak > 520:
            fx += (px - a[3]) * 0.02; fy += (py - a[4]) * 0.02
        a[3] += max(-12, min(12, fx)); a[4] += max(-12, min(12, fy))
        a[3] = max(w / 2 + 20, min(W - w / 2 - 20, a[3])); a[4] = max(AH / 2 + 20, min(H - AH / 2 - 20, a[4]))

# ---------- geser seluruh diagram ke atas agar tidak ada ruang kosong di bawah judul ----------
atas = min([a[4] - AH / 2 for a in A] + [y - EH / 2 for (_, y) in E.values()] + [y - DH / 2 for (_, (_, y), _) in R])
GY = 100 - atas
E = {k: (x, y + GY) for k, (x, y) in E.items()}
R = [(n, (x, y + GY), [(e, k, [(bx, by + GY) for bx, by in b]) for (e, k, b) in kaki]) for (n, (x, y), kaki) in R]
d = dict((r[0], r[1]) for r in R)
for a in A:
    a[4] += GY
H = int(max([a[4] + AH / 2 for a in A] + [y + EH / 2 for (_, y) in E.values()]) + 60)
H = int(max(H, 2090 + GY + 300 + 60))

# ---------- SVG ----------
esc = html.escape
s = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}" font-family="{FONT}" font-size="15">',
     f'<rect width="{W}" height="{H}" fill="#ffffff"/>',
     '<g stroke="#000" stroke-width="1.3" fill="none">']

def crowfoot(p, q):
    """crow's foot 'satu atau banyak' di titik p (tepi entitas), q = arah dari entitas keluar"""
    (px, py), (qx, qy) = p, q
    ux, uy = qx - px, qy - py; n = math.hypot(ux, uy) or 1; ux, uy = ux / n, uy / n
    vx, vy = -uy, ux
    tip = (px + ux * 16, py + uy * 16)
    bar = (px + ux * 21, py + uy * 21)
    out = [f'<path d="M{tip[0]:.1f},{tip[1]:.1f} L{px + vx*9:.1f},{py + vy*9:.1f} M{tip[0]:.1f},{tip[1]:.1f} L{px:.1f},{py:.1f} M{tip[0]:.1f},{tip[1]:.1f} L{px - vx*9:.1f},{py - vy*9:.1f}"/>',
           f'<path d="M{bar[0] + vx*9:.1f},{bar[1] + vy*9:.1f} L{bar[0] - vx*9:.1f},{bar[1] - vy*9:.1f}"/>']
    return out

def satu(p, q):
    """garis tunggal 'tepat satu' (dua garis tegak) di titik p"""
    (px, py), (qx, qy) = p, q
    ux, uy = qx - px, qy - py; n = math.hypot(ux, uy) or 1; ux, uy = ux / n, uy / n
    vx, vy = -uy, ux
    o = []
    for k in (12, 18):
        bx, by = px + ux * k, py + uy * k
        o.append(f'<path d="M{bx + vx*8:.1f},{by + vy*8:.1f} L{bx - vx*8:.1f},{by - vy*8:.1f}"/>')
    return o

label_kard = []
for a in A:   # garis atribut (di bawah bentuk)
    px, py = pemilik_xy(a)
    s.append(f'<line x1="{a[3]:.1f}" y1="{a[4]:.1f}" x2="{px}" y2="{py}"/>')
for rel in R:
    for kaki in rel[2]:
        pts = kaki_titik(rel, kaki)
        ent, kard, _ = kaki
        ex, ey = E[ent]
        tepi = potong_kotak(pts[-2], pts[-1], ex, ey, EW, EH)
        garis = pts[:-1] + [tepi]
        s.append('<polyline points="' + ' '.join(f'{x:.1f},{y:.1f}' for x, y in garis) + '"/>')
        s += crowfoot(tepi, pts[-2]) if kard in ('N', 'M') else satu(tepi, pts[-2])
        # label kardinalitas sedikit di luar tepi, di sisi garis
        ux, uy = pts[-2][0] - tepi[0], pts[-2][1] - tepi[1]; n = math.hypot(ux, uy) or 1; ux, uy = ux / n, uy / n
        label_kard.append((tepi[0] + ux * 34 - uy * 14, tepi[1] + uy * 34 + ux * 14, kard))
s.append('</g>')

s.append('<g stroke="#000" stroke-width="1.3" fill="#fff">')
for a in A:
    w = lebar_attr(a[1])
    dash = ' stroke-dasharray="6 4"' if a[2] == 'turunan' else ''
    s.append(f'<ellipse cx="{a[3]:.1f}" cy="{a[4]:.1f}" rx="{w/2:.1f}" ry="{AH/2}"{dash}/>')
for (nama, (x, y), _) in R:
    s.append(f'<polygon points="{x},{y - DH/2} {x + DW/2},{y} {x},{y + DH/2} {x - DW/2},{y}"/>')
for nama, (x, y) in E.items():
    s.append(f'<rect x="{x - EW/2}" y="{y - EH/2}" width="{EW}" height="{EH}"/>')
s.append('</g>')

s.append('<g fill="#000" text-anchor="middle" dominant-baseline="central">')
for a in A:
    gaya = ' font-weight="bold" text-decoration="underline"' if a[2] == 'pk' else ''
    s.append(f'<text x="{a[3]:.1f}" y="{a[4]:.1f}"{gaya}>{esc(a[1])}</text>')
for (nama, (x, y), _) in R:
    baris = nama.split('\n')
    for k, b in enumerate(baris):
        s.append(f'<text x="{x}" y="{y + (k - (len(baris) - 1) / 2) * 18:.1f}">{esc(b)}</text>')
for nama, (x, y) in E.items():
    s.append(f'<text x="{x}" y="{y}" font-size="16">{esc(nama)}</text>')
for (x, y, k) in label_kard:
    s.append(f'<text x="{x:.1f}" y="{y:.1f}" font-size="15" font-weight="bold">{k}</text>')
s.append('</g>')

# keterangan notasi
lx, ly = 120, 2090 + GY
s.append(f'<g font-size="14" stroke="#000" stroke-width="1.2" fill="#fff">'
         f'<rect x="{lx}" y="{ly}" width="560" height="300" fill="#fff"/>'
         f'<rect x="{lx+24}" y="{ly+46}" width="70" height="30"/>'
         f'<polygon points="{lx+59},{ly+92} {lx+94},{ly+112} {lx+59},{ly+132} {lx+24},{ly+112}"/>'
         f'<ellipse cx="{lx+59}" cy="{ly+158}" rx="35" ry="15"/>'
         f'<ellipse cx="{lx+59}" cy="{ly+196}" rx="35" ry="15" stroke-dasharray="6 4"/>'
         f'<path d="M{lx+24},{ly+240} H{lx+94} M{lx+78},{ly+240} L{lx+94},{ly+231} M{lx+78},{ly+240} L{lx+94},{ly+249} M{lx+73},{ly+231} V{ly+249}" fill="none"/>'
         f'<path d="M{lx+24},{ly+275} H{lx+94} M{lx+80},{ly+267} V{ly+283} M{lx+86},{ly+267} V{ly+283}" fill="none"/>'
         '</g>')
s.append(f'<g font-size="14" fill="#000" dominant-baseline="central">'
         f'<text x="{lx+24}" y="{ly+22}" font-weight="bold" font-size="15">Keterangan</text>'
         f'<text x="{lx+112}" y="{ly+61}">Entitas</text>'
         f'<text x="{lx+112}" y="{ly+112}">Relasi</text>'
         f'<text x="{lx+112}" y="{ly+158}">Atribut (<tspan font-weight="bold" text-decoration="underline">primary_key</tspan> = tebal bergaris bawah)</text>'
         f'<text x="{lx+112}" y="{ly+196}">Atribut turunan (dihitung, tidak disimpan)</text>'
         f'<text x="{lx+112}" y="{ly+240}">Banyak (N / M)</text>'
         f'<text x="{lx+112}" y="{ly+275}">Tepat satu (1)</text>'
         '</g>')
s.append(f'<text x="{W/2}" y="46" text-anchor="middle" font-size="26" font-weight="bold" font-family="{FONT}">ERD Konseptual Smart Reading Room</text>')
s.append('</svg>')
open(os.path.join(OUT, 'erd-konseptual.svg'), 'w', encoding='utf-8').write('\n'.join(s))

# ---------- draw.io ----------
sel = ['<mxfile host="app.diagrams.net"><diagram name="ERD Konseptual" id="erd"><mxGraphModel dx="1400" dy="900" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="' + str(W) + '" pageHeight="' + str(H) + '" math="0" shadow="0"><root><mxCell id="0"/><mxCell id="1" parent="0"/>']
nid = [1]
def baru():
    nid[0] += 1; return f'c{nid[0]}'
def nilai(t): return esc(t, quote=True).replace('\n', '&#xa;')
idE, idR = {}, {}
dasar = 'whiteSpace=wrap;html=1;fontFamily=Helvetica;fontSize=13;strokeColor=#000000;fillColor=#ffffff;'
for nama, (x, y) in E.items():
    i = baru(); idE[nama] = i
    sel.append(f'<mxCell id="{i}" value="{nilai(nama)}" style="rounded=0;{dasar}" vertex="1" parent="1"><mxGeometry x="{x-EW/2}" y="{y-EH/2}" width="{EW}" height="{EH}" as="geometry"/></mxCell>')
for (nama, (x, y), _) in R:
    i = baru(); idR[nama] = i
    sel.append(f'<mxCell id="{i}" value="{nilai(nama)}" style="rhombus;{dasar}" vertex="1" parent="1"><mxGeometry x="{x-DW/2}" y="{y-DH/2}" width="{DW}" height="{DH}" as="geometry"/></mxCell>')
for a in A:
    i = baru(); w = lebar_attr(a[1])
    teks = f'<b><u>{esc(a[1])}</u></b>' if a[2] == 'pk' else esc(a[1])
    dash = 'dashed=1;' if a[2] == 'turunan' else ''
    sel.append(f'<mxCell id="{i}" value="{esc(teks, quote=True)}" style="ellipse;{dash}{dasar}" vertex="1" parent="1"><mxGeometry x="{a[3]-w/2:.0f}" y="{a[4]-AH/2:.0f}" width="{w:.0f}" height="{AH}" as="geometry"/></mxCell>')
    sumber = idR[a[0][4:]] if a[0].startswith('rel:') else idE[a[0]]
    sel.append(f'<mxCell id="{baru()}" style="endArrow=none;html=1;strokeColor=#000000;" edge="1" parent="1" source="{i}" target="{sumber}"><mxGeometry relative="1" as="geometry"/></mxCell>')
for rel in R:
    nama = rel[0]
    for kaki in rel[2]:
        ent, kard, belok = kaki
        ujung = 'ERoneToMany' if kard in ('N', 'M') else 'ERmandOne'
        titik = ''.join(f'<mxPoint x="{x}" y="{y}"/>' for x, y in belok)
        dx, dy = MASUK.get((nama, ent), (0, 0))
        tx = 0.5 + dx / EW if dy == 0 else 0.5 + dx / EW
        ty = 0.5 + dy / EH
        sel.append(f'<mxCell id="{baru()}" value="{kard}" style="endArrow={ujung};endFill=0;endSize=12;html=1;strokeColor=#000000;fontStyle=1;fontSize=13;labelPosition=right;align=left;edgeStyle=none;exitX=0.5;exitY=0.5;exitDx=0;exitDy=0;" edge="1" parent="1" source="{idR[nama]}" target="{idE[ent]}"><mxGeometry x="0.8" relative="1" as="geometry">' + (f'<Array as="points">{titik}</Array>' if titik else '') + '</mxGeometry></mxCell>')
sel.append('</root></mxGraphModel></diagram></mxfile>')
open(os.path.join(OUT, 'erd-konseptual.drawio'), 'w', encoding='utf-8').write(''.join(sel))
print('ok', len(A), 'atribut,', len(R), 'relasi')
