"""Generator data dummy NEXA.

Menghasilkan dua file dari satu sumber data:
  backend/application/database/dummy.json    -> dibaca oleh: php index.php tools seed
  database/nexa_dump.sql                      -> skema + data, untuk dibuka di DB Browser for SQLite

Jalankan dari folder root proyek:  python tools/generate_dummy.py
Ubah data dummy di file ini, lalu jalankan ulang. Jangan edit dummy.json secara manual.
"""
import json, os, sqlite3, crypt

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
TABLES = ['roles', 'users', 'cost_categories', 'motors', 'inspections', 'inspection_items', 'motor_costs',
          'inventory_items', 'inventory_transactions', 'sparepart_usage', 'vehicle_documents', 'customers',
          'sales', 'asset_activities']
T = {k: [] for k in TABLES}
clock = {'n': 0}


def ts(date):
    """Timestamp berurutan agar urutan aktivitas di timeline konsisten."""
    clock['n'] += 1
    m = clock['n']
    return f"{date} {8 + (m // 60) % 10:02d}:{m % 60:02d}:00"


def nid(t):
    return len(T[t]) + 1


STATUS_LABEL = {'masuk': 'Baru masuk', 'inspeksi': 'Sedang diperiksa', 'perbaikan': 'Dalam perbaikan',
                'siap': 'Siap dijual', 'booking': 'Booking', 'terjual': 'Terjual'}
# Harus sama dengan application/config/nexa.php ($checklist)
CHECK = [
    ('mesin', 'Kondisi mesin', 35, [('m1', 'Suara mesin'), ('m2', 'Kebocoran oli'), ('m3', 'Sistem pengapian'), ('m4', 'Performa tarikan')]),
    ('listrik', 'Sistem kelistrikan', 20, [('l1', 'Aki'), ('l2', 'Lampu depan/belakang'), ('l3', 'Lampu sein'), ('l4', 'Klakson'), ('l5', 'Starter')]),
    ('rem', 'Pengereman & body', 30, [('r1', 'Ketebalan kampas rem'), ('r2', 'Fungsi rem depan/belakang'), ('r3', 'Kondisi fisik body'), ('r4', 'Ketersediaan spion'), ('r5', 'Keausan ban')]),
    ('dok', 'Kelengkapan dokumen', 15, [('d1', 'Nomor rangka sesuai'), ('d2', 'Nomor mesin sesuai'), ('d3', 'STNK'), ('d4', 'BPKB')]),
]
VAL = {'baik': 1, 'perlu': .5, 'rusak': 0}


def rp(n):
    return 'Rp ' + f"{int(n):,}".replace(',', '.')


# ---------------- master data ----------------
for i, (c, n) in enumerate([('admin', 'Administrator'), ('owner', 'Pemilik Usaha'), ('ops', 'Staff Operasional'), ('sales', 'Staff Penjualan')], 1):
    T['roles'].append({'id': i, 'code': c, 'name': n})
ROLE = {r['code']: r['id'] for r in T['roles']}
USERS = [('Alifya Putri Aisyka', 'admin', 'admin@nexa.local', 'admin123', 'admin'),
         ('Akmal Lutfan Hasibuan', 'pemilik', 'pemilik@nexa.local', 'pemilik123', 'owner'),
         ('Muhammad Kemal Pasha', 'operasional', 'ops@nexa.local', 'ops123', 'ops'),
         ('Mulyo Saputra', 'penjualan', 'sales@nexa.local', 'sales123', 'sales'),
         ('Firmansyah', 'mekanik', 'mekanik@nexa.local', 'mekanik123', 'ops')]
for i, (n, u, e, p, r) in enumerate(USERS, 1):
    T['users'].append({'id': i, 'role_id': ROLE[r], 'name': n, 'username': u, 'email': e, 'password': p,
                       'is_active': 1, 'created_at': '2026-08-01 08:00:00', 'updated_at': '2026-08-01 08:00:00'})
A, OWNER, K, S, F = 1, 2, 3, 4, 5

CATS = [('Operasional', 'Pengambilan, pengiriman, bahan bakar, transportasi'),
        ('Perbaikan', 'Aksesori, sparepart dari luar gudang, bahan pendukung, jasa bengkel'),
        ('Surat-surat', 'Pajak, STNK, BPKB, balik nama'),
        ('Penyimpanan', 'Sewa gudang atau tempat penyimpanan'),
        ('Lainnya', 'Biaya lain yang terkait motor')]
for i, (c, d) in enumerate(CATS, 1):
    T['cost_categories'].append({'id': i, 'name': c, 'description': d})
CAT = {c: i for i, (c, _) in enumerate(CATS, 1)}


# ---------------- helper "transaksi" (meniru logika aplikasi) ----------------
def log(mid, typ, title, detail, date, by, status=None):
    T['asset_activities'].append({'id': nid('asset_activities'), 'motor_id': mid, 'activity_date': date, 'type': typ,
                                  'status': status, 'title': title, 'detail': detail, 'created_by': by, 'created_at': ts(date)})


def motor(d, by):
    mid = nid('motors')
    T['motors'].append({'id': mid, 'code': f'NX-{mid:03d}', 'brand': d['brand'], 'model': d['model'], 'year': d['year'],
                        'plate_number': d['plate'], 'color': d['color'], 'frame_number': d['frame'], 'engine_number': d['engine'],
                        'purchase_date': d['date'], 'purchase_price': d['price'], 'purchase_source': d['source'],
                        'target_price': d['target'], 'condition_note': d.get('note', ''), 'status': 'masuk',
                        'health_score': None, 'created_at': ts(d['date']), 'updated_at': ts(d['date'])})
    log(mid, 'masuk', 'Motor masuk', f"Dibeli {rp(d['price'])} dari {d['source']}", d['date'], by, 'masuk')
    return mid


def M(mid):
    return T['motors'][mid - 1]


def status(mid, st, note, by, date):
    M(mid)['status'] = st
    M(mid)['updated_at'] = ts(date)
    log(mid, 'status', 'Status: ' + STATUS_LABEL[st], note, date, by, st)


def cost(mid, cat, item, amount, date, by, note=''):
    T['motor_costs'].append({'id': nid('motor_costs'), 'motor_id': mid, 'category_id': CAT[cat], 'item': item, 'amount': amount,
                             'cost_date': date, 'note': note, 'created_by': by, 'created_at': ts(date)})
    log(mid, 'biaya', f'Biaya {cat.lower()}: {item}', rp(amount) + (', ' + note if note else ''), date, by)


def item(code, name, unit, price, stock, minimum, loc):
    iid = nid('inventory_items')
    T['inventory_items'].append({'id': iid, 'code': code, 'name': name, 'unit': unit, 'unit_price': price, 'stock': 0,
                                 'min_stock': minimum, 'location': loc, 'created_at': '2026-08-15 08:00:00',
                                 'updated_at': '2026-08-15 08:00:00'})
    stock_in(iid, stock, price, 'Stok awal', '2026-08-15', K)
    return iid


def stock_in(iid, qty, price, supplier, date, by, note=''):
    it = T['inventory_items'][iid - 1]
    it['stock'] += qty
    it['unit_price'] = price
    T['inventory_transactions'].append({'id': nid('inventory_transactions'), 'item_id': iid, 'type': 'in', 'qty': qty,
                                        'unit_price': price, 'trx_date': date, 'supplier': supplier, 'motor_id': None,
                                        'note': note, 'created_by': by, 'created_at': ts(date)})


def stock_out(iid, qty, mid, date, by, note=''):
    it = T['inventory_items'][iid - 1]
    assert it['stock'] >= qty, it['name']
    it['stock'] -= qty
    tid = nid('inventory_transactions')
    T['inventory_transactions'].append({'id': tid, 'item_id': iid, 'type': 'out', 'qty': qty, 'unit_price': it['unit_price'],
                                        'trx_date': date, 'supplier': None, 'motor_id': mid, 'note': note,
                                        'created_by': by, 'created_at': ts(date)})
    if mid:
        T['sparepart_usage'].append({'id': nid('sparepart_usage'), 'motor_id': mid, 'item_id': iid, 'transaction_id': tid,
                                     'qty': qty, 'unit_price': it['unit_price'], 'total': qty * it['unit_price'],
                                     'usage_date': date, 'created_at': ts(date)})
        log(mid, 'sparepart', f"Pakai {it['name']} x {qty} {it['unit']}",
            rp(qty * it['unit_price']) + ' diambil dari stok gudang', date, by)


def inspect(mid, over, notes, by, date):
    res = {k: over.get(k, 'baik') for g in CHECK for k, _ in g[3]}
    score = round(sum(g[2] * sum(VAL[res[k]] for k, _ in g[3]) / len(g[3]) for g in CHECK))
    insid = nid('inspections')
    T['inspections'].append({'id': insid, 'motor_id': mid, 'inspection_date': date, 'inspector': T['users'][by - 1]['name'],
                             'notes': notes, 'score': score, 'created_by': by, 'created_at': ts(date)})
    for gk, _, _, its in CHECK:
        for k, label in its:
            T['inspection_items'].append({'id': nid('inspection_items'), 'inspection_id': insid, 'group_key': gk,
                                          'item_key': k, 'item_label': label, 'result': res[k]})
    M(mid)['health_score'] = score
    lab = {k: l for g in CHECK for k, l in g[3]}
    perlu = [lab[k] for k, v in res.items() if v == 'perlu']
    rusak = [lab[k] for k, v in res.items() if v == 'rusak']
    parts = [('Perlu perbaikan: ' + ', '.join(perlu)) if perlu else '', ('Rusak/tidak ada: ' + ', '.join(rusak)) if rusak else '']
    log(mid, 'inspeksi', f'Inspeksi selesai, Health Score {score}', '. '.join(p for p in parts if p) or 'Semua item dalam kondisi baik', date, by)


def doc(mid, typ, st, num, exp, date, by=K):
    T['vehicle_documents'].append({'id': nid('vehicle_documents'), 'motor_id': mid, 'doc_type': typ, 'doc_number': num,
                                   'status': st, 'expiry_date': exp or None, 'note': '', 'created_at': ts(date), 'updated_at': ts(date)})
    log(mid, 'dokumen', f'Dokumen dicatat: {typ}', st + (f', no. {num}' if num else ''), date, by)


def customer(name, phone, addr):
    cid = nid('customers')
    T['customers'].append({'id': cid, 'name': name, 'phone': phone, 'address': addr, 'created_at': '2026-09-01 08:00:00'})
    return cid


def booking(mid, cid, price, dp, date, by):
    sid = nid('sales')
    T['sales'].append({'id': sid, 'motor_id': mid, 'customer_id': cid, 'booking_date': date, 'sale_date': None, 'price': price,
                       'dp': dp, 'payment_status': 'Booking', 'created_by': by, 'created_at': ts(date), 'updated_at': ts(date)})
    M(mid)['status'] = 'booking'
    log(mid, 'booking', f"Booking oleh {T['customers'][cid - 1]['name']}", f'DP {rp(dp)}, harga disepakati {rp(price)}', date, by, 'booking')
    return sid


def sell(sid, price, pay, date, by):
    s = T['sales'][sid - 1]
    s.update(price=price, sale_date=date, payment_status=pay, updated_at=ts(date))
    M(s['motor_id'])['status'] = 'terjual'
    log(s['motor_id'], 'terjual', f"Terjual ke {T['customers'][s['customer_id'] - 1]['name']}", f'{rp(price)}, pembayaran {pay.lower()}', date, by, 'terjual')


# ---------------- inventaris (harga dari Lampiran B) ----------------
I = {}
for row in [('SP-001', 'Headlamp LED', 'pcs', 500000, 5, 2, 'Rak A1'), ('SP-002', 'Shockbreaker belakang', 'pcs', 400000, 2, 2, 'Rak A2'),
            ('SP-003', 'Velg racing 17 inci', 'set', 1300000, 2, 1, 'Rak B1'), ('SP-004', 'Oli mesin 1 liter', 'botol', 60000, 20, 10, 'Rak C1'),
            ('SP-005', 'Oli samping 1 liter', 'botol', 45000, 5, 5, 'Rak C1'), ('SP-006', 'Busi', 'pcs', 25000, 23, 10, 'Rak C2'),
            ('SP-007', 'Kampas rem depan', 'set', 55000, 7, 4, 'Rak C2'), ('SP-008', 'Ban luar 80/90-14', 'pcs', 210000, 6, 4, 'Rak D1'),
            ('SP-009', 'Spion standar', 'set', 85000, 3, 3, 'Rak A3'), ('SP-010', 'Aki kering 12V', 'pcs', 230000, 2, 2, 'Rak D2'),
            ('SP-011', 'Sabun cuci motor', 'botol', 25000, 9, 3, 'Rak E1'), ('SP-012', 'Wax poles motor', 'botol', 65000, 5, 2, 'Rak E1'),
            ('SP-013', 'Knalpot standar', 'pcs', 450000, 1, 1, 'Rak B2')]:
    I[row[0]] = item(*row)
stock_in(I['SP-010'], 2, 230000, 'CV Mitra Motor', '2026-10-10', K, 'Pembelian rutin')
C = {'rizky': customer('Rizky Pratama', '0812-8841-2290', 'Bekasi Timur'),
     'dewi': customer('Dewi Anggraini', '0857-1120-4418', 'Ciputat, Tangerang Selatan'),
     'bagus': customer('Bagus Santoso', '0813-9902-1187', 'Cimahi, Bandung')}

# ---------------- 7 motor, tiap tahap lifecycle terwakili ----------------
# NX-001 Satria: terjual lunas
m = motor(dict(brand='Suzuki', model='Satria F150', year=2016, plate='D 3301 XY', color='Hitam merah', frame='MH8BG41CAGJ402211', engine='G427ID402266', date='2026-08-20', price=10500000, source='Perorangan, Bandung', target=13000000, note='Mesin halus, ban belakang tipis'), A)
cost(m, 'Operasional', 'Travel pengambilan luar kota', 300000, '2026-08-20', K); cost(m, 'Operasional', 'Uang makan pengambilan', 70000, '2026-08-20', K)
status(m, 'inspeksi', '', F, '2026-08-21'); inspect(m, {'r5': 'rusak', 'r1': 'perlu', 'l3': 'perlu'}, 'Ban belakang harus diganti, rantai kendor.', F, '2026-08-21')
doc(m, 'STNK', 'Lengkap', '11223344', '2027-03-12', '2026-08-21'); doc(m, 'BPKB', 'Lengkap', 'N-0098812', '', '2026-08-21')
status(m, 'perbaikan', 'Ganti ban dan servis rantai', K, '2026-08-22')
stock_out(I['SP-008'], 1, m, '2026-08-22', F, 'Ban belakang'); stock_out(I['SP-004'], 1, m, '2026-08-22', F)
cost(m, 'Perbaikan', 'Sparepart', 320000, '2026-08-23', K, 'Rantai dan gir'); status(m, 'siap', '', K, '2026-09-01')
s = booking(m, C['bagus'], 13000000, 1500000, '2026-09-25', S); sell(s, 13000000, 'Lunas', '2026-09-28', S)
# NX-002 RX-King: booking
m = motor(dict(brand='Yamaha', model='RX-King', year=2002, plate='B 2287 SFA', color='Hitam', frame='MH33KA00X2K118823', engine='3KA-118823', date='2026-08-28', price=18500000, source='Komunitas RX-King, Tangerang', target=24000000, note='Original, perlu restorasi lampu dan knalpot'), A)
cost(m, 'Operasional', 'Jasa pengambilan kendaraan', 300000, '2026-08-28', K); cost(m, 'Operasional', 'Bensin pengambilan', 100000, '2026-08-28', K)
status(m, 'inspeksi', '', F, '2026-08-29'); inspect(m, {'l2': 'rusak', 'm2': 'perlu', 'r3': 'perlu', 'd4': 'perlu'}, 'Headlamp mati, rembes oli ringan, BPKB masih proses.', F, '2026-08-29')
doc(m, 'STNK', 'Lengkap', '55102291', '2026-12-02', '2026-08-29'); doc(m, 'BPKB', 'Dalam proses', '', '', '2026-08-29')
status(m, 'perbaikan', 'Restorasi lampu, knalpot, velg', K, '2026-08-30')
stock_out(I['SP-001'], 1, m, '2026-08-31', F); stock_out(I['SP-005'], 2, m, '2026-08-31', F)
cost(m, 'Perbaikan', 'Aksesori', 2500000, '2026-09-03', K, 'Knalpot dan velg'); cost(m, 'Surat-surat', 'STNK', 1000000, '2026-09-10', A, 'Perpanjangan dan ganti plat')
cost(m, 'Penyimpanan', 'Sewa gudang', 150000, '2026-09-30', A, 'Porsi bulan September'); status(m, 'siap', '', K, '2026-09-25')
booking(m, C['dewi'], 24000000, 2000000, '2026-10-14', S)
# NX-003 Beat: terjual lunas
m = motor(dict(brand='Honda', model='Beat Street', year=2019, plate='B 3412 KLM', color='Hitam doff', frame='MH1JM8118KK441902', engine='JM81E1441960', date='2026-09-03', price=9500000, source='Perorangan, Bekasi', target=13800000, note='Spion kiri hilang'), A)
cost(m, 'Operasional', 'Jasa pengambilan kendaraan', 300000, '2026-09-03', K); cost(m, 'Operasional', 'Uang makan pengambilan', 70000, '2026-09-03', K); cost(m, 'Operasional', 'Bensin pengambilan', 100000, '2026-09-03', K)
status(m, 'inspeksi', '', F, '2026-09-04'); inspect(m, {'r4': 'rusak', 'm2': 'perlu'}, 'Spion kiri tidak ada, ada rembesan di gasket.', F, '2026-09-04')
doc(m, 'STNK', 'Lengkap', '88231190', '2027-01-18', '2026-09-04'); doc(m, 'BPKB', 'Lengkap', 'P-1187720', '', '2026-09-04'); doc(m, 'Faktur', 'Tidak ada', '', '', '2026-09-04')
status(m, 'perbaikan', '', K, '2026-09-05'); stock_out(I['SP-009'], 1, m, '2026-09-05', F); stock_out(I['SP-004'], 1, m, '2026-09-05', F)
cost(m, 'Perbaikan', 'Jasa servis bengkel', 150000, '2026-09-06', K, 'Ganti gasket'); cost(m, 'Surat-surat', 'Pajak tahunan', 380000, '2026-09-08', A)
status(m, 'siap', '', K, '2026-09-12'); s = booking(m, C['rizky'], 13800000, 1000000, '2026-10-02', S); sell(s, 13800000, 'Lunas', '2026-10-05', S)
cost(m, 'Operasional', 'Bahan bakar pengiriman Jabodetabek', 100000, '2026-10-05', K); cost(m, 'Operasional', 'Uang makan pengiriman', 70000, '2026-10-05', K)
# NX-004 NMAX: siap dijual
m = motor(dict(brand='Yamaha', model='NMAX 155', year=2020, plate='B 6120 TJR', color='Abu-abu', frame='MH3SG3190LJ220871', engine='G3E4E1220905', date='2026-09-10', price=21000000, source='Dealer motor bekas, Depok', target=25500000, note='Body baret di sisi kanan'), A)
cost(m, 'Operasional', 'Jasa pengambilan kendaraan', 300000, '2026-09-10', K); cost(m, 'Operasional', 'Bensin pengambilan', 100000, '2026-09-10', K)
status(m, 'inspeksi', '', F, '2026-09-11'); inspect(m, {'l2': 'perlu', 'r3': 'perlu'}, 'Lampu belakang redup, baret body kanan.', F, '2026-09-11')
doc(m, 'STNK', 'Lengkap', '70921183', '2027-06-30', '2026-09-11'); doc(m, 'BPKB', 'Lengkap', 'Q-2201991', '', '2026-09-11'); doc(m, 'Faktur', 'Lengkap', '', '', '2026-09-11')
status(m, 'perbaikan', '', K, '2026-09-12')
cost(m, 'Perbaikan', 'Cat dan perbaikan body', 650000, '2026-09-15', K, 'Cat ulang panel kanan'); cost(m, 'Perbaikan', 'Aksesori', 350000, '2026-09-16', K, 'Lampu belakang LED')
stock_out(I['SP-011'], 1, m, '2026-09-19', F); stock_out(I['SP-012'], 1, m, '2026-09-19', F); cost(m, 'Surat-surat', 'STNK', 1000000, '2026-09-19', A)
status(m, 'siap', '', K, '2026-09-20'); cost(m, 'Penyimpanan', 'Sewa gudang', 150000, '2026-10-01', A, 'Porsi bulan Oktober')
# NX-005 Vario: dalam perbaikan
m = motor(dict(brand='Honda', model='Vario 150', year=2018, plate='F 4471 AB', color='Putih', frame='MH1KF4114JK301877', engine='KF41E1301902', date='2026-09-25', price=11000000, source='Perorangan, Bogor', target=14500000, note='Tarikan berat, rem depan bunyi'), A)
cost(m, 'Operasional', 'Jasa pengambilan kendaraan', 300000, '2026-09-25', K); cost(m, 'Operasional', 'Uang makan pengambilan', 70000, '2026-09-25', K); cost(m, 'Operasional', 'Bensin pengambilan', 100000, '2026-09-25', K)
status(m, 'inspeksi', '', F, '2026-09-26'); inspect(m, {'m1': 'perlu', 'm4': 'rusak', 'r1': 'rusak', 'r2': 'perlu', 'l1': 'perlu', 'r3': 'perlu'}, 'CVT aus, kampas rem depan habis, aki lemah.', F, '2026-09-26')
doc(m, 'STNK', 'Lengkap', '30219987', '2026-11-20', '2026-09-26'); doc(m, 'BPKB', 'Lengkap', 'R-0081123', '', '2026-09-26')
status(m, 'perbaikan', 'Servis CVT, rem, dan kaki-kaki', K, '2026-09-27')
stock_out(I['SP-002'], 1, m, '2026-09-28', F); stock_out(I['SP-006'], 1, m, '2026-09-28', F); stock_out(I['SP-007'], 1, m, '2026-09-28', F)
cost(m, 'Perbaikan', 'Turun mesin', 850000, '2026-10-03', K, 'Ganti seal dan roller CVT')
# NX-006 Ninja: sedang diperiksa, belum ada hasil inspeksi
m = motor(dict(brand='Kawasaki', model='Ninja 250 FI', year=2017, plate='B 5530 PQR', color='Hijau', frame='MH4EX250HHT118274', engine='EX250LEA18301', date='2026-10-15', price=28000000, source='Perorangan, Surabaya', target=33000000, note='Dikirim via travel, belum dicek'), A)
cost(m, 'Operasional', 'Travel pengambilan luar kota', 300000, '2026-10-15', K); status(m, 'inspeksi', 'Menunggu mekanik', K, '2026-10-16')
# NX-007 Scoopy: baru masuk
m = motor(dict(brand='Honda', model='Scoopy', year=2021, plate='B 4019 UVW', color='Krem', frame='MH1JM3115MK770021', engine='JM31E1770051', date='2026-10-18', price=14200000, source='Perorangan, Jakarta Timur', target=17000000, note=''), A)
cost(m, 'Operasional', 'Bensin pengambilan', 100000, '2026-10-18', K)

# ---------------- tulis JSON untuk seeder ----------------
out = {'_info': 'Data dummy NEXA, dibuat oleh tools/generate_dummy.py. Password user ditulis polos dan di-hash saat seed (php index.php tools seed).',
       'order': TABLES, 'tables': T}
with open(os.path.join(ROOT, 'backend/application/database/dummy.json'), 'w', encoding='utf-8') as f:
    json.dump(out, f, ensure_ascii=False, indent=1)

# ---------------- tulis SQL dump + verifikasi ----------------
SCHEMA = open(os.path.join(ROOT, 'database', 'schema.sql'), encoding='utf-8').read()
con = sqlite3.connect(':memory:')
con.execute('PRAGMA foreign_keys=ON')
con.executescript(SCHEMA)


def q(v):
    if v is None:
        return 'NULL'
    if isinstance(v, (int, float)):
        return str(v)
    return "'" + str(v).replace("'", "''") + "'"


lines = ['-- NEXA: skema + data dummy (SQLite)',
         '-- Untuk melihat data tanpa menjalankan aplikasi: DB Browser for SQLite > File > Import > Database from SQL file.',
         '-- Aplikasi sendiri membuat database lewat: php index.php tools migrate && php index.php tools seed',
         'PRAGMA foreign_keys=OFF;', 'BEGIN TRANSACTION;', SCHEMA.strip()]
for t in TABLES:
    for r in T[t]:
        r = dict(r)
        if t == 'users':
            r['password_hash'] = crypt.crypt(r.pop('password'), crypt.mksalt(crypt.METHOD_BLOWFISH)).replace('$2b$', '$2y$', 1)
        stmt = f"INSERT INTO {t} ({','.join(r.keys())}) VALUES ({','.join(q(v) for v in r.values())});"
        con.execute(stmt)
        lines.append(stmt)
lines.append('COMMIT;')
con.commit()
assert con.execute('PRAGMA foreign_key_check').fetchall() == [], 'Foreign key tidak valid'
with open(os.path.join(ROOT, 'database', 'nexa_dump.sql'), 'w', encoding='utf-8') as f:
    f.write('\n'.join(lines) + '\n')

print('Jumlah baris:', {t: len(v) for t, v in T.items()})
print('Total modal per motor:')
for r in con.execute("""SELECT m.code, m.status, m.purchase_price
        + COALESCE((SELECT SUM(amount) FROM motor_costs c WHERE c.motor_id = m.id), 0)
        + COALESCE((SELECT SUM(total) FROM sparepart_usage u WHERE u.motor_id = m.id), 0)
        FROM motors m"""):
    print('  ', r)
