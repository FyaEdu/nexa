<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GET /api/dashboard
 * Tahap 1: ringkasan dasar (jumlah per status, modal, stok minimum, aktivitas).
 * Tahap 5 menambahkan grafik modal vs harga jual, komposisi biaya, notifikasi.
 */
class Dashboard extends Api_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('motor_model', 'asset_activity_model'));
	}

	public function index()
	{
		if ( ! $this->guard()) {
			return;
		}

		$counts = array_fill_keys(array_keys($this->config->item('statuses')), 0);
		$rows   = $this->db->query('SELECT status, COUNT(*) AS n FROM motors GROUP BY status')->result_array();
		foreach ($rows as $r) {
			$counts[$r['status']] = (int) $r['n'];
		}

		$modal = Motor_model::MODAL_SQL;
		$money = $this->db->query("
			SELECT
			  COALESCE(SUM(CASE WHEN m.status <> 'terjual' THEN {$modal} END), 0) AS modal_tertanam,
			  COALESCE(SUM(CASE WHEN m.status = 'terjual'
			       THEN (SELECT s.price FROM sales s WHERE s.motor_id = m.id AND s.payment_status <> 'Batal' ORDER BY s.id DESC LIMIT 1) - {$modal}
			  END), 0) AS laba_terjual
			FROM motors m")->row_array();

		$inv = $this->db->query('
			SELECT COALESCE(SUM(stock * unit_price), 0) AS nilai,
			       COALESCE(SUM(CASE WHEN stock <= min_stock THEN 1 ELSE 0 END), 0) AS low
			FROM inventory_items')->row_array();

		$this->respond(array(
			'status_counts'    => $counts,
			'total_motor'      => array_sum($counts),
			'modal_tertanam'   => (int) $money['modal_tertanam'],
			'laba_terjual'     => (int) $money['laba_terjual'],
			'nilai_inventaris' => (int) $inv['nilai'],
			'stok_minimum'     => (int) $inv['low'],
			'recent'           => $this->asset_activity_model->recent(8),
		));
	}
}
