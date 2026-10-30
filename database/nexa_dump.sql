-- NEXA: skema + data dummy (SQLite)
-- Untuk melihat data tanpa menjalankan aplikasi: DB Browser for SQLite > File > Import > Database from SQL file.
-- Aplikasi sendiri membuat database lewat: php index.php tools migrate && php index.php tools seed
PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
-- =====================================================================
-- NEXA - Next-generation Asset Management
-- Skema database SQLite. Isinya sama dengan migration CodeIgniter 3:
--   backend/application/migrations/20261020000001_create_nexa_tables.php
-- Kalau mengubah struktur tabel, ubah migration DAN file ini.
-- =====================================================================

-- Role / hak akses: admin, owner, ops, sales
CREATE TABLE roles (
  id   INTEGER PRIMARY KEY AUTOINCREMENT,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(50) NOT NULL
);

-- Akun pengguna
CREATE TABLE users (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  role_id       INTEGER NOT NULL REFERENCES roles(id),
  name          VARCHAR(100) NOT NULL,
  username      VARCHAR(50) NOT NULL UNIQUE,
  email         VARCHAR(100),
  password_hash VARCHAR(255) NOT NULL,
  is_active     INTEGER NOT NULL DEFAULT 1,
  created_at    DATETIME,
  updated_at    DATETIME
);

-- Token login (Bearer) untuk frontend. Yang disimpan hanya hash SHA-256.
CREATE TABLE api_tokens (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  token_hash VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at DATETIME
);

-- Kategori biaya: Operasional, Perbaikan, Surat-surat, Penyimpanan, Lainnya
CREATE TABLE cost_categories (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  name        VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(200)
);

-- Master aset motor, pusat lifecycle kendaraan.
-- status: masuk | inspeksi | perbaikan | siap | booking | terjual
CREATE TABLE motors (
  id              INTEGER PRIMARY KEY AUTOINCREMENT,
  code            VARCHAR(20) NOT NULL UNIQUE,
  brand           VARCHAR(50) NOT NULL,
  model           VARCHAR(80) NOT NULL,
  year            INTEGER,
  plate_number    VARCHAR(20),
  color           VARCHAR(40),
  frame_number    VARCHAR(40),
  engine_number   VARCHAR(40),
  purchase_date   DATE,
  purchase_price  BIGINT NOT NULL DEFAULT 0,
  purchase_source VARCHAR(120),
  target_price    BIGINT NOT NULL DEFAULT 0,
  condition_note  TEXT,
  status          VARCHAR(20) NOT NULL DEFAULT 'masuk',
  health_score    INTEGER,
  created_at      DATETIME,
  updated_at      DATETIME
);

-- Header inspeksi + nilai Asset Health Score (0-100)
CREATE TABLE inspections (
  id              INTEGER PRIMARY KEY AUTOINCREMENT,
  motor_id        INTEGER NOT NULL REFERENCES motors(id) ON DELETE CASCADE,
  inspection_date DATE NOT NULL,
  inspector       VARCHAR(100),
  notes           TEXT,
  score           INTEGER NOT NULL,
  created_by      INTEGER REFERENCES users(id),
  created_at      DATETIME
);

-- Detail checklist. result: baik | perlu | rusak
CREATE TABLE inspection_items (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  inspection_id INTEGER NOT NULL REFERENCES inspections(id) ON DELETE CASCADE,
  group_key     VARCHAR(20) NOT NULL,
  item_key      VARCHAR(10) NOT NULL,
  item_label    VARCHAR(80) NOT NULL,
  result        VARCHAR(10) NOT NULL
);

-- Biaya per motor (masuk ke total modal)
CREATE TABLE motor_costs (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  motor_id    INTEGER NOT NULL REFERENCES motors(id) ON DELETE CASCADE,
  category_id INTEGER NOT NULL REFERENCES cost_categories(id),
  item        VARCHAR(120) NOT NULL,
  amount      BIGINT NOT NULL,
  cost_date   DATE NOT NULL,
  note        VARCHAR(255),
  created_by  INTEGER REFERENCES users(id),
  created_at  DATETIME
);

-- Master sparepart / barang gudang
CREATE TABLE inventory_items (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  code       VARCHAR(20) NOT NULL UNIQUE,
  name       VARCHAR(100) NOT NULL,
  unit       VARCHAR(20) NOT NULL,
  unit_price BIGINT NOT NULL DEFAULT 0,
  stock      INTEGER NOT NULL DEFAULT 0,
  min_stock  INTEGER NOT NULL DEFAULT 0,
  location   VARCHAR(50),
  created_at DATETIME,
  updated_at DATETIME
);

-- Kartu stok. type: in | out
CREATE TABLE inventory_transactions (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  item_id    INTEGER NOT NULL REFERENCES inventory_items(id),
  type       VARCHAR(5) NOT NULL,
  qty        INTEGER NOT NULL,
  unit_price BIGINT NOT NULL,
  trx_date   DATE NOT NULL,
  supplier   VARCHAR(100),
  motor_id   INTEGER REFERENCES motors(id),
  note       VARCHAR(255),
  created_by INTEGER REFERENCES users(id),
  created_at DATETIME
);

-- Pemakaian sparepart untuk motor tertentu (nilainya masuk modal motor)
CREATE TABLE sparepart_usage (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  motor_id       INTEGER NOT NULL REFERENCES motors(id) ON DELETE CASCADE,
  item_id        INTEGER NOT NULL REFERENCES inventory_items(id),
  transaction_id INTEGER REFERENCES inventory_transactions(id),
  qty            INTEGER NOT NULL,
  unit_price     BIGINT NOT NULL,
  total          BIGINT NOT NULL,
  usage_date     DATE NOT NULL,
  created_at     DATETIME
);

-- Dokumen kendaraan. status: Lengkap | Dalam proses | Tidak ada
CREATE TABLE vehicle_documents (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  motor_id    INTEGER NOT NULL REFERENCES motors(id) ON DELETE CASCADE,
  doc_type    VARCHAR(40) NOT NULL,
  doc_number  VARCHAR(60),
  status      VARCHAR(20) NOT NULL,
  expiry_date DATE,
  note        VARCHAR(255),
  created_at  DATETIME,
  updated_at  DATETIME
);

-- Customer / pembeli
CREATE TABLE customers (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  name       VARCHAR(100) NOT NULL,
  phone      VARCHAR(30),
  address    VARCHAR(200),
  created_at DATETIME
);

-- Booking & penjualan. payment_status: Booking | Lunas | Belum lunas | Batal
CREATE TABLE sales (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  motor_id       INTEGER NOT NULL REFERENCES motors(id),
  customer_id    INTEGER NOT NULL REFERENCES customers(id),
  booking_date   DATE,
  sale_date      DATE,
  price          BIGINT NOT NULL,
  dp             BIGINT NOT NULL DEFAULT 0,
  payment_status VARCHAR(20) NOT NULL,
  created_by     INTEGER REFERENCES users(id),
  created_at     DATETIME,
  updated_at     DATETIME
);

-- Event lifecycle untuk Asset Timeline.
-- type: masuk | status | inspeksi | biaya | sparepart | dokumen | booking | terjual | batal
-- status: diisi kalau event ini mengubah status motor
CREATE TABLE asset_activities (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  motor_id      INTEGER NOT NULL REFERENCES motors(id) ON DELETE CASCADE,
  activity_date DATE NOT NULL,
  type          VARCHAR(20) NOT NULL,
  status        VARCHAR(20),
  title         VARCHAR(200) NOT NULL,
  detail        TEXT,
  created_by    INTEGER REFERENCES users(id),
  created_at    DATETIME
);
INSERT INTO roles (id,code,name) VALUES (1,'admin','Administrator');
INSERT INTO roles (id,code,name) VALUES (2,'owner','Pemilik Usaha');
INSERT INTO roles (id,code,name) VALUES (3,'ops','Staff Operasional');
INSERT INTO roles (id,code,name) VALUES (4,'sales','Staff Penjualan');
INSERT INTO users (id,role_id,name,username,email,is_active,created_at,updated_at,password_hash) VALUES (1,1,'Alifya Putri Aisyka','admin','admin@nexa.local',1,'2026-08-01 08:00:00','2026-08-01 08:00:00','$2y$12$XZyK2Hp8kgwIDJxNQOCZXeDADaMPhKgWtqgY50JgRAyU1PWdUQi0a');
INSERT INTO users (id,role_id,name,username,email,is_active,created_at,updated_at,password_hash) VALUES (2,2,'Akmal Lutfan Hasibuan','pemilik','pemilik@nexa.local',1,'2026-08-01 08:00:00','2026-08-01 08:00:00','$2y$12$3AEdYn3iFgrkK4D87k0QfOdO7aZr3hxlD.tMO2vkVfj.dz7CJhlDS');
INSERT INTO users (id,role_id,name,username,email,is_active,created_at,updated_at,password_hash) VALUES (3,3,'Muhammad Kemal Pasha','operasional','ops@nexa.local',1,'2026-08-01 08:00:00','2026-08-01 08:00:00','$2y$12$ta9bpeTHKXzHYZPPSe/Cxe3VhuvHNLNm7gS3LItbQElW5yt3kii6q');
INSERT INTO users (id,role_id,name,username,email,is_active,created_at,updated_at,password_hash) VALUES (4,4,'Mulyo Saputra','penjualan','sales@nexa.local',1,'2026-08-01 08:00:00','2026-08-01 08:00:00','$2y$12$VxnIiMpgmspjgRBkh8.4wOofDvIYjyrJTm8gbVZbLcVvg89WdV60e');
INSERT INTO users (id,role_id,name,username,email,is_active,created_at,updated_at,password_hash) VALUES (5,3,'Firmansyah','mekanik','mekanik@nexa.local',1,'2026-08-01 08:00:00','2026-08-01 08:00:00','$2y$12$8ZRlzA9hOwxRTjwrDFR7Y.ntEpKpo/Zs/c/vKIyw0mF5EDuq5p2Fy');
INSERT INTO cost_categories (id,name,description) VALUES (1,'Operasional','Pengambilan, pengiriman, bahan bakar, transportasi');
INSERT INTO cost_categories (id,name,description) VALUES (2,'Perbaikan','Aksesori, sparepart dari luar gudang, bahan pendukung, jasa bengkel');
INSERT INTO cost_categories (id,name,description) VALUES (3,'Surat-surat','Pajak, STNK, BPKB, balik nama');
INSERT INTO cost_categories (id,name,description) VALUES (4,'Penyimpanan','Sewa gudang atau tempat penyimpanan');
INSERT INTO cost_categories (id,name,description) VALUES (5,'Lainnya','Biaya lain yang terkait motor');
INSERT INTO motors (id,code,brand,model,year,plate_number,color,frame_number,engine_number,purchase_date,purchase_price,purchase_source,target_price,condition_note,status,health_score,created_at,updated_at) VALUES (1,'NX-001','Suzuki','Satria F150',2016,'D 3301 XY','Hitam merah','MH8BG41CAGJ402211','G427ID402266','2026-08-20',10500000,'Perorangan, Bandung',13000000,'Mesin halus, ban belakang tipis','terjual',89,'2026-08-20 08:15:00','2026-09-01 08:42:00');
INSERT INTO motors (id,code,brand,model,year,plate_number,color,frame_number,engine_number,purchase_date,purchase_price,purchase_source,target_price,condition_note,status,health_score,created_at,updated_at) VALUES (2,'NX-002','Yamaha','RX-King',2002,'B 2287 SFA','Hitam','MH33KA00X2K118823','3KA-118823','2026-08-28',18500000,'Komunitas RX-King, Tangerang',24000000,'Original, perlu restorasi lampu dan knalpot','booking',87,'2026-08-28 08:49:00','2026-09-25 09:20:00');
INSERT INTO motors (id,code,brand,model,year,plate_number,color,frame_number,engine_number,purchase_date,purchase_price,purchase_source,target_price,condition_note,status,health_score,created_at,updated_at) VALUES (3,'NX-003','Honda','Beat Street',2019,'B 3412 KLM','Hitam doff','MH1JM8118KK441902','JM81E1441960','2026-09-03',9500000,'Perorangan, Bekasi',13800000,'Spion kiri hilang','terjual',90,'2026-09-03 09:25:00','2026-09-12 09:59:00');
INSERT INTO motors (id,code,brand,model,year,plate_number,color,frame_number,engine_number,purchase_date,purchase_price,purchase_source,target_price,condition_note,status,health_score,created_at,updated_at) VALUES (4,'NX-004','Yamaha','NMAX 155',2020,'B 6120 TJR','Abu-abu','MH3SG3190LJ220871','G3E4E1220905','2026-09-10',21000000,'Dealer motor bekas, Depok',25500000,'Body baret di sisi kanan','siap',95,'2026-09-10 10:10:00','2026-09-20 10:44:00');
INSERT INTO motors (id,code,brand,model,year,plate_number,color,frame_number,engine_number,purchase_date,purchase_price,purchase_source,target_price,condition_note,status,health_score,created_at,updated_at) VALUES (5,'NX-005','Honda','Vario 150',2018,'F 4471 AB','Putih','MH1KF4114JK301877','KF41E1301902','2026-09-25',11000000,'Perorangan, Bogor',14500000,'Tarikan berat, rem depan bunyi','perbaikan',73,'2026-09-25 10:48:00','2026-09-27 11:07:00');
INSERT INTO motors (id,code,brand,model,year,plate_number,color,frame_number,engine_number,purchase_date,purchase_price,purchase_source,target_price,condition_note,status,health_score,created_at,updated_at) VALUES (6,'NX-006','Kawasaki','Ninja 250 FI',2017,'B 5530 PQR','Hijau','MH4EX250HHT118274','EX250LEA18301','2026-10-15',28000000,'Perorangan, Surabaya',33000000,'Dikirim via travel, belum dicek','inspeksi',NULL,'2026-10-15 11:20:00','2026-10-16 11:25:00');
INSERT INTO motors (id,code,brand,model,year,plate_number,color,frame_number,engine_number,purchase_date,purchase_price,purchase_source,target_price,condition_note,status,health_score,created_at,updated_at) VALUES (7,'NX-007','Honda','Scoopy',2021,'B 4019 UVW','Krem','MH1JM3115MK770021','JM31E1770051','2026-10-18',14200000,'Perorangan, Jakarta Timur',17000000,'','masuk',NULL,'2026-10-18 11:27:00','2026-10-18 11:28:00');
INSERT INTO inspections (id,motor_id,inspection_date,inspector,notes,score,created_by,created_at) VALUES (1,1,'2026-08-21','Firmansyah','Ban belakang harus diganti, rantai kendor.',89,5,'2026-08-21 08:24:00');
INSERT INTO inspections (id,motor_id,inspection_date,inspector,notes,score,created_by,created_at) VALUES (2,2,'2026-08-29','Firmansyah','Headlamp mati, rembes oli ringan, BPKB masih proses.',87,5,'2026-08-29 08:58:00');
INSERT INTO inspections (id,motor_id,inspection_date,inspector,notes,score,created_by,created_at) VALUES (3,3,'2026-09-04','Firmansyah','Spion kiri tidak ada, ada rembesan di gasket.',90,5,'2026-09-04 09:36:00');
INSERT INTO inspections (id,motor_id,inspection_date,inspector,notes,score,created_by,created_at) VALUES (4,4,'2026-09-11','Firmansyah','Lampu belakang redup, baret body kanan.',95,5,'2026-09-11 10:19:00');
INSERT INTO inspections (id,motor_id,inspection_date,inspector,notes,score,created_by,created_at) VALUES (5,5,'2026-09-26','Firmansyah','CVT aus, kampas rem depan habis, aki lemah.',73,5,'2026-09-26 10:59:00');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (1,1,'mesin','m1','Suara mesin','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (2,1,'mesin','m2','Kebocoran oli','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (3,1,'mesin','m3','Sistem pengapian','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (4,1,'mesin','m4','Performa tarikan','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (5,1,'listrik','l1','Aki','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (6,1,'listrik','l2','Lampu depan/belakang','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (7,1,'listrik','l3','Lampu sein','perlu');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (8,1,'listrik','l4','Klakson','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (9,1,'listrik','l5','Starter','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (10,1,'rem','r1','Ketebalan kampas rem','perlu');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (11,1,'rem','r2','Fungsi rem depan/belakang','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (12,1,'rem','r3','Kondisi fisik body','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (13,1,'rem','r4','Ketersediaan spion','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (14,1,'rem','r5','Keausan ban','rusak');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (15,1,'dok','d1','Nomor rangka sesuai','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (16,1,'dok','d2','Nomor mesin sesuai','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (17,1,'dok','d3','STNK','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (18,1,'dok','d4','BPKB','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (19,2,'mesin','m1','Suara mesin','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (20,2,'mesin','m2','Kebocoran oli','perlu');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (21,2,'mesin','m3','Sistem pengapian','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (22,2,'mesin','m4','Performa tarikan','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (23,2,'listrik','l1','Aki','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (24,2,'listrik','l2','Lampu depan/belakang','rusak');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (25,2,'listrik','l3','Lampu sein','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (26,2,'listrik','l4','Klakson','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (27,2,'listrik','l5','Starter','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (28,2,'rem','r1','Ketebalan kampas rem','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (29,2,'rem','r2','Fungsi rem depan/belakang','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (30,2,'rem','r3','Kondisi fisik body','perlu');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (31,2,'rem','r4','Ketersediaan spion','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (32,2,'rem','r5','Keausan ban','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (33,2,'dok','d1','Nomor rangka sesuai','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (34,2,'dok','d2','Nomor mesin sesuai','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (35,2,'dok','d3','STNK','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (36,2,'dok','d4','BPKB','perlu');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (37,3,'mesin','m1','Suara mesin','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (38,3,'mesin','m2','Kebocoran oli','perlu');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (39,3,'mesin','m3','Sistem pengapian','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (40,3,'mesin','m4','Performa tarikan','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (41,3,'listrik','l1','Aki','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (42,3,'listrik','l2','Lampu depan/belakang','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (43,3,'listrik','l3','Lampu sein','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (44,3,'listrik','l4','Klakson','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (45,3,'listrik','l5','Starter','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (46,3,'rem','r1','Ketebalan kampas rem','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (47,3,'rem','r2','Fungsi rem depan/belakang','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (48,3,'rem','r3','Kondisi fisik body','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (49,3,'rem','r4','Ketersediaan spion','rusak');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (50,3,'rem','r5','Keausan ban','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (51,3,'dok','d1','Nomor rangka sesuai','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (52,3,'dok','d2','Nomor mesin sesuai','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (53,3,'dok','d3','STNK','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (54,3,'dok','d4','BPKB','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (55,4,'mesin','m1','Suara mesin','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (56,4,'mesin','m2','Kebocoran oli','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (57,4,'mesin','m3','Sistem pengapian','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (58,4,'mesin','m4','Performa tarikan','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (59,4,'listrik','l1','Aki','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (60,4,'listrik','l2','Lampu depan/belakang','perlu');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (61,4,'listrik','l3','Lampu sein','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (62,4,'listrik','l4','Klakson','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (63,4,'listrik','l5','Starter','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (64,4,'rem','r1','Ketebalan kampas rem','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (65,4,'rem','r2','Fungsi rem depan/belakang','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (66,4,'rem','r3','Kondisi fisik body','perlu');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (67,4,'rem','r4','Ketersediaan spion','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (68,4,'rem','r5','Keausan ban','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (69,4,'dok','d1','Nomor rangka sesuai','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (70,4,'dok','d2','Nomor mesin sesuai','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (71,4,'dok','d3','STNK','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (72,4,'dok','d4','BPKB','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (73,5,'mesin','m1','Suara mesin','perlu');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (74,5,'mesin','m2','Kebocoran oli','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (75,5,'mesin','m3','Sistem pengapian','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (76,5,'mesin','m4','Performa tarikan','rusak');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (77,5,'listrik','l1','Aki','perlu');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (78,5,'listrik','l2','Lampu depan/belakang','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (79,5,'listrik','l3','Lampu sein','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (80,5,'listrik','l4','Klakson','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (81,5,'listrik','l5','Starter','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (82,5,'rem','r1','Ketebalan kampas rem','rusak');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (83,5,'rem','r2','Fungsi rem depan/belakang','perlu');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (84,5,'rem','r3','Kondisi fisik body','perlu');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (85,5,'rem','r4','Ketersediaan spion','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (86,5,'rem','r5','Keausan ban','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (87,5,'dok','d1','Nomor rangka sesuai','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (88,5,'dok','d2','Nomor mesin sesuai','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (89,5,'dok','d3','STNK','baik');
INSERT INTO inspection_items (id,inspection_id,group_key,item_key,item_label,result) VALUES (90,5,'dok','d4','BPKB','baik');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (1,1,1,'Travel pengambilan luar kota',300000,'2026-08-20','',3,'2026-08-20 08:18:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (2,1,1,'Uang makan pengambilan',70000,'2026-08-20','',3,'2026-08-20 08:20:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (3,1,2,'Sparepart',320000,'2026-08-23','Rantai dan gir',3,'2026-08-23 08:40:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (4,2,1,'Jasa pengambilan kendaraan',300000,'2026-08-28','',3,'2026-08-28 08:52:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (5,2,1,'Bensin pengambilan',100000,'2026-08-28','',3,'2026-08-28 08:54:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (6,2,2,'Aksesori',2500000,'2026-09-03','Knalpot dan velg',3,'2026-09-03 09:14:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (7,2,3,'STNK',1000000,'2026-09-10','Perpanjangan dan ganti plat',1,'2026-09-10 09:16:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (8,2,4,'Sewa gudang',150000,'2026-09-30','Porsi bulan September',1,'2026-09-30 09:18:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (9,3,1,'Jasa pengambilan kendaraan',300000,'2026-09-03','',3,'2026-09-03 09:28:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (10,3,1,'Uang makan pengambilan',70000,'2026-09-03','',3,'2026-09-03 09:30:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (11,3,1,'Bensin pengambilan',100000,'2026-09-03','',3,'2026-09-03 09:32:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (12,3,2,'Jasa servis bengkel',150000,'2026-09-06','Ganti gasket',3,'2026-09-06 09:55:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (13,3,3,'Pajak tahunan',380000,'2026-09-08','',1,'2026-09-08 09:57:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (14,3,1,'Bahan bakar pengiriman Jabodetabek',100000,'2026-10-05','',3,'2026-10-05 10:06:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (15,3,1,'Uang makan pengiriman',70000,'2026-10-05','',3,'2026-10-05 10:08:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (16,4,1,'Jasa pengambilan kendaraan',300000,'2026-09-10','',3,'2026-09-10 10:13:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (17,4,1,'Bensin pengambilan',100000,'2026-09-10','',3,'2026-09-10 10:15:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (18,4,2,'Cat dan perbaikan body',650000,'2026-09-15','Cat ulang panel kanan',3,'2026-09-15 10:32:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (19,4,2,'Aksesori',350000,'2026-09-16','Lampu belakang LED',3,'2026-09-16 10:34:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (20,4,3,'STNK',1000000,'2026-09-19','',1,'2026-09-19 10:42:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (21,4,4,'Sewa gudang',150000,'2026-10-01','Porsi bulan Oktober',1,'2026-10-01 10:46:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (22,5,1,'Jasa pengambilan kendaraan',300000,'2026-09-25','',3,'2026-09-25 10:51:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (23,5,1,'Uang makan pengambilan',70000,'2026-09-25','',3,'2026-09-25 10:53:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (24,5,1,'Bensin pengambilan',100000,'2026-09-25','',3,'2026-09-25 10:55:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (25,5,2,'Turun mesin',850000,'2026-10-03','Ganti seal dan roller CVT',3,'2026-10-03 11:18:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (26,6,1,'Travel pengambilan luar kota',300000,'2026-10-15','',3,'2026-10-15 11:23:00');
INSERT INTO motor_costs (id,motor_id,category_id,item,amount,cost_date,note,created_by,created_at) VALUES (27,7,1,'Bensin pengambilan',100000,'2026-10-18','',3,'2026-10-18 11:30:00');
INSERT INTO inventory_items (id,code,name,unit,unit_price,stock,min_stock,location,created_at,updated_at) VALUES (1,'SP-001','Headlamp LED','pcs',500000,4,2,'Rak A1','2026-08-15 08:00:00','2026-08-15 08:00:00');
INSERT INTO inventory_items (id,code,name,unit,unit_price,stock,min_stock,location,created_at,updated_at) VALUES (2,'SP-002','Shockbreaker belakang','pcs',400000,1,2,'Rak A2','2026-08-15 08:00:00','2026-08-15 08:00:00');
INSERT INTO inventory_items (id,code,name,unit,unit_price,stock,min_stock,location,created_at,updated_at) VALUES (3,'SP-003','Velg racing 17 inci','set',1300000,2,1,'Rak B1','2026-08-15 08:00:00','2026-08-15 08:00:00');
INSERT INTO inventory_items (id,code,name,unit,unit_price,stock,min_stock,location,created_at,updated_at) VALUES (4,'SP-004','Oli mesin 1 liter','botol',60000,18,10,'Rak C1','2026-08-15 08:00:00','2026-08-15 08:00:00');
INSERT INTO inventory_items (id,code,name,unit,unit_price,stock,min_stock,location,created_at,updated_at) VALUES (5,'SP-005','Oli samping 1 liter','botol',45000,3,5,'Rak C1','2026-08-15 08:00:00','2026-08-15 08:00:00');
INSERT INTO inventory_items (id,code,name,unit,unit_price,stock,min_stock,location,created_at,updated_at) VALUES (6,'SP-006','Busi','pcs',25000,22,10,'Rak C2','2026-08-15 08:00:00','2026-08-15 08:00:00');
INSERT INTO inventory_items (id,code,name,unit,unit_price,stock,min_stock,location,created_at,updated_at) VALUES (7,'SP-007','Kampas rem depan','set',55000,6,4,'Rak C2','2026-08-15 08:00:00','2026-08-15 08:00:00');
INSERT INTO inventory_items (id,code,name,unit,unit_price,stock,min_stock,location,created_at,updated_at) VALUES (8,'SP-008','Ban luar 80/90-14','pcs',210000,5,4,'Rak D1','2026-08-15 08:00:00','2026-08-15 08:00:00');
INSERT INTO inventory_items (id,code,name,unit,unit_price,stock,min_stock,location,created_at,updated_at) VALUES (9,'SP-009','Spion standar','set',85000,2,3,'Rak A3','2026-08-15 08:00:00','2026-08-15 08:00:00');
INSERT INTO inventory_items (id,code,name,unit,unit_price,stock,min_stock,location,created_at,updated_at) VALUES (10,'SP-010','Aki kering 12V','pcs',230000,4,2,'Rak D2','2026-08-15 08:00:00','2026-08-15 08:00:00');
INSERT INTO inventory_items (id,code,name,unit,unit_price,stock,min_stock,location,created_at,updated_at) VALUES (11,'SP-011','Sabun cuci motor','botol',25000,8,3,'Rak E1','2026-08-15 08:00:00','2026-08-15 08:00:00');
INSERT INTO inventory_items (id,code,name,unit,unit_price,stock,min_stock,location,created_at,updated_at) VALUES (12,'SP-012','Wax poles motor','botol',65000,4,2,'Rak E1','2026-08-15 08:00:00','2026-08-15 08:00:00');
INSERT INTO inventory_items (id,code,name,unit,unit_price,stock,min_stock,location,created_at,updated_at) VALUES (13,'SP-013','Knalpot standar','pcs',450000,1,1,'Rak B2','2026-08-15 08:00:00','2026-08-15 08:00:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (1,1,'in',5,500000,'2026-08-15','Stok awal',NULL,'',3,'2026-08-15 08:01:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (2,2,'in',2,400000,'2026-08-15','Stok awal',NULL,'',3,'2026-08-15 08:02:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (3,3,'in',2,1300000,'2026-08-15','Stok awal',NULL,'',3,'2026-08-15 08:03:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (4,4,'in',20,60000,'2026-08-15','Stok awal',NULL,'',3,'2026-08-15 08:04:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (5,5,'in',5,45000,'2026-08-15','Stok awal',NULL,'',3,'2026-08-15 08:05:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (6,6,'in',23,25000,'2026-08-15','Stok awal',NULL,'',3,'2026-08-15 08:06:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (7,7,'in',7,55000,'2026-08-15','Stok awal',NULL,'',3,'2026-08-15 08:07:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (8,8,'in',6,210000,'2026-08-15','Stok awal',NULL,'',3,'2026-08-15 08:08:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (9,9,'in',3,85000,'2026-08-15','Stok awal',NULL,'',3,'2026-08-15 08:09:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (10,10,'in',2,230000,'2026-08-15','Stok awal',NULL,'',3,'2026-08-15 08:10:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (11,11,'in',9,25000,'2026-08-15','Stok awal',NULL,'',3,'2026-08-15 08:11:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (12,12,'in',5,65000,'2026-08-15','Stok awal',NULL,'',3,'2026-08-15 08:12:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (13,13,'in',1,450000,'2026-08-15','Stok awal',NULL,'',3,'2026-08-15 08:13:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (14,10,'in',2,230000,'2026-10-10','CV Mitra Motor',NULL,'Pembelian rutin',3,'2026-10-10 08:14:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (15,8,'out',1,210000,'2026-08-22',NULL,1,'Ban belakang',5,'2026-08-22 08:34:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (16,4,'out',1,60000,'2026-08-22',NULL,1,'',5,'2026-08-22 08:37:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (17,1,'out',1,500000,'2026-08-31',NULL,2,'',5,'2026-08-31 09:08:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (18,5,'out',2,45000,'2026-08-31',NULL,2,'',5,'2026-08-31 09:11:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (19,9,'out',1,85000,'2026-09-05',NULL,3,'',5,'2026-09-05 09:49:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (20,4,'out',1,60000,'2026-09-05',NULL,3,'',5,'2026-09-05 09:52:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (21,11,'out',1,25000,'2026-09-19',NULL,4,'',5,'2026-09-19 10:36:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (22,12,'out',1,65000,'2026-09-19',NULL,4,'',5,'2026-09-19 10:39:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (23,2,'out',1,400000,'2026-09-28',NULL,5,'',5,'2026-09-28 11:09:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (24,6,'out',1,25000,'2026-09-28',NULL,5,'',5,'2026-09-28 11:12:00');
INSERT INTO inventory_transactions (id,item_id,type,qty,unit_price,trx_date,supplier,motor_id,note,created_by,created_at) VALUES (25,7,'out',1,55000,'2026-09-28',NULL,5,'',5,'2026-09-28 11:15:00');
INSERT INTO sparepart_usage (id,motor_id,item_id,transaction_id,qty,unit_price,total,usage_date,created_at) VALUES (1,1,8,15,1,210000,210000,'2026-08-22','2026-08-22 08:35:00');
INSERT INTO sparepart_usage (id,motor_id,item_id,transaction_id,qty,unit_price,total,usage_date,created_at) VALUES (2,1,4,16,1,60000,60000,'2026-08-22','2026-08-22 08:38:00');
INSERT INTO sparepart_usage (id,motor_id,item_id,transaction_id,qty,unit_price,total,usage_date,created_at) VALUES (3,2,1,17,1,500000,500000,'2026-08-31','2026-08-31 09:09:00');
INSERT INTO sparepart_usage (id,motor_id,item_id,transaction_id,qty,unit_price,total,usage_date,created_at) VALUES (4,2,5,18,2,45000,90000,'2026-08-31','2026-08-31 09:12:00');
INSERT INTO sparepart_usage (id,motor_id,item_id,transaction_id,qty,unit_price,total,usage_date,created_at) VALUES (5,3,9,19,1,85000,85000,'2026-09-05','2026-09-05 09:50:00');
INSERT INTO sparepart_usage (id,motor_id,item_id,transaction_id,qty,unit_price,total,usage_date,created_at) VALUES (6,3,4,20,1,60000,60000,'2026-09-05','2026-09-05 09:53:00');
INSERT INTO sparepart_usage (id,motor_id,item_id,transaction_id,qty,unit_price,total,usage_date,created_at) VALUES (7,4,11,21,1,25000,25000,'2026-09-19','2026-09-19 10:37:00');
INSERT INTO sparepart_usage (id,motor_id,item_id,transaction_id,qty,unit_price,total,usage_date,created_at) VALUES (8,4,12,22,1,65000,65000,'2026-09-19','2026-09-19 10:40:00');
INSERT INTO sparepart_usage (id,motor_id,item_id,transaction_id,qty,unit_price,total,usage_date,created_at) VALUES (9,5,2,23,1,400000,400000,'2026-09-28','2026-09-28 11:10:00');
INSERT INTO sparepart_usage (id,motor_id,item_id,transaction_id,qty,unit_price,total,usage_date,created_at) VALUES (10,5,6,24,1,25000,25000,'2026-09-28','2026-09-28 11:13:00');
INSERT INTO sparepart_usage (id,motor_id,item_id,transaction_id,qty,unit_price,total,usage_date,created_at) VALUES (11,5,7,25,1,55000,55000,'2026-09-28','2026-09-28 11:16:00');
INSERT INTO vehicle_documents (id,motor_id,doc_type,doc_number,status,expiry_date,note,created_at,updated_at) VALUES (1,1,'STNK','11223344','Lengkap','2027-03-12','','2026-08-21 08:26:00','2026-08-21 08:27:00');
INSERT INTO vehicle_documents (id,motor_id,doc_type,doc_number,status,expiry_date,note,created_at,updated_at) VALUES (2,1,'BPKB','N-0098812','Lengkap',NULL,'','2026-08-21 08:29:00','2026-08-21 08:30:00');
INSERT INTO vehicle_documents (id,motor_id,doc_type,doc_number,status,expiry_date,note,created_at,updated_at) VALUES (3,2,'STNK','55102291','Lengkap','2026-12-02','','2026-08-29 09:00:00','2026-08-29 09:01:00');
INSERT INTO vehicle_documents (id,motor_id,doc_type,doc_number,status,expiry_date,note,created_at,updated_at) VALUES (4,2,'BPKB','','Dalam proses',NULL,'','2026-08-29 09:03:00','2026-08-29 09:04:00');
INSERT INTO vehicle_documents (id,motor_id,doc_type,doc_number,status,expiry_date,note,created_at,updated_at) VALUES (5,3,'STNK','88231190','Lengkap','2027-01-18','','2026-09-04 09:38:00','2026-09-04 09:39:00');
INSERT INTO vehicle_documents (id,motor_id,doc_type,doc_number,status,expiry_date,note,created_at,updated_at) VALUES (6,3,'BPKB','P-1187720','Lengkap',NULL,'','2026-09-04 09:41:00','2026-09-04 09:42:00');
INSERT INTO vehicle_documents (id,motor_id,doc_type,doc_number,status,expiry_date,note,created_at,updated_at) VALUES (7,3,'Faktur','','Tidak ada',NULL,'','2026-09-04 09:44:00','2026-09-04 09:45:00');
INSERT INTO vehicle_documents (id,motor_id,doc_type,doc_number,status,expiry_date,note,created_at,updated_at) VALUES (8,4,'STNK','70921183','Lengkap','2027-06-30','','2026-09-11 10:21:00','2026-09-11 10:22:00');
INSERT INTO vehicle_documents (id,motor_id,doc_type,doc_number,status,expiry_date,note,created_at,updated_at) VALUES (9,4,'BPKB','Q-2201991','Lengkap',NULL,'','2026-09-11 10:24:00','2026-09-11 10:25:00');
INSERT INTO vehicle_documents (id,motor_id,doc_type,doc_number,status,expiry_date,note,created_at,updated_at) VALUES (10,4,'Faktur','','Lengkap',NULL,'','2026-09-11 10:27:00','2026-09-11 10:28:00');
INSERT INTO vehicle_documents (id,motor_id,doc_type,doc_number,status,expiry_date,note,created_at,updated_at) VALUES (11,5,'STNK','30219987','Lengkap','2026-11-20','','2026-09-26 11:01:00','2026-09-26 11:02:00');
INSERT INTO vehicle_documents (id,motor_id,doc_type,doc_number,status,expiry_date,note,created_at,updated_at) VALUES (12,5,'BPKB','R-0081123','Lengkap',NULL,'','2026-09-26 11:04:00','2026-09-26 11:05:00');
INSERT INTO customers (id,name,phone,address,created_at) VALUES (1,'Rizky Pratama','0812-8841-2290','Bekasi Timur','2026-09-01 08:00:00');
INSERT INTO customers (id,name,phone,address,created_at) VALUES (2,'Dewi Anggraini','0857-1120-4418','Ciputat, Tangerang Selatan','2026-09-01 08:00:00');
INSERT INTO customers (id,name,phone,address,created_at) VALUES (3,'Bagus Santoso','0813-9902-1187','Cimahi, Bandung','2026-09-01 08:00:00');
INSERT INTO sales (id,motor_id,customer_id,booking_date,sale_date,price,dp,payment_status,created_by,created_at,updated_at) VALUES (1,1,3,'2026-09-25','2026-09-28',13000000,1500000,'Lunas',4,'2026-09-25 08:44:00','2026-09-28 08:47:00');
INSERT INTO sales (id,motor_id,customer_id,booking_date,sale_date,price,dp,payment_status,created_by,created_at,updated_at) VALUES (2,2,2,'2026-10-14',NULL,24000000,2000000,'Booking',4,'2026-10-14 09:22:00','2026-10-14 09:23:00');
INSERT INTO sales (id,motor_id,customer_id,booking_date,sale_date,price,dp,payment_status,created_by,created_at,updated_at) VALUES (3,3,1,'2026-10-02','2026-10-05',13800000,1000000,'Lunas',4,'2026-10-02 10:01:00','2026-10-05 10:04:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (1,1,'2026-08-20','masuk','masuk','Motor masuk','Dibeli Rp 10.500.000 dari Perorangan, Bandung',1,'2026-08-20 08:17:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (2,1,'2026-08-20','biaya',NULL,'Biaya operasional: Travel pengambilan luar kota','Rp 300.000',3,'2026-08-20 08:19:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (3,1,'2026-08-20','biaya',NULL,'Biaya operasional: Uang makan pengambilan','Rp 70.000',3,'2026-08-20 08:21:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (4,1,'2026-08-21','status','inspeksi','Status: Sedang diperiksa','',5,'2026-08-21 08:23:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (5,1,'2026-08-21','inspeksi',NULL,'Inspeksi selesai, Health Score 89','Perlu perbaikan: Lampu sein, Ketebalan kampas rem. Rusak/tidak ada: Keausan ban',5,'2026-08-21 08:25:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (6,1,'2026-08-21','dokumen',NULL,'Dokumen dicatat: STNK','Lengkap, no. 11223344',3,'2026-08-21 08:28:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (7,1,'2026-08-21','dokumen',NULL,'Dokumen dicatat: BPKB','Lengkap, no. N-0098812',3,'2026-08-21 08:31:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (8,1,'2026-08-22','status','perbaikan','Status: Dalam perbaikan','Ganti ban dan servis rantai',3,'2026-08-22 08:33:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (9,1,'2026-08-22','sparepart',NULL,'Pakai Ban luar 80/90-14 x 1 pcs','Rp 210.000 diambil dari stok gudang',5,'2026-08-22 08:36:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (10,1,'2026-08-22','sparepart',NULL,'Pakai Oli mesin 1 liter x 1 botol','Rp 60.000 diambil dari stok gudang',5,'2026-08-22 08:39:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (11,1,'2026-08-23','biaya',NULL,'Biaya perbaikan: Sparepart','Rp 320.000, Rantai dan gir',3,'2026-08-23 08:41:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (12,1,'2026-09-01','status','siap','Status: Siap dijual','',3,'2026-09-01 08:43:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (13,1,'2026-09-25','booking','booking','Booking oleh Bagus Santoso','DP Rp 1.500.000, harga disepakati Rp 13.000.000',4,'2026-09-25 08:46:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (14,1,'2026-09-28','terjual','terjual','Terjual ke Bagus Santoso','Rp 13.000.000, pembayaran lunas',4,'2026-09-28 08:48:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (15,2,'2026-08-28','masuk','masuk','Motor masuk','Dibeli Rp 18.500.000 dari Komunitas RX-King, Tangerang',1,'2026-08-28 08:51:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (16,2,'2026-08-28','biaya',NULL,'Biaya operasional: Jasa pengambilan kendaraan','Rp 300.000',3,'2026-08-28 08:53:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (17,2,'2026-08-28','biaya',NULL,'Biaya operasional: Bensin pengambilan','Rp 100.000',3,'2026-08-28 08:55:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (18,2,'2026-08-29','status','inspeksi','Status: Sedang diperiksa','',5,'2026-08-29 08:57:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (19,2,'2026-08-29','inspeksi',NULL,'Inspeksi selesai, Health Score 87','Perlu perbaikan: Kebocoran oli, Kondisi fisik body, BPKB. Rusak/tidak ada: Lampu depan/belakang',5,'2026-08-29 08:59:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (20,2,'2026-08-29','dokumen',NULL,'Dokumen dicatat: STNK','Lengkap, no. 55102291',3,'2026-08-29 09:02:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (21,2,'2026-08-29','dokumen',NULL,'Dokumen dicatat: BPKB','Dalam proses',3,'2026-08-29 09:05:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (22,2,'2026-08-30','status','perbaikan','Status: Dalam perbaikan','Restorasi lampu, knalpot, velg',3,'2026-08-30 09:07:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (23,2,'2026-08-31','sparepart',NULL,'Pakai Headlamp LED x 1 pcs','Rp 500.000 diambil dari stok gudang',5,'2026-08-31 09:10:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (24,2,'2026-08-31','sparepart',NULL,'Pakai Oli samping 1 liter x 2 botol','Rp 90.000 diambil dari stok gudang',5,'2026-08-31 09:13:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (25,2,'2026-09-03','biaya',NULL,'Biaya perbaikan: Aksesori','Rp 2.500.000, Knalpot dan velg',3,'2026-09-03 09:15:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (26,2,'2026-09-10','biaya',NULL,'Biaya surat-surat: STNK','Rp 1.000.000, Perpanjangan dan ganti plat',1,'2026-09-10 09:17:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (27,2,'2026-09-30','biaya',NULL,'Biaya penyimpanan: Sewa gudang','Rp 150.000, Porsi bulan September',1,'2026-09-30 09:19:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (28,2,'2026-09-25','status','siap','Status: Siap dijual','',3,'2026-09-25 09:21:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (29,2,'2026-10-14','booking','booking','Booking oleh Dewi Anggraini','DP Rp 2.000.000, harga disepakati Rp 24.000.000',4,'2026-10-14 09:24:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (30,3,'2026-09-03','masuk','masuk','Motor masuk','Dibeli Rp 9.500.000 dari Perorangan, Bekasi',1,'2026-09-03 09:27:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (31,3,'2026-09-03','biaya',NULL,'Biaya operasional: Jasa pengambilan kendaraan','Rp 300.000',3,'2026-09-03 09:29:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (32,3,'2026-09-03','biaya',NULL,'Biaya operasional: Uang makan pengambilan','Rp 70.000',3,'2026-09-03 09:31:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (33,3,'2026-09-03','biaya',NULL,'Biaya operasional: Bensin pengambilan','Rp 100.000',3,'2026-09-03 09:33:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (34,3,'2026-09-04','status','inspeksi','Status: Sedang diperiksa','',5,'2026-09-04 09:35:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (35,3,'2026-09-04','inspeksi',NULL,'Inspeksi selesai, Health Score 90','Perlu perbaikan: Kebocoran oli. Rusak/tidak ada: Ketersediaan spion',5,'2026-09-04 09:37:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (36,3,'2026-09-04','dokumen',NULL,'Dokumen dicatat: STNK','Lengkap, no. 88231190',3,'2026-09-04 09:40:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (37,3,'2026-09-04','dokumen',NULL,'Dokumen dicatat: BPKB','Lengkap, no. P-1187720',3,'2026-09-04 09:43:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (38,3,'2026-09-04','dokumen',NULL,'Dokumen dicatat: Faktur','Tidak ada',3,'2026-09-04 09:46:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (39,3,'2026-09-05','status','perbaikan','Status: Dalam perbaikan','',3,'2026-09-05 09:48:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (40,3,'2026-09-05','sparepart',NULL,'Pakai Spion standar x 1 set','Rp 85.000 diambil dari stok gudang',5,'2026-09-05 09:51:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (41,3,'2026-09-05','sparepart',NULL,'Pakai Oli mesin 1 liter x 1 botol','Rp 60.000 diambil dari stok gudang',5,'2026-09-05 09:54:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (42,3,'2026-09-06','biaya',NULL,'Biaya perbaikan: Jasa servis bengkel','Rp 150.000, Ganti gasket',3,'2026-09-06 09:56:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (43,3,'2026-09-08','biaya',NULL,'Biaya surat-surat: Pajak tahunan','Rp 380.000',1,'2026-09-08 09:58:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (44,3,'2026-09-12','status','siap','Status: Siap dijual','',3,'2026-09-12 10:00:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (45,3,'2026-10-02','booking','booking','Booking oleh Rizky Pratama','DP Rp 1.000.000, harga disepakati Rp 13.800.000',4,'2026-10-02 10:03:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (46,3,'2026-10-05','terjual','terjual','Terjual ke Rizky Pratama','Rp 13.800.000, pembayaran lunas',4,'2026-10-05 10:05:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (47,3,'2026-10-05','biaya',NULL,'Biaya operasional: Bahan bakar pengiriman Jabodetabek','Rp 100.000',3,'2026-10-05 10:07:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (48,3,'2026-10-05','biaya',NULL,'Biaya operasional: Uang makan pengiriman','Rp 70.000',3,'2026-10-05 10:09:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (49,4,'2026-09-10','masuk','masuk','Motor masuk','Dibeli Rp 21.000.000 dari Dealer motor bekas, Depok',1,'2026-09-10 10:12:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (50,4,'2026-09-10','biaya',NULL,'Biaya operasional: Jasa pengambilan kendaraan','Rp 300.000',3,'2026-09-10 10:14:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (51,4,'2026-09-10','biaya',NULL,'Biaya operasional: Bensin pengambilan','Rp 100.000',3,'2026-09-10 10:16:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (52,4,'2026-09-11','status','inspeksi','Status: Sedang diperiksa','',5,'2026-09-11 10:18:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (53,4,'2026-09-11','inspeksi',NULL,'Inspeksi selesai, Health Score 95','Perlu perbaikan: Lampu depan/belakang, Kondisi fisik body',5,'2026-09-11 10:20:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (54,4,'2026-09-11','dokumen',NULL,'Dokumen dicatat: STNK','Lengkap, no. 70921183',3,'2026-09-11 10:23:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (55,4,'2026-09-11','dokumen',NULL,'Dokumen dicatat: BPKB','Lengkap, no. Q-2201991',3,'2026-09-11 10:26:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (56,4,'2026-09-11','dokumen',NULL,'Dokumen dicatat: Faktur','Lengkap',3,'2026-09-11 10:29:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (57,4,'2026-09-12','status','perbaikan','Status: Dalam perbaikan','',3,'2026-09-12 10:31:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (58,4,'2026-09-15','biaya',NULL,'Biaya perbaikan: Cat dan perbaikan body','Rp 650.000, Cat ulang panel kanan',3,'2026-09-15 10:33:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (59,4,'2026-09-16','biaya',NULL,'Biaya perbaikan: Aksesori','Rp 350.000, Lampu belakang LED',3,'2026-09-16 10:35:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (60,4,'2026-09-19','sparepart',NULL,'Pakai Sabun cuci motor x 1 botol','Rp 25.000 diambil dari stok gudang',5,'2026-09-19 10:38:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (61,4,'2026-09-19','sparepart',NULL,'Pakai Wax poles motor x 1 botol','Rp 65.000 diambil dari stok gudang',5,'2026-09-19 10:41:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (62,4,'2026-09-19','biaya',NULL,'Biaya surat-surat: STNK','Rp 1.000.000',1,'2026-09-19 10:43:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (63,4,'2026-09-20','status','siap','Status: Siap dijual','',3,'2026-09-20 10:45:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (64,4,'2026-10-01','biaya',NULL,'Biaya penyimpanan: Sewa gudang','Rp 150.000, Porsi bulan Oktober',1,'2026-10-01 10:47:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (65,5,'2026-09-25','masuk','masuk','Motor masuk','Dibeli Rp 11.000.000 dari Perorangan, Bogor',1,'2026-09-25 10:50:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (66,5,'2026-09-25','biaya',NULL,'Biaya operasional: Jasa pengambilan kendaraan','Rp 300.000',3,'2026-09-25 10:52:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (67,5,'2026-09-25','biaya',NULL,'Biaya operasional: Uang makan pengambilan','Rp 70.000',3,'2026-09-25 10:54:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (68,5,'2026-09-25','biaya',NULL,'Biaya operasional: Bensin pengambilan','Rp 100.000',3,'2026-09-25 10:56:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (69,5,'2026-09-26','status','inspeksi','Status: Sedang diperiksa','',5,'2026-09-26 10:58:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (70,5,'2026-09-26','inspeksi',NULL,'Inspeksi selesai, Health Score 73','Perlu perbaikan: Suara mesin, Aki, Fungsi rem depan/belakang, Kondisi fisik body. Rusak/tidak ada: Performa tarikan, Ketebalan kampas rem',5,'2026-09-26 11:00:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (71,5,'2026-09-26','dokumen',NULL,'Dokumen dicatat: STNK','Lengkap, no. 30219987',3,'2026-09-26 11:03:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (72,5,'2026-09-26','dokumen',NULL,'Dokumen dicatat: BPKB','Lengkap, no. R-0081123',3,'2026-09-26 11:06:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (73,5,'2026-09-27','status','perbaikan','Status: Dalam perbaikan','Servis CVT, rem, dan kaki-kaki',3,'2026-09-27 11:08:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (74,5,'2026-09-28','sparepart',NULL,'Pakai Shockbreaker belakang x 1 pcs','Rp 400.000 diambil dari stok gudang',5,'2026-09-28 11:11:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (75,5,'2026-09-28','sparepart',NULL,'Pakai Busi x 1 pcs','Rp 25.000 diambil dari stok gudang',5,'2026-09-28 11:14:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (76,5,'2026-09-28','sparepart',NULL,'Pakai Kampas rem depan x 1 set','Rp 55.000 diambil dari stok gudang',5,'2026-09-28 11:17:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (77,5,'2026-10-03','biaya',NULL,'Biaya perbaikan: Turun mesin','Rp 850.000, Ganti seal dan roller CVT',3,'2026-10-03 11:19:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (78,6,'2026-10-15','masuk','masuk','Motor masuk','Dibeli Rp 28.000.000 dari Perorangan, Surabaya',1,'2026-10-15 11:22:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (79,6,'2026-10-15','biaya',NULL,'Biaya operasional: Travel pengambilan luar kota','Rp 300.000',3,'2026-10-15 11:24:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (80,6,'2026-10-16','status','inspeksi','Status: Sedang diperiksa','Menunggu mekanik',3,'2026-10-16 11:26:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (81,7,'2026-10-18','masuk','masuk','Motor masuk','Dibeli Rp 14.200.000 dari Perorangan, Jakarta Timur',1,'2026-10-18 11:29:00');
INSERT INTO asset_activities (id,motor_id,activity_date,type,status,title,detail,created_by,created_at) VALUES (82,7,'2026-10-18','biaya',NULL,'Biaya operasional: Bensin pengambilan','Rp 100.000',3,'2026-10-18 11:31:00');
COMMIT;
