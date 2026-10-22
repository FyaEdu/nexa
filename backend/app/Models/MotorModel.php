<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Master aset motor. Tahap 2 menambahkan method daftar/detail di sini.
 */
class MotorModel extends Model
{
    protected $table         = 'motors';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'code', 'brand', 'model', 'year', 'plate_number', 'color', 'frame_number', 'engine_number',
        'purchase_date', 'purchase_price', 'purchase_source', 'target_price', 'condition_note',
        'status', 'health_score',
    ];

    /**
     * Ekspresi SQL total modal per motor:
     * harga beli + semua biaya (motor_costs) + sparepart dari gudang (sparepart_usage).
     * Pakai di SELECT dengan alias tabel motor "m".
     */
    public const MODAL_SQL = '(m.purchase_price'
        . ' + COALESCE((SELECT SUM(c.amount) FROM motor_costs c WHERE c.motor_id = m.id), 0)'
        . ' + COALESCE((SELECT SUM(u.total) FROM sparepart_usage u WHERE u.motor_id = m.id), 0))';
}
