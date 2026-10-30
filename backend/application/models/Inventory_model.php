<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Inventaris gudang: barang, kartu stok (inventory_transactions), pemakaian sparepart per motor.
 *
 * Indikator stok (level):
 *   bad  (merah)  : stock <= min_stock
 *   warn (kuning) : stock <= ceil(min_stock x 1,5)
 *   good          : selain itu
 */
class Inventory_model extends CI_Model
{
	public static $units = array('pcs', 'set', 'botol', 'liter', 'meter', 'pasang');

	public static function level($stock, $min)
	{
		if ($stock <= $min) {
			return 'bad';
		}

		return $stock <= (int) ceil($min * 1.5) ? 'warn' : 'good';
	}

	private function decorate($r)
	{
		foreach (array('id', 'unit_price', 'stock', 'min_stock') as $k) {
			$r[$k] = (int) $r[$k];
		}
		$r['value'] = $r['stock'] * $r['unit_price'];
		$r['level'] = self::level($r['stock'], $r['min_stock']);

		return $r;
	}

	public function items($q = '')
	{
		if ($q !== '') {
			$like = '%' . strtolower($q) . '%';
			$this->db->where("LOWER(code || ' ' || name || ' ' || COALESCE(location, '')) LIKE " . $this->db->escape($like), NULL, FALSE);
		}
		$rows = $this->db->order_by('code')->get('inventory_items')->result_array();
		$rows = array_map(array($this, 'decorate'), $rows);
		// barang kritis tampil paling atas
		$rank = array('bad' => 0, 'warn' => 1, 'good' => 2);
		usort($rows, function ($a, $b) use ($rank) {
			$d = $rank[$a['level']] - $rank[$b['level']];

			return $d !== 0 ? $d : strcmp($a['code'], $b['code']);
		});

		return $rows;
	}

	public function find($id)
	{
		$row = $this->db->get_where('inventory_items', array('id' => (int) $id))->row_array();

		return $row ? $this->decorate($row) : NULL;
	}

	public function code_exists($code, $except_id = NULL)
	{
		$this->db->where('LOWER(code)', strtolower($code));
		if ($except_id) {
			$this->db->where('id !=', (int) $except_id);
		}

		return $this->db->count_all_results('inventory_items') > 0;
	}

	public function insert_item($data)
	{
		$now = date('Y-m-d H:i:s');
		$data['stock']      = 0;
		$data['created_at'] = $now;
		$data['updated_at'] = $now;
		$this->db->insert('inventory_items', $data);

		return (int) $this->db->insert_id();
	}

	public function update_item($id, $data)
	{
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where('id', (int) $id)->update('inventory_items', $data);
	}

	/** Barang masuk: tambah stok + catat kartu stok. Harga satuan diperbarui kalau diisi. */
	public function stock_in($item_id, $qty, $price, $supplier, $date, $user_id, $note = '')
	{
		$item = $this->find($item_id);
		$set  = array('updated_at' => date('Y-m-d H:i:s'));
		if ($price > 0) {
			$set['unit_price'] = (int) $price;
		}
		$this->db->set('stock', 'stock + ' . (int) $qty, FALSE)->where('id', (int) $item_id)->update('inventory_items', $set);

		$this->db->insert('inventory_transactions', array(
			'item_id'    => (int) $item_id,
			'type'       => 'in',
			'qty'        => (int) $qty,
			'unit_price' => $price > 0 ? (int) $price : $item['unit_price'],
			'trx_date'   => $date,
			'supplier'   => $supplier,
			'motor_id'   => NULL,
			'note'       => $note,
			'created_by' => $user_id,
			'created_at' => date('Y-m-d H:i:s'),
		));

		return (int) $this->db->insert_id();
	}

	/**
	 * Barang keluar: kurangi stok + catat kartu stok. Kalau untuk motor, catat juga sparepart_usage.
	 * Mengembalikan FALSE kalau stok tidak cukup (tidak ada yang diubah).
	 */
	public function stock_out($item_id, $qty, $motor_id, $date, $user_id, $note = '')
	{
		$item = $this->find($item_id);
		$this->db->set('stock', 'stock - ' . (int) $qty, FALSE)
			->where('id', (int) $item_id)
			->where('stock >=', (int) $qty)
			->update('inventory_items', array('updated_at' => date('Y-m-d H:i:s')));
		if ($this->db->affected_rows() < 1) {
			return FALSE;
		}

		$now = date('Y-m-d H:i:s');
		$this->db->insert('inventory_transactions', array(
			'item_id'    => (int) $item_id,
			'type'       => 'out',
			'qty'        => (int) $qty,
			'unit_price' => $item['unit_price'],
			'trx_date'   => $date,
			'supplier'   => NULL,
			'motor_id'   => $motor_id ? (int) $motor_id : NULL,
			'note'       => $note,
			'created_by' => $user_id,
			'created_at' => $now,
		));
		$trx_id = (int) $this->db->insert_id();

		if ($motor_id) {
			$this->db->insert('sparepart_usage', array(
				'motor_id'       => (int) $motor_id,
				'item_id'        => (int) $item_id,
				'transaction_id' => $trx_id,
				'qty'            => (int) $qty,
				'unit_price'     => $item['unit_price'],
				'total'          => (int) $qty * $item['unit_price'],
				'usage_date'     => $date,
				'created_at'     => $now,
			));
		}

		return $trx_id;
	}

	/** Kartu stok, terbaru dulu. Filter opsional per barang. */
	public function transactions($item_id = NULL, $limit = 300)
	{
		$this->db
			->select('t.*, i.code AS item_code, i.name AS item_name, i.unit, m.code AS motor_code, m.model AS motor_model, u.name AS created_by_name')
			->from('inventory_transactions t')
			->join('inventory_items i', 'i.id = t.item_id')
			->join('motors m', 'm.id = t.motor_id', 'left')
			->join('users u', 'u.id = t.created_by', 'left');
		if ($item_id) {
			$this->db->where('t.item_id', (int) $item_id);
		}
		$rows = $this->db->order_by('t.trx_date', 'DESC')->order_by('t.id', 'DESC')->limit((int) $limit)->get()->result_array();
		foreach ($rows as $k => $r) {
			$rows[$k]['qty']        = (int) $r['qty'];
			$rows[$k]['unit_price'] = (int) $r['unit_price'];
			$rows[$k]['total']      = (int) $r['qty'] * (int) $r['unit_price'];
		}

		return $rows;
	}

	/** Sparepart yang dipakai satu motor */
	public function usage_for_motor($motor_id)
	{
		$rows = $this->db
			->select('su.*, i.code AS item_code, i.name AS item_name, i.unit, t.note, u.name AS created_by_name')
			->from('sparepart_usage su')
			->join('inventory_items i', 'i.id = su.item_id')
			->join('inventory_transactions t', 't.id = su.transaction_id', 'left')
			->join('users u', 'u.id = t.created_by', 'left')
			->where('su.motor_id', (int) $motor_id)
			->order_by('su.usage_date', 'DESC')->order_by('su.id', 'DESC')
			->get()->result_array();
		foreach ($rows as $k => $r) {
			foreach (array('qty', 'unit_price', 'total') as $f) {
				$rows[$k][$f] = (int) $r[$f];
			}
		}

		return $rows;
	}

	public function summary()
	{
		$items = $this->items();
		$count = array('bad' => 0, 'warn' => 0, 'good' => 0);
		foreach ($items as $i) {
			$count[$i['level']]++;
		}

		return array(
			'item_count' => count($items),
			'total_value' => array_sum(array_column($items, 'value')),
			'bad'        => $count['bad'],
			'warn'       => $count['warn'],
		);
	}
}
