<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tahap 2: dokumen kendaraan (STNK, BPKB, faktur, ...).
 *
 * POST   /api/motors/{id}/documents
 * PUT    /api/documents/{id}
 * DELETE /api/documents/{id}
 */
class Documents extends Api_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('motor_model', 'vehicle_document_model', 'asset_activity_model'));
	}

	private function read_form($in, &$errors)
	{
		$data = array(
			'doc_type'    => $this->str($in, 'doc_type'),
			'doc_number'  => $this->str($in, 'doc_number'),
			'status'      => $this->str($in, 'status'),
			'expiry_date' => $this->str($in, 'expiry_date'),
			'note'        => $this->str($in, 'note'),
		);
		if ( ! in_array($data['doc_type'], Vehicle_document_model::$types, TRUE)) {
			$errors['doc_type'] = 'Jenis dokumen tidak dikenal.';
		}
		if ( ! in_array($data['status'], Vehicle_document_model::$statuses, TRUE)) {
			$errors['status'] = 'Status dokumen harus Lengkap, Dalam proses, atau Tidak ada.';
		}
		if ($data['expiry_date'] === '') {
			$data['expiry_date'] = NULL;
		} elseif ( ! $this->valid_date($data['expiry_date'])) {
			$errors['expiry_date'] = 'Tanggal berlaku tidak valid.';
		}

		return $data;
	}

	private function detail_text($d)
	{
		return $d['status'] . ($d['doc_number'] !== '' ? ', no. ' . $d['doc_number'] : '');
	}

	public function create($motor_id)
	{
		if ( ! $this->only('post') || ! $this->guard('doc.edit')) {
			return;
		}
		$motor = $this->motor_model->find($motor_id);
		if ( ! $motor) {
			return $this->fail('Motor tidak ditemukan.', 404);
		}
		$errors = array();
		$data   = $this->read_form($this->input_data(), $errors);
		if ($this->fail_if_errors($errors)) {
			return;
		}

		$this->db->trans_start();
		$data['motor_id'] = $motor['id'];
		$id = $this->vehicle_document_model->insert($data);
		$this->asset_activity_model->log($motor['id'], 'dokumen', 'Dokumen dicatat: ' . $data['doc_type'], $this->detail_text($data), $this->user_id());
		$this->db->trans_complete();

		$this->respond($this->vehicle_document_model->find($id), 201);
	}

	public function update($id)
	{
		if ( ! $this->only('put') || ! $this->guard('doc.edit')) {
			return;
		}
		$doc = $this->vehicle_document_model->find($id);
		if ( ! $doc) {
			return $this->fail('Dokumen tidak ditemukan.', 404);
		}
		$errors = array();
		$data   = $this->read_form($this->input_data(), $errors);
		if ($this->fail_if_errors($errors)) {
			return;
		}

		$this->db->trans_start();
		$this->vehicle_document_model->update($doc['id'], $data);
		$this->asset_activity_model->log($doc['motor_id'], 'dokumen', 'Dokumen diperbarui: ' . $data['doc_type'], $this->detail_text($data), $this->user_id());
		$this->db->trans_complete();

		$this->respond($this->vehicle_document_model->find($doc['id']));
	}

	public function delete($id)
	{
		if ( ! $this->only('delete') || ! $this->guard('doc.edit')) {
			return;
		}
		$doc = $this->vehicle_document_model->find($id);
		if ( ! $doc) {
			return $this->fail('Dokumen tidak ditemukan.', 404);
		}

		$this->db->trans_start();
		$this->vehicle_document_model->delete($doc['id']);
		$this->asset_activity_model->log($doc['motor_id'], 'dokumen', 'Dokumen dihapus: ' . $doc['doc_type'], '', $this->user_id());
		$this->db->trans_complete();

		$this->respond(array('message' => 'Dokumen dihapus.'));
	}
}
