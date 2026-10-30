<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Motor_cost_model extends CI_Model
{
	private function base()
	{
		return $this->db
			->select('x.*, c.name AS category_name, m.code AS motor_code, m.brand, m.model, u.name AS created_by_name')
			->from('motor_costs x')
			->join('cost_categories c', 'c.id = x.category_id')
			->join('motors m', 'm.id = x.motor_id')
			->join('users u', 'u.id = x.created_by', 'left');
	}

	private function cast($rows)
	{
		foreach ($rows as $k => $r) {
			$rows[$k]['id']          = (int) $r['id'];
			$rows[$k]['motor_id']    = (int) $r['motor_id'];
			$rows[$k]['category_id'] = (int) $r['category_id'];
			$rows[$k]['amount']      = (int) $r['amount'];
		}

		return $rows;
	}

	/** Filter opsional: motor_id, category_id */
	public function all($motor_id = NULL, $category_id = NULL)
	{
		$this->base();
		if ($motor_id) {
			$this->db->where('x.motor_id', (int) $motor_id);
		}
		if ($category_id) {
			$this->db->where('x.category_id', (int) $category_id);
		}

		return $this->cast($this->db->order_by('x.cost_date', 'DESC')->order_by('x.id', 'DESC')->get()->result_array());
	}

	public function for_motor($motor_id)
	{
		return $this->all($motor_id);
	}

	/** Total per kategori (semua kategori ikut, walau 0) */
	public function totals_by_category($motor_id = NULL)
	{
		$bind = array();
		$join = 'LEFT JOIN motor_costs x ON x.category_id = c.id';
		if ($motor_id) {
			$join  .= ' AND x.motor_id = ?';
			$bind[] = (int) $motor_id;
		}
		$rows = $this->db->query(
			"SELECT c.id, c.name, COALESCE(SUM(x.amount), 0) AS total FROM cost_categories c {$join} GROUP BY c.id, c.name ORDER BY c.id",
			$bind
		)->result_array();
		foreach ($rows as $k => $r) {
			$rows[$k]['id']    = (int) $r['id'];
			$rows[$k]['total'] = (int) $r['total'];
		}

		return $rows;
	}

	public function find($id)
	{
		$row = $this->base()->where('x.id', (int) $id)->get()->row_array();

		return $row ? $row : NULL;
	}

	public function category($id)
	{
		$row = $this->db->get_where('cost_categories', array('id' => (int) $id))->row_array();

		return $row ? $row : NULL;
	}

	public function insert($data)
	{
		$data['created_at'] = date('Y-m-d H:i:s');
		$this->db->insert('motor_costs', $data);

		return (int) $this->db->insert_id();
	}

	public function delete($id)
	{
		$this->db->where('id', (int) $id)->delete('motor_costs');
	}
}
