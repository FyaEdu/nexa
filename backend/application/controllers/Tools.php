<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Perintah CLI NEXA (hanya bisa dijalankan dari terminal, bukan browser).
 * Jalankan dari folder backend:
 *
 *   php index.php tools migrate   buat/upgrade tabel database
 *   php index.php tools seed      kosongkan tabel lalu isi data dummy
 *   php index.php tools reset     hapus semua tabel, buat ulang, isi data dummy
 */
class Tools extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		if ( ! is_cli()) {
			show_404();
		}
	}

	public function index()
	{
		echo "Perintah: php index.php tools migrate | seed | reset\n";
	}

	public function migrate()
	{
		$this->load->library('migration');
		if ($this->migration->latest() === FALSE) {
			echo 'Migration gagal: ' . $this->migration->error_string() . "\n";
			exit(1);
		}
		echo "Migration selesai. Database: " . $this->db->database . "\n";
	}

	public function seed()
	{
		$file = APPPATH . 'database/dummy.json';
		$json = json_decode((string) file_get_contents($file), TRUE);
		if ( ! is_array($json) || ! isset($json['order'], $json['tables'])) {
			echo "File data dummy tidak valid: {$file}\n";
			exit(1);
		}
		$order = $json['order'];

		$this->db->trans_start();
		$this->db->empty_table('api_tokens');
		foreach (array_reverse($order) as $table) {
			$this->db->empty_table($table);
		}
		foreach ($order as $table) {
			$rows = isset($json['tables'][$table]) ? $json['tables'][$table] : array();
			if (empty($rows)) {
				continue;
			}
			if ($table === 'users') {
				foreach ($rows as $i => $row) {
					$rows[$i]['password_hash'] = password_hash($row['password'], PASSWORD_DEFAULT);
					unset($rows[$i]['password']);
				}
			}
			$this->db->insert_batch($table, $rows, NULL, 50);
		}
		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			echo "Gagal mengisi data dummy.\n";
			exit(1);
		}
		echo "Data dummy NEXA berhasil dimasukkan.\n";
		echo "Login: admin/admin123, pemilik/pemilik123, operasional/ops123, penjualan/sales123\n";
	}

	public function reset()
	{
		$this->load->library('migration');
		if ($this->migration->version(0) === FALSE || $this->migration->latest() === FALSE) {
			echo 'Reset gagal: ' . $this->migration->error_string() . "\n";
			exit(1);
		}
		echo "Tabel dibuat ulang.\n";
		$this->seed();
	}
}
