<?php

namespace App\Controllers\Api;

/**
 * GET /api/meta : data referensi untuk form di frontend
 * (status lifecycle, role, kategori biaya, template checklist).
 */
class MetaController extends BaseApiController
{
    public function index()
    {
        if ($deny = $this->guard()) {
            return $deny;
        }
        $cfg = config('Nexa');

        return $this->respond([
            'statuses'        => $cfg->statuses,
            'roles'           => $this->db->table('roles')->orderBy('id')->get()->getResultArray(),
            'cost_categories' => $this->db->table('cost_categories')->orderBy('id')->get()->getResultArray(),
            'checklist'       => $cfg->checklist,
        ]);
    }
}
