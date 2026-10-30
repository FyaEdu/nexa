<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vehicle_document_model extends CI_Model
{
	public static $types    = array('STNK', 'BPKB', 'Faktur', 'Kuitansi pembelian', 'KTP pemilik lama', 'Lainnya');
	public static $statuses = array('Lengkap', 'Dalam proses', 'Tidak ada');

	public function for_motor($motor_id)
	{
		return $this->db->where('motor_id', (int) $motor_id)->order_by('id')->get('vehicle_documents')->result_array();
	}

	public function find($id)
	{
		$row = $this->db->get_where('vehicle_documents', array('id' => (int) $id))->row_array();

		return $row ? $row : NULL;
	}

	public function insert($data)
	{
		$now = date('Y-m-d H:i:s');
		$data['created_at'] = $now;
		$data['updated_at'] = $now;
		$this->db->insert('vehicle_documents', $data);

		return (int) $this->db->insert_id();
	}

	public function update($id, $data)
	{
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where('id', (int) $id)->update('vehicle_documents', $data);
	}

	public function delete($id)
	{
		$this->db->where('id', (int) $id)->delete('vehicle_documents');
	}
}
