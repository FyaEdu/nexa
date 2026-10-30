<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Membuat seluruh tabel NEXA sesuai ERD proposal (Bab 3.3 c).
 * SQL di bawah sama persis dengan database/schema.sql.
 *
 * Jangan ubah file ini setelah dipakai tim. Untuk perubahan struktur, buat file migration baru
 * (mis. 20261101000001_tambah_kolom_x.php) dan naikkan migration_version di config/migration.php.
 */
class Migration_Create_nexa_tables extends CI_Migration
{
	public function up()
	{
		foreach ($this->statements() as $sql) {
			$this->db->query($sql);
		}
	}

	public function down()
	{
		foreach (array_reverse($this->tables()) as $table) {
			$this->dbforge->drop_table($table, TRUE);
		}
	}

	private function tables()
	{
		return array('roles', 'users', 'api_tokens', 'cost_categories', 'motors', 'inspections', 'inspection_items', 'motor_costs', 'inventory_items', 'inventory_transactions', 'sparepart_usage', 'vehicle_documents', 'customers', 'sales', 'asset_activities');
	}

	private function statements()
	{
		$sql = <<<'SQL'
CREATE TABLE roles (
  id   INTEGER PRIMARY KEY AUTOINCREMENT,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(50) NOT NULL
);

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

CREATE TABLE api_tokens (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  token_hash VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at DATETIME
);

CREATE TABLE cost_categories (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  name        VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(200)
);

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

CREATE TABLE inspection_items (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  inspection_id INTEGER NOT NULL REFERENCES inspections(id) ON DELETE CASCADE,
  group_key     VARCHAR(20) NOT NULL,
  item_key      VARCHAR(10) NOT NULL,
  item_label    VARCHAR(80) NOT NULL,
  result        VARCHAR(10) NOT NULL
);

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

CREATE TABLE customers (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  name       VARCHAR(100) NOT NULL,
  phone      VARCHAR(30),
  address    VARCHAR(200),
  created_at DATETIME
);

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
SQL;
		$out = array();
		foreach (explode(';', $sql) as $stmt) {
			$stmt = trim($stmt);
			if ($stmt !== '') {
				$out[] = $stmt;
			}
		}

		return $out;
	}
}
