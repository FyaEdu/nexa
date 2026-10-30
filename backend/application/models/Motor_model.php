<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Master aset motor: daftar, detail, ringkasan modal.
 */
class Motor_model extends CI_Model
{
	/**
	 * Ekspresi SQL total modal per motor:
	 * harga beli + semua biaya (motor_costs) + sparepart dari gudang (sparepart_usage).
	 * Pakai di SELECT dengan alias tabel motor "m".
	 */
	const MODAL_SQL = '(m.purchase_price
		+ COALESCE((SELECT SUM(c.amount) FROM motor_costs c WHERE c.motor_id = m.id), 0)
		+ COALESCE((SELECT SUM(u.total) FROM sparepart_usage u WHERE u.motor_id = m.id), 0))';

	/** Harga dari transaksi penjualan terakhir yang tidak batal */
	const SALE_PRICE_SQL = "(SELECT s.price FROM sales s WHERE s.motor_id = m.id AND s.payment_status <> 'Batal' ORDER BY s.id DESC LIMIT 1)";

	/** Kolom yang boleh diisi dari form tambah/edit motor */
	public static $editable = array(
		'brand', 'model', 'year', 'plate_number', 'color', 'frame_number', 'engine_number',
		'purchase_date', 'purchase_price', 'purchase_source', 'target_price', 'condition_note',
	);

	/** Status yang boleh dilihat Staff Penjualan */
	public static $sales_visible = array('siap', 'booking', 'terjual');

	private function base_select()
	{
		return 'SELECT m.*, ' . self::MODAL_SQL . ' AS total_modal, ' . self::SALE_PRICE_SQL . ' AS sale_price FROM motors m';
	}

	/** Tambah total_modal, price (harga jual/target), profit dalam bentuk angka */
	public function decorate($m)
	{
		foreach (array('id', 'purchase_price', 'target_price', 'total_modal') as $k) {
			$m[$k] = (int) $m[$k];
		}
		$m['year']         = $m['year'] === NULL ? NULL : (int) $m['year'];
		$m['health_score'] = $m['health_score'] === NULL ? NULL : (int) $m['health_score'];
		$m['sale_price']   = $m['sale_price'] === NULL ? NULL : (int) $m['sale_price'];
		$m['price']        = $m['sale_price'] !== NULL ? $m['sale_price'] : $m['target_price'];
		$m['profit']       = $m['price'] > 0 ? $m['price'] - $m['total_modal'] : NULL;

		return $m;
	}

	/**
	 * @param string     $q      kata kunci (kode, merek, tipe, nopol, tahun, warna)
	 * @param string     $status filter status, kosong = semua
	 * @param array|null $only   batasi ke daftar status tertentu (untuk role sales)
	 */
	public function all($q = '', $status = '', $only = NULL)
	{
		$sql  = $this->base_select() . ' WHERE 1 = 1';
		$bind = array();
		if ($only) {
			$sql .= ' AND m.status IN (' . implode(',', array_fill(0, count($only), '?')) . ')';
			$bind = array_merge($bind, $only);
		}
		if ($status !== '') {
			$sql   .= ' AND m.status = ?';
			$bind[] = $status;
		}
		if ($q !== '') {
			$sql .= " AND LOWER(m.code || ' ' || m.brand || ' ' || m.model || ' ' || COALESCE(m.plate_number, '')
				|| ' ' || COALESCE(m.year, '') || ' ' || COALESCE(m.color, '')) LIKE ?";
			$bind[] = '%' . strtolower($q) . '%';
		}
		$sql .= " ORDER BY CASE m.status WHEN 'masuk' THEN 0 WHEN 'inspeksi' THEN 1 WHEN 'perbaikan' THEN 2
			WHEN 'siap' THEN 3 WHEN 'booking' THEN 4 ELSE 5 END, m.purchase_date DESC, m.id DESC";

		return array_map(array($this, 'decorate'), $this->db->query($sql, $bind)->result_array());
	}

	/** Jumlah motor per status (untuk chip filter) */
	public function counts($only = NULL)
	{
		$counts = array_fill_keys(array_keys($this->config->item('statuses')), 0);
		foreach ($this->db->query('SELECT status, COUNT(*) AS n FROM motors GROUP BY status')->result_array() as $r) {
			if ( ! $only || in_array($r['status'], $only, TRUE)) {
				$counts[$r['status']] = (int) $r['n'];
			}
		}
		if ($only) {
			$counts = array_intersect_key($counts, array_flip($only));
		}

		return $counts;
	}

	public function find($id)
	{
		$row = $this->db->query($this->base_select() . ' WHERE m.id = ?', array((int) $id))->row_array();

		return $row ? $this->decorate($row) : NULL;
	}

	public function next_code()
	{
		$max = 0;
		foreach ($this->db->select('code')->get('motors')->result_array() as $r) {
			$n   = (int) preg_replace('/\D/', '', $r['code']);
			$max = max($max, $n);
		}

		return 'NX-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
	}

	/** Rincian modal: harga beli, biaya per kategori, sparepart, total, harga, laba */
	public function summary($motor)
	{
		$by_cat = $this->db->query(
			'SELECT c.id, c.name, COALESCE(SUM(x.amount), 0) AS total
			 FROM cost_categories c
			 LEFT JOIN motor_costs x ON x.category_id = c.id AND x.motor_id = ?
			 GROUP BY c.id, c.name ORDER BY c.id',
			array($motor['id'])
		)->result_array();
		foreach ($by_cat as $i => $r) {
			$by_cat[$i]['id']    = (int) $r['id'];
			$by_cat[$i]['total'] = (int) $r['total'];
		}
		$parts = (int) $this->db->query(
			'SELECT COALESCE(SUM(total), 0) AS t FROM sparepart_usage WHERE motor_id = ?',
			array($motor['id'])
		)->row()->t;

		return array(
			'purchase_price' => $motor['purchase_price'],
			'costs'          => $by_cat,
			'costs_total'    => array_sum(array_column($by_cat, 'total')),
			'parts_total'    => $parts,
			'total_modal'    => $motor['total_modal'],
			'price'          => $motor['price'],
			'price_source'   => $motor['status'] === 'terjual' ? 'terjual' : ($motor['sale_price'] !== NULL ? 'disepakati' : 'target'),
			'profit'         => $motor['profit'],
		);
	}

	/**
	 * Transaksi penjualan aktif (tidak batal) beserta customer.
	 * Hanya dibaca di Tahap 2-4; aksi booking/penjualan dibuat di Tahap 5.
	 */
	public function sale_for($motor_id)
	{
		$row = $this->db->query(
			"SELECT s.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address
			 FROM sales s JOIN customers c ON c.id = s.customer_id
			 WHERE s.motor_id = ? AND s.payment_status <> 'Batal' ORDER BY s.id DESC LIMIT 1",
			array((int) $motor_id)
		)->row_array();
		if ( ! $row) {
			return NULL;
		}
		foreach (array('id', 'price', 'dp') as $k) {
			$row[$k] = (int) $row[$k];
		}

		return $row;
	}

	public function insert($data)
	{
		$now = date('Y-m-d H:i:s');
		$data['created_at'] = $now;
		$data['updated_at'] = $now;
		$this->db->insert('motors', $data);

		return (int) $this->db->insert_id();
	}

	public function update($id, $data)
	{
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where('id', (int) $id)->update('motors', $data);
	}
}
