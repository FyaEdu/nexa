<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tahap 4: inventaris & sparepart.
 *
 * GET  /api/inventory?q=              daftar barang + ringkasan
 * POST /api/inventory                 barang baru (stok awal opsional)
 * PUT  /api/inventory/{id}            edit barang (stok tidak diubah di sini)
 * GET  /api/inventory/transactions    kartu stok (?item_id=)
 * POST /api/inventory/in              barang masuk dari supplier
 * POST /api/inventory/out             barang keluar / pakai sparepart untuk motor
 */
class Inventory extends Api_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('inventory_model', 'motor_model', 'asset_activity_model'));
	}

	private function rp($n)
	{
		return 'Rp ' . number_format((int) $n, 0, ',', '.');
	}

	private function read_item($in, &$errors, $is_new)
	{
		$data = array(
			'code'       => strtoupper($this->str($in, 'code')),
			'name'       => $this->str($in, 'name'),
			'unit'       => $this->str($in, 'unit', 'pcs'),
			'unit_price' => isset($in['unit_price']) ? $in['unit_price'] : '',
			'min_stock'  => isset($in['min_stock']) ? $in['min_stock'] : '',
			'location'   => $this->str($in, 'location'),
		);
		if ($data['code'] === '') {
			$errors['code'] = 'Kode barang wajib diisi.';
		}
		if ($data['name'] === '') {
			$errors['name'] = 'Nama barang wajib diisi.';
		}
		if ( ! in_array($data['unit'], Inventory_model::$units, TRUE)) {
			$errors['unit'] = 'Satuan harus salah satu dari: ' . implode(', ', Inventory_model::$units) . '.';
		}
		if ( ! $this->valid_int($data['unit_price'], 0)) {
			$errors['unit_price'] = 'Harga satuan harus angka.';
		}
		if ( ! $this->valid_int($data['min_stock'], 0)) {
			$errors['min_stock'] = 'Stok minimum harus angka 0 atau lebih.';
		}
		$data['unit_price'] = (int) $data['unit_price'];
		$data['min_stock']  = (int) $data['min_stock'];

		return $data;
	}

	public function index()
	{
		if ( ! $this->guard('inv.view')) {
			return;
		}
		$this->respond(array(
			'items'   => $this->inventory_model->items(trim((string) $this->input->get('q'))),
			'summary' => $this->inventory_model->summary(),
		));
	}

	public function create()
	{
		if ( ! $this->only('post') || ! $this->guard('inv.edit')) {
			return;
		}
		$in     = $this->input_data();
		$errors = array();
		$data   = $this->read_item($in, $errors, TRUE);
		$stock  = isset($in['stock']) && $in['stock'] !== '' ? $in['stock'] : 0;
		if ( ! $this->valid_int($stock, 0)) {
			$errors['stock'] = 'Stok awal harus angka 0 atau lebih.';
		}
		if ( ! isset($errors['code']) && $this->inventory_model->code_exists($data['code'])) {
			$errors['code'] = 'Kode barang sudah dipakai.';
		}
		if ($this->fail_if_errors($errors)) {
			return;
		}

		$this->db->trans_start();
		$id = $this->inventory_model->insert_item($data);
		if ((int) $stock > 0) {
			$this->inventory_model->stock_in($id, (int) $stock, $data['unit_price'], 'Stok awal', date('Y-m-d'), $this->user_id());
		}
		$this->db->trans_complete();

		$this->respond($this->inventory_model->find($id), 201);
	}

	public function update($id)
	{
		if ( ! $this->only('put') || ! $this->guard('inv.edit')) {
			return;
		}
		$item = $this->inventory_model->find($id);
		if ( ! $item) {
			return $this->fail('Barang tidak ditemukan.', 404);
		}
		$errors = array();
		$data   = $this->read_item($this->input_data(), $errors, FALSE);
		if ( ! isset($errors['code']) && $this->inventory_model->code_exists($data['code'], $item['id'])) {
			$errors['code'] = 'Kode barang sudah dipakai.';
		}
		if ($this->fail_if_errors($errors)) {
			return;
		}
		$this->inventory_model->update_item($item['id'], $data);
		$this->respond($this->inventory_model->find($item['id']));
	}

	public function transactions()
	{
		if ( ! $this->guard('inv.view')) {
			return;
		}
		$item_id = (int) $this->input->get('item_id');
		$this->respond(array('transactions' => $this->inventory_model->transactions($item_id ?: NULL)));
	}

	/** Body: {item_id, qty, unit_price (opsional), supplier, trx_date, note} */
	public function stock_in()
	{
		if ( ! $this->only('post') || ! $this->guard('inv.edit')) {
			return;
		}
		$in     = $this->input_data();
		$errors = array();
		$item   = $this->inventory_model->find(isset($in['item_id']) ? (int) $in['item_id'] : 0);
		$qty    = isset($in['qty']) ? $in['qty'] : '';
		$price  = isset($in['unit_price']) && $in['unit_price'] !== '' && $in['unit_price'] !== NULL ? $in['unit_price'] : 0;
		$date   = $this->str($in, 'trx_date', date('Y-m-d'));
		$supp   = $this->str($in, 'supplier');

		if ( ! $item) {
			$errors['item_id'] = 'Pilih barang.';
		}
		if ( ! $this->valid_int($qty, 1)) {
			$errors['qty'] = 'Jumlah harus angka bulat lebih dari 0.';
		}
		if ( ! $this->valid_int($price, 0)) {
			$errors['unit_price'] = 'Harga satuan harus angka.';
		}
		if ($supp === '') {
			$errors['supplier'] = 'Nama supplier wajib diisi.';
		}
		if ( ! $this->valid_date($date)) {
			$errors['trx_date'] = 'Tanggal tidak valid.';
		}
		if ($this->fail_if_errors($errors)) {
			return;
		}

		$this->db->trans_start();
		$this->inventory_model->stock_in($item['id'], (int) $qty, (int) $price, $supp, $date, $this->user_id(), $this->str($in, 'note'));
		$this->db->trans_complete();

		$this->respond($this->inventory_model->find($item['id']), 201);
	}

	/** Body: {item_id, qty, motor_id (boleh kosong), trx_date, note} */
	public function stock_out()
	{
		if ( ! $this->only('post') || ! $this->guard('inv.edit')) {
			return;
		}
		$in       = $this->input_data();
		$errors   = array();
		$item     = $this->inventory_model->find(isset($in['item_id']) ? (int) $in['item_id'] : 0);
		$qty      = isset($in['qty']) ? $in['qty'] : '';
		$date     = $this->str($in, 'trx_date', date('Y-m-d'));
		$motor_id = isset($in['motor_id']) && $in['motor_id'] !== '' && $in['motor_id'] !== NULL ? (int) $in['motor_id'] : NULL;
		$motor    = $motor_id ? $this->motor_model->find($motor_id) : NULL;

		if ( ! $item) {
			$errors['item_id'] = 'Pilih barang.';
		}
		if ( ! $this->valid_int($qty, 1)) {
			$errors['qty'] = 'Jumlah harus angka bulat lebih dari 0.';
		} elseif ($item && (int) $qty > $item['stock']) {
			$errors['qty'] = 'Stok ' . $item['name'] . ' hanya ' . $item['stock'] . ' ' . $item['unit'] . '.';
		}
		if ($motor_id && ! $motor) {
			$errors['motor_id'] = 'Motor tidak ditemukan.';
		} elseif ($motor && $motor['status'] === 'terjual') {
			$errors['motor_id'] = 'Motor sudah terjual, sparepart tidak bisa ditambahkan.';
		}
		if ( ! $this->valid_date($date)) {
			$errors['trx_date'] = 'Tanggal tidak valid.';
		}
		if ($this->fail_if_errors($errors)) {
			return;
		}

		$qty  = (int) $qty;
		$note = $this->str($in, 'note');

		$this->db->trans_start();
		$trx = $this->inventory_model->stock_out($item['id'], $qty, $motor ? $motor['id'] : NULL, $date, $this->user_id(), $note);
		if ($trx !== FALSE && $motor) {
			$this->asset_activity_model->log($motor['id'], 'sparepart', 'Pakai ' . $item['name'] . ' x ' . $qty . ' ' . $item['unit'],
				$this->rp($qty * $item['unit_price']) . ' diambil dari stok gudang' . ($note !== '' ? ', ' . $note : ''), $this->user_id(), $date);
		}
		$this->db->trans_complete();

		if ($trx === FALSE) {
			return $this->fail('', 422, array('qty' => 'Stok tidak mencukupi. Muat ulang halaman untuk melihat stok terbaru.'));
		}

		$after = $this->inventory_model->find($item['id']);
		$this->respond(array('item' => $after, 'transaction_id' => $trx, 'low_stock' => $after['level'] === 'bad'), 201);
	}
}
