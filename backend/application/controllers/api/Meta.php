<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GET /api/meta : data referensi untuk form di frontend
 * (status lifecycle, role, kategori biaya, template checklist).
 */
class Meta extends Api_Controller
{
	public function index()
	{
		if ( ! $this->guard()) {
			return;
		}

		$this->respond(array(
			'statuses'        => $this->config->item('statuses'),
			'roles'           => $this->db->order_by('id')->get('roles')->result_array(),
			'cost_categories' => $this->db->order_by('id')->get('cost_categories')->result_array(),
			'checklist'       => $this->config->item('checklist'),
		));
	}
}
