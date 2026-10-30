<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tahap 2: data kendaraan, ubah status, detail + Asset Timeline.
 *
 * GET  /api/motors?q=&status=      daftar motor
 * POST /api/motors                 tambah motor masuk
 * GET  /api/motors/{id}            detail lengkap
 * PUT  /api/motors/{id}            edit identitas & harga
 * POST /api/motors/{id}/status     ubah status (masuk/inspeksi/perbaikan/siap)
 */
class Motors extends Api_Controller
{
	/** Status yang boleh diubah manual. Booking & terjual hanya lewat penjualan (Tahap 5). */
	private $manual_statuses = array('masuk', 'inspeksi', 'perbaikan', 'siap');

	private $labels = array(
		'brand' => 'Merek', 'model' => 'Tipe', 'year' => 'Tahun', 'plate_number' => 'Nomor polisi',
		'color' => 'Warna', 'frame_number' => 'Nomor rangka', 'engine_number' => 'Nomor mesin',
		'purchase_date' => 'Tanggal beli', 'purchase_price' => 'Harga beli', 'purchase_source' => 'Asal pembelian',
		'target_price' => 'Harga target', 'condition_note' => 'Catatan kondisi',
	);

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('motor_model', 'asset_activity_model', 'vehicle_document_model', 'inspection_model', 'motor_cost_model', 'inventory_model'));
	}

	private function visible_statuses()
	{
		return $this->user['role'] === 'sales' ? Motor_model::$sales_visible : NULL;
	}

	/** Ambil motor yang boleh dilihat user ini, atau kirim 404 */
	private function motor_or_404($id)
	{
		$motor = $this->motor_model->find($id);
		$only  = $this->visible_statuses();
		if ( ! $motor || ($only && ! in_array($motor['status'], $only, TRUE))) {
			$this->fail('Motor tidak ditemukan.', 404);
			return NULL;
		}

		return $motor;
	}

	/** Validasi & ambil data form motor */
	private function read_form($in, &$errors)
	{
		$data = array();
		foreach (Motor_model::$editable as $f) {
			$data[$f] = $this->str($in, $f);
		}
		foreach (array('brand' => 'Merek', 'model' => 'Tipe', 'plate_number' => 'Nomor polisi') as $f => $label) {
			if ($data[$f] === '') {
				$errors[$f] = $label . ' wajib diisi.';
			}
		}
		$data['plate_number'] = strtoupper($data['plate_number']);
		if ( ! $this->valid_int($data['year'], 1950) || (int) $data['year'] > (int) date('Y') + 1) {
			$errors['year'] = 'Tahun tidak valid.';
		}
		if ( ! $this->valid_date($data['purchase_date'])) {
			$errors['purchase_date'] = 'Tanggal beli wajib diisi (YYYY-MM-DD).';
		}
		if ( ! $this->valid_int($data['purchase_price'], 1)) {
			$errors['purchase_price'] = 'Harga beli harus angka lebih dari 0.';
		}
		if ($data['target_price'] === '') {
			$data['target_price'] = 0;
		} elseif ( ! $this->valid_int($data['target_price'], 0)) {
			$errors['target_price'] = 'Harga target harus angka.';
		}
		$data['year']           = (int) $data['year'];
		$data['purchase_price'] = (int) $data['purchase_price'];
		$data['target_price']   = (int) $data['target_price'];

		return $data;
	}

	public function index()
	{
		if ( ! $this->guard('motor.view')) {
			return;
		}
		$only   = $this->visible_statuses();
		$status = (string) $this->input->get('status');
		$this->respond(array(
			'motors' => $this->motor_model->all(trim((string) $this->input->get('q')), $status, $only),
			'counts' => $this->motor_model->counts($only),
		));
	}

	public function create()
	{
		if ( ! $this->only('post') || ! $this->guard('motor.edit')) {
			return;
		}
		$errors = array();
		$data   = $this->read_form($this->input_data(), $errors);
		if ($this->fail_if_errors($errors)) {
			return;
		}

		$this->db->trans_start();
		$data['code']   = $this->motor_model->next_code();
		$data['status'] = 'masuk';
		$id = $this->motor_model->insert($data);
		$detail = 'Dibeli Rp ' . number_format($data['purchase_price'], 0, ',', '.')
			. ($data['purchase_source'] !== '' ? ' dari ' . $data['purchase_source'] : '');
		$this->asset_activity_model->log($id, 'masuk', 'Motor masuk', $detail, $this->user_id(), $data['purchase_date'], 'masuk');
		$this->db->trans_complete();

		$this->respond($this->motor_model->find($id), 201);
	}

	public function show($id)
	{
		if ( ! $this->guard('motor.view')) {
			return;
		}
		$motor = $this->motor_or_404($id);
		if ( ! $motor) {
			return;
		}
		$this->respond(array(
			'motor'       => $motor,
			'summary'     => $this->motor_model->summary($motor),
			'activities'  => $this->asset_activity_model->for_motor($motor['id']),
			'documents'   => $this->vehicle_document_model->for_motor($motor['id']),
			'inspections' => $this->inspection_model->for_motor($motor['id']),
			'costs'       => $this->can('cost.view') ? $this->motor_cost_model->for_motor($motor['id']) : array(),
			'parts'       => $this->inventory_model->usage_for_motor($motor['id']),
			'sale'        => $this->motor_model->sale_for($motor['id']),
		));
	}

	public function update($id)
	{
		if ( ! $this->only('put') || ! $this->guard('motor.edit')) {
			return;
		}
		$motor = $this->motor_or_404($id);
		if ( ! $motor) {
			return;
		}
		$errors = array();
		$data   = $this->read_form($this->input_data(), $errors);
		if ($this->fail_if_errors($errors)) {
			return;
		}

		$changed = array();
		foreach ($data as $f => $v) {
			if ((string) $motor[$f] !== (string) $v) {
				$changed[] = $this->labels[$f];
			}
		}
		if ( ! $changed) {
			return $this->respond($motor);
		}

		$this->db->trans_start();
		$this->motor_model->update($motor['id'], $data);
		$this->asset_activity_model->log($motor['id'], 'edit', 'Data motor diperbarui', 'Diubah: ' . implode(', ', $changed), $this->user_id());
		$this->db->trans_complete();

		$this->respond($this->motor_model->find($motor['id']));
	}

	public function change_status($id)
	{
		if ( ! $this->only('post') || ! $this->guard('motor.status')) {
			return;
		}
		$motor = $this->motor_or_404($id);
		if ( ! $motor) {
			return;
		}
		$in     = $this->input_data();
		$status = $this->str($in, 'status');
		$date   = $this->str($in, 'date', date('Y-m-d'));
		$note   = $this->str($in, 'note');

		if (in_array($motor['status'], array('booking', 'terjual'), TRUE)) {
			return $this->fail('Motor berstatus ' . $this->status_label($motor['status']) . '. Status diatur lewat menu Penjualan.', 422);
		}
		$errors = array();
		if ( ! in_array($status, $this->manual_statuses, TRUE)) {
			$errors['status'] = 'Pilih status: Baru masuk, Sedang diperiksa, Dalam perbaikan, atau Siap dijual.';
		} elseif ($status === $motor['status']) {
			$errors['status'] = 'Motor sudah berstatus ' . $this->status_label($status) . '.';
		}
		if ( ! $this->valid_date($date)) {
			$errors['date'] = 'Tanggal tidak valid.';
		}
		if ($this->fail_if_errors($errors)) {
			return;
		}

		$this->db->trans_start();
		$this->motor_model->update($motor['id'], array('status' => $status));
		$this->asset_activity_model->log($motor['id'], 'status', 'Status: ' . $this->status_label($status), $note, $this->user_id(), $date, $status);
		$this->db->trans_complete();

		$this->respond($this->motor_model->find($motor['id']));
	}
}
