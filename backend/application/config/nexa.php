<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Konfigurasi bisnis NEXA (dimuat otomatis lewat autoload.php).
| Akses: $this->config->item('permissions')
| Harus sama dengan frontend/src/utils/constants.js dan tools/generate_dummy.py.
*/

// Masa berlaku token login (jam)
$config['token_ttl_hours'] = 12;

// Urutan lifecycle motor. Kunci disimpan di kolom motors.status
$config['statuses'] = array(
	'masuk'     => 'Baru masuk',
	'inspeksi'  => 'Sedang diperiksa',
	'perbaikan' => 'Dalam perbaikan',
	'siap'      => 'Siap dijual',
	'booking'   => 'Booking',
	'terjual'   => 'Terjual',
);

// Hak akses per aksi (role-based access control, Bab 3.3 a).
// Dipakai di controller: if ( ! $this->guard('motor.edit')) return;
$config['permissions'] = array(
	'motor.view'   => array('admin', 'owner', 'ops', 'sales'),
	'motor.edit'   => array('admin', 'ops'),
	'motor.status' => array('admin', 'ops'),
	'inspect'      => array('admin', 'ops'),
	'cost.view'    => array('admin', 'owner', 'ops'),
	'cost.edit'    => array('admin', 'ops'),
	'doc.edit'     => array('admin', 'ops'),
	'inv.view'     => array('admin', 'owner', 'ops'),
	'inv.edit'     => array('admin', 'ops'),
	'sale.view'    => array('admin', 'owner', 'sales'),
	'sale.edit'    => array('admin', 'sales'),
	'report.view'  => array('admin', 'owner', 'sales'),
	'user.manage'  => array('admin'),
);

// Checklist inspeksi (Bab 2.1). weight = bobot grup (persen, total 100).
// Nilai item: baik = 1, perlu = 0,5, rusak = 0.
// Health Score = jumlah(bobot grup x rata-rata nilai item grup), dibulatkan.
$config['checklist'] = array(
	'mesin' => array('label' => 'Kondisi mesin', 'weight' => 35, 'items' => array(
		'm1' => 'Suara mesin', 'm2' => 'Kebocoran oli', 'm3' => 'Sistem pengapian', 'm4' => 'Performa tarikan',
	)),
	'listrik' => array('label' => 'Sistem kelistrikan', 'weight' => 20, 'items' => array(
		'l1' => 'Aki', 'l2' => 'Lampu depan/belakang', 'l3' => 'Lampu sein', 'l4' => 'Klakson', 'l5' => 'Starter',
	)),
	'rem' => array('label' => 'Pengereman & body', 'weight' => 30, 'items' => array(
		'r1' => 'Ketebalan kampas rem', 'r2' => 'Fungsi rem depan/belakang', 'r3' => 'Kondisi fisik body',
		'r4' => 'Ketersediaan spion', 'r5' => 'Keausan ban',
	)),
	'dok' => array('label' => 'Kelengkapan dokumen', 'weight' => 15, 'items' => array(
		'd1' => 'Nomor rangka sesuai', 'd2' => 'Nomor mesin sesuai', 'd3' => 'STNK', 'd4' => 'BPKB',
	)),
);

$config['result_values'] = array('baik' => 1.0, 'perlu' => 0.5, 'rusak' => 0.0);
