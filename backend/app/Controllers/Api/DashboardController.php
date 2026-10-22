<?php

namespace App\Controllers\Api;

use App\Models\AssetActivityModel;
use App\Models\MotorModel;

/**
 * GET /api/dashboard
 * Tahap 1: ringkasan dasar (jumlah per status, modal, stok minimum, aktivitas).
 * Tahap 5 menambahkan grafik modal vs harga jual dan komposisi biaya.
 */
class DashboardController extends BaseApiController
{
    public function index()
    {
        if ($deny = $this->guard()) {
            return $deny;
        }

        $counts = array_fill_keys(array_keys(config('Nexa')->statuses), 0);
        $rows   = $this->db->query('SELECT status, COUNT(*) AS n FROM motors GROUP BY status')->getResultArray();
        foreach ($rows as $r) {
            $counts[$r['status']] = (int) $r['n'];
        }

        $modal = MotorModel::MODAL_SQL;
        $money = $this->db->query("
            SELECT
              COALESCE(SUM(CASE WHEN m.status <> 'terjual' THEN {$modal} END), 0) AS modal_tertanam,
              COALESCE(SUM(CASE WHEN m.status = 'terjual'
                   THEN (SELECT s.price FROM sales s WHERE s.motor_id = m.id AND s.payment_status <> 'Batal' ORDER BY s.id DESC LIMIT 1) - {$modal}
              END), 0) AS laba_terjual
            FROM motors m")->getRowArray();

        $inv = $this->db->query('
            SELECT COALESCE(SUM(stock * unit_price), 0) AS nilai,
                   SUM(CASE WHEN stock <= min_stock THEN 1 ELSE 0 END) AS low
            FROM inventory_items')->getRowArray();

        return $this->respond([
            'status_counts'   => $counts,
            'total_motor'     => array_sum($counts),
            'modal_tertanam'  => (int) $money['modal_tertanam'],
            'laba_terjual'    => (int) $money['laba_terjual'],
            'nilai_inventaris' => (int) $inv['nilai'],
            'stok_minimum'    => (int) $inv['low'],
            'recent'          => (new AssetActivityModel())->recent(8),
        ]);
    }
}
