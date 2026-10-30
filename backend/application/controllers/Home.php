<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller
{
	/** GET /  -> cek backend berjalan (dipakai juga oleh healthcheck Docker) */
	public function index()
	{
		$this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode(array(
			'app'     => 'NEXA API (CodeIgniter 3)',
			'status'  => 'berjalan',
			'catatan' => 'Buka aplikasi di http://localhost:5173 (frontend Vue).',
		)));
	}

	/** Dipakai sebagai 404_override supaya endpoint yang salah tetap dibalas JSON */
	public function not_found()
	{
		$this->output->set_status_header(404)->set_content_type('application/json', 'utf-8')->set_output(json_encode(array(
			'status'   => 404,
			'error'    => 404,
			'messages' => array('error' => 'Endpoint tidak ditemukan.'),
		)));
	}
}
