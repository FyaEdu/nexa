<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tahap 3: pencatatan biaya per motor.
 *
 * GET    /api/costs?motor_id=&category_id=
 * POST   /api/costs
 * DELETE /api/costs/{id}
 */
class Costs extends Api_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('motor_model', 'motor_cost_model', 'asset_activity_model'));
	}

	public function index()
	{
		if ( ! $this->guard('cost.view')) {
			return;
		}
		$motor_id    = (int) $this->input->get('motor_id');
		$category_id = (int) $this->input->get('category_id');
		$costs       = $this->motor_cost_model->all($motor_id ?: NULL, $category_id ?: NULL);

		$this->respond(array(
			'costs'       => $costs,
			'total'       => array_sum(array_column($costs, 'amount')),
			'by_category' => $this->motor_cost_model->totals_by_category($motor_id ?: NULL),
		));
	}

	/** Body: {motor_id, category_id, item, amount, cost_date, note} */
	public function create()
	{
		if ( ! $this->only('post') || ! $this->guard('cost.edit')) {
			return;
		}
		$in       = $this->input_data();
		$errors   = array();
		$motor    = $this->motor_model->find(isset($in['motor_id']) ? (int) $in['motor_id'] : 0);
		$category = $this->motor_cost_model->category(isset($in['category_id']) ? (int) $in['category_id'] : 0);
		$item     = $this->str($in, 'item');
		$amount   = isset($in['amount']) ? $in['amount'] : '';
		$date     = $this->str($in, 'cost_date', date('Y-m-d'));

		if ( ! $motor) {
			$errors['motor_id'] = 'Pilih motor.';
		}
		if ( ! $category) {
			$errors['category_id'] = 'Pilih kategori biaya.';
		}
		if ($item === '') {
			$errors['item'] = 'Rincian biaya wajib diisi.';
		}
		if ( ! $this->valid_int($amount, 1)) {
			$errors['amount'] = 'Nominal harus angka bulat lebih dari 0.';
		}
		if ( ! $this->valid_date($date)) {
			$errors['cost_date'] = 'Tanggal tidak valid.';
		}
		if ($this->fail_if_errors($errors)) {
			return;
		}

		$amount = (int) $amount;
		$note   = $this->str($in, 'note');

		$this->db->trans_start();
		$id = $this->motor_cost_model->insert(array(
			'motor_id'    => $motor['id'],
			'category_id' => (int) $category['id'],
			'item'        => $item,
			'amount'      => $amount,
			'cost_date'   => $date,
			'note'        => $note,
			'created_by'  => $this->user_id(),
		));
		$detail = 'Rp ' . number_format($amount, 0, ',', '.') . ($note !== '' ? ', ' . $note : '');
		$this->asset_activity_model->log($motor['id'], 'biaya', 'Biaya ' . strtolower($category['name']) . ': ' . $item, $detail, $this->user_id(), $date);
		$this->db->trans_complete();

		$this->respond($this->motor_cost_model->find($id), 201);
	}

	public function delete($id)
	{
		if ( ! $this->only('delete') || ! $this->guard('cost.edit')) {
			return;
		}
		$cost = $this->motor_cost_model->find($id);
		if ( ! $cost) {
			return $this->fail('Data biaya tidak ditemukan.', 404);
		}

		$this->db->trans_start();
		$this->motor_cost_model->delete($cost['id']);
		$this->asset_activity_model->log($cost['motor_id'], 'biaya', 'Biaya dihapus: ' . $cost['item'],
			'Rp ' . number_format((int) $cost['amount'], 0, ',', '.'), $this->user_id());
		$this->db->trans_complete();

		$this->respond(array('message' => 'Biaya dihapus.'));
	}
}
