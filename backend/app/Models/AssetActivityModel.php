<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Event lifecycle motor (Asset Timeline). Semua perubahan pada motor
 * WAJIB dicatat lewat method log() agar timeline lengkap.
 */
class AssetActivityModel extends Model
{
    protected $table         = 'asset_activities';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = ['motor_id', 'activity_date', 'type', 'status', 'title', 'detail', 'created_by'];

    public function log(int $motorId, string $type, string $title, string $detail = '', ?int $userId = null, ?string $date = null, ?string $status = null): void
    {
        $this->insert([
            'motor_id'      => $motorId,
            'activity_date' => $date ?: date('Y-m-d'),
            'type'          => $type,
            'status'        => $status,
            'title'         => $title,
            'detail'        => $detail,
            'created_by'    => $userId,
        ]);
    }

    /** Aktivitas terbaru lintas motor (untuk dashboard). */
    public function recent(int $limit = 8): array
    {
        return $this->db->table('asset_activities a')
            ->select('a.*, m.code AS motor_code, m.brand, m.model, u.name AS created_by_name')
            ->join('motors m', 'm.id = a.motor_id')
            ->join('users u', 'u.id = a.created_by', 'left')
            ->orderBy('a.activity_date', 'DESC')->orderBy('a.id', 'DESC')
            ->limit($limit)->get()->getResultArray();
    }
}
