<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Event lifecycle motor (Asset Timeline). Setiap perubahan pada motor
 * WAJIB dicatat lewat log() agar timeline lengkap.
 *
 * type: masuk | status | edit | inspeksi | biaya | sparepart | dokumen | booking | terjual | batal
 * $status diisi kalau event ini mengubah status motor (dipakai jalur status di detail motor).
 */
class Asset_activity_model extends CI_Model
{
	public function log($motor_id, $type, $title, $detail = '', $user_id = NULL, $date = NULL, $status = NULL)
	{
		$this->db->insert('asset_activities', array(
			'motor_id'      => (int) $motor_id,
			'activity_date' => $date ? $date : date('Y-m-d'),
			'type'          => $type,
			'status'        => $status,
			'title'         => $title,
			'detail'        => $detail,
			'created_by'    => $user_id,
			'created_at'    => date('Y-m-d H:i:s'),
		));
	}

	/** Aktivitas terbaru lintas motor (untuk dashboard). */
	public function recent($limit = 8)
	{
		return $this->db
			->select('a.*, m.code AS motor_code, m.brand, m.model, u.name AS created_by_name')
			->from('asset_activities a')
			->join('motors m', 'm.id = a.motor_id')
			->join('users u', 'u.id = a.created_by', 'left')
			->order_by('a.activity_date', 'DESC')
			->order_by('a.id', 'DESC')
			->limit((int) $limit)
			->get()->result_array();
	}

	/** Timeline satu motor, urut kronologis (lama ke baru). */
	public function for_motor($motor_id)
	{
		return $this->db
			->select('a.*, u.name AS created_by_name')
			->from('asset_activities a')
			->join('users u', 'u.id = a.created_by', 'left')
			->where('a.motor_id', (int) $motor_id)
			->order_by('a.activity_date', 'ASC')
			->order_by('a.id', 'ASC')
			->get()->result_array();
	}
}
