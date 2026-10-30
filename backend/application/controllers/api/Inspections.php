<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tahap 3: inspeksi + Asset Health Score.
 *
 * GET  /api/inspections   riwayat + daftar motor yang menunggu inspeksi
 * POST /api/inspections   simpan checklist, hitung skor, perbarui status motor
 */
class Inspections extends Api_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('motor_model', 'inspection_model', 'asset_activity_model'));
		$this->load->library('health_score');
	}

	public function index()
	{
		if ( ! $this->guard('inspect')) {
			return;
		}
		$this->respond(array(
			'inspections' => $this->inspection_model->all(),
			'waiting'     => $this->inspection_model->waiting(),
		));
	}

	/**
	 * Body: {motor_id, inspection_date, inspector, notes, items: {m1: 'baik'|'perlu'|'rusak', ...}, to_repair: true}
	 */
	public function create()
	{
		if ( ! $this->only('post') || ! $this->guard('inspect')) {
			return;
		}
		$in     = $this->input_data();
		$errors = array();
		$motor  = $this->motor_model->find(isset($in['motor_id']) ? (int) $in['motor_id'] : 0);
		if ( ! $motor) {
			$errors['motor_id'] = 'Pilih motor yang diperiksa.';
		} elseif (in_array($motor['status'], array('booking', 'terjual'), TRUE)) {
			$errors['motor_id'] = 'Motor yang sudah dibooking/terjual tidak bisa diinspeksi.';
		}
		$date = $this->str($in, 'inspection_date', date('Y-m-d'));
		if ( ! $this->valid_date($date)) {
			$errors['inspection_date'] = 'Tanggal inspeksi tidak valid.';
		}
		$inspector = $this->str($in, 'inspector', $this->user['name']);
		if ($inspector === '') {
			$errors['inspector'] = 'Nama pemeriksa wajib diisi.';
		}
		$items = $this->health_score->normalize(isset($in['items']) ? $in['items'] : array(), $errors);
		if ($this->fail_if_errors($errors)) {
			return;
		}

		$score       = $this->health_score->calculate($items);
		$has_finding = in_array('perlu', $items, TRUE) || in_array('rusak', $items, TRUE);
		$to_repair   = ! empty($in['to_repair']);
		$uid         = $this->user_id();
		$hs          = $this->health_score;

		$this->db->trans_start();
		if ($motor['status'] === 'masuk') {
			$this->motor_model->update($motor['id'], array('status' => 'inspeksi'));
			$this->asset_activity_model->log($motor['id'], 'status', 'Status: ' . $this->status_label('inspeksi'), '', $uid, $date, 'inspeksi');
			$motor['status'] = 'inspeksi';
		}
		$ins_id = $this->inspection_model->insert(array(
			'motor_id'        => $motor['id'],
			'inspection_date' => $date,
			'inspector'       => $inspector,
			'notes'           => $this->str($in, 'notes'),
			'score'           => $score,
			'created_by'      => $uid,
		), function ($id) use ($hs, $items) {
			return $hs->rows($id, $items);
		});
		$this->motor_model->update($motor['id'], array('health_score' => $score));
		$this->asset_activity_model->log($motor['id'], 'inspeksi', 'Inspeksi selesai, Health Score ' . $score, $this->health_score->findings_text($items), $uid, $date);
		if ($to_repair && $has_finding && $motor['status'] === 'inspeksi') {
			$this->motor_model->update($motor['id'], array('status' => 'perbaikan'));
			$this->asset_activity_model->log($motor['id'], 'status', 'Status: ' . $this->status_label('perbaikan'), 'Tindak lanjut hasil inspeksi', $uid, $date, 'perbaikan');
			$motor['status'] = 'perbaikan';
		}
		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			return $this->fail('Gagal menyimpan inspeksi.', 500);
		}

		$this->respond(array(
			'inspection_id' => $ins_id,
			'score'         => $score,
			'label'         => Health_score::label($score),
			'groups'        => $this->health_score->group_scores($items),
			'motor_status'  => $motor['status'],
		), 201);
	}
}
