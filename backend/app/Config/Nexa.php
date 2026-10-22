<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi bisnis NEXA. Daftar di sini harus sama dengan
 * frontend/src/utils/constants.js dan tools/generate_dummy.py.
 */
class Nexa extends BaseConfig
{
    /** Masa berlaku token login dalam jam (bisa diubah di .env: nexa.tokenTtlHours) */
    public int $tokenTtlHours = 12;

    /** Urutan lifecycle motor. Kunci disimpan di kolom motors.status */
    public array $statuses = [
        'masuk'     => 'Baru masuk',
        'inspeksi'  => 'Sedang diperiksa',
        'perbaikan' => 'Dalam perbaikan',
        'siap'      => 'Siap dijual',
        'booking'   => 'Booking',
        'terjual'   => 'Terjual',
    ];

    /**
     * Hak akses per aksi (role-based access control, Bab 3.3 a).
     * Dipakai di controller lewat $this->guard(...) dan di frontend untuk menyembunyikan tombol.
     */
    public array $permissions = [
        'motor.view'   => ['admin', 'owner', 'ops', 'sales'],
        'motor.edit'   => ['admin', 'ops'],
        'motor.status' => ['admin', 'ops'],
        'inspect'      => ['admin', 'ops'],
        'cost.view'    => ['admin', 'owner', 'ops'],
        'cost.edit'    => ['admin', 'ops'],
        'doc.edit'     => ['admin', 'ops'],
        'inv.view'     => ['admin', 'owner', 'ops'],
        'inv.edit'     => ['admin', 'ops'],
        'sale.view'    => ['admin', 'owner', 'sales'],
        'sale.edit'    => ['admin', 'sales'],
        'report.view'  => ['admin', 'owner', 'sales'],
        'user.manage'  => ['admin'],
    ];

    /**
     * Checklist inspeksi (Bab 2.1). weight = bobot grup dalam persen (total 100).
     * Nilai item: baik = 1, perlu = 0,5, rusak = 0.
     * Health Score = jumlah(bobot grup x rata-rata nilai item grup), dibulatkan.
     */
    public array $checklist = [
        'mesin' => ['label' => 'Kondisi mesin', 'weight' => 35, 'items' => [
            'm1' => 'Suara mesin', 'm2' => 'Kebocoran oli', 'm3' => 'Sistem pengapian', 'm4' => 'Performa tarikan',
        ]],
        'listrik' => ['label' => 'Sistem kelistrikan', 'weight' => 20, 'items' => [
            'l1' => 'Aki', 'l2' => 'Lampu depan/belakang', 'l3' => 'Lampu sein', 'l4' => 'Klakson', 'l5' => 'Starter',
        ]],
        'rem' => ['label' => 'Pengereman & body', 'weight' => 30, 'items' => [
            'r1' => 'Ketebalan kampas rem', 'r2' => 'Fungsi rem depan/belakang', 'r3' => 'Kondisi fisik body',
            'r4' => 'Ketersediaan spion', 'r5' => 'Keausan ban',
        ]],
        'dok' => ['label' => 'Kelengkapan dokumen', 'weight' => 15, 'items' => [
            'd1' => 'Nomor rangka sesuai', 'd2' => 'Nomor mesin sesuai', 'd3' => 'STNK', 'd4' => 'BPKB',
        ]],
    ];

    public array $resultValues = ['baik' => 1.0, 'perlu' => 0.5, 'rusak' => 0.0];
}
