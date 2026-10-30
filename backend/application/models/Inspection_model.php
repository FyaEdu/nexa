<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inspection_model extends CI_Model
{
	/** Semua inspeksi (riwayat) beserta info motor */
	public function all()
	{
		$rows = $this->db->query(
			"SELECT i.*, m.code AS motor_code, m.brand, m.model, m.year, m.plate_number,
			        (SELECT COUNT(*) FROM inspection_items it WHERE it.inspection_id = i.id AND it.result <> 'baik') AS findings
			 FROM inspections i JOIN motors m ON m.id = i.motor_id
			 ORDER BY i.inspection_date DESC, i.id DESC"
		)->result_array();
		foreach ($rows as $k => $r) {
			$rows[$k]['score']    = (int) $r['score'];
			$rows[$k]['findings'] = (int) $r['findings'];
		}

		return $rows;
	}

	/** Motor berstatus masuk/inspeksi yang belum pernah diinspeksi */
	public function waiting()
	{
		return $this->db->query(
			"SELECT m.id, m.code, m.brand, m.model, m.year, m.plate_number, m.status, m.purchase_date
			 FROM motors m
			 WHERE m.status IN ('masuk', 'inspeksi')
			   AND NOT EXISTS (SELECT 1 FROM inspections i WHERE i.motor_id = m.id)
			 ORDER BY m.purchase_date"
		)->result_array();
	}

	/** Riwayat inspeksi satu motor, terbaru dulu, beserta item checklist (key => result) */
	public function for_motor($motor_id)
	{
		$list = $this->db->where('motor_id', (int) $motor_id)
			->order_by('inspection_date', 'DESC')->order_by('id', 'DESC')
			->get('inspections')->result_array();
		foreach ($list as $k => $ins) {
			$items = array();
			foreach ($this->db->where('inspection_id', $ins['id'])->order_by('id')->get('inspection_items')->result_array() as $it) {
				$items[$it['item_key']] = $it['result'];
			}
			$list[$k]['score'] = (int) $ins['score'];
			$list[$k]['items'] = $items;
		}

		return $list;
	}

	public function insert($data, $item_rows_builder)
	{
		$data['created_at'] = date('Y-m-d H:i:s');
		$this->db->insert('inspections', $data);
		$id = (int) $this->db->insert_id();
		$this->db->insert_batch('inspection_items', call_user_func($item_rows_builder, $id));

		return $id;
	}
}
