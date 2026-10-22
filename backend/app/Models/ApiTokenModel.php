<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Token login sederhana (Bearer). Token asli hanya dikirim sekali ke frontend;
 * di database hanya disimpan hash SHA-256-nya.
 */
class ApiTokenModel extends Model
{
    protected $table         = 'api_tokens';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = ['user_id', 'token_hash', 'expires_at'];

    public function issue(int $userId): string
    {
        $this->where('expires_at <', date('Y-m-d H:i:s'))->delete();

        $hours = (int) (config('Nexa')->tokenTtlHours ?: 12);
        $token = bin2hex(random_bytes(32));
        $this->insert([
            'user_id'    => $userId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + $hours * 3600),
        ]);

        return $token;
    }

    public function revoke(string $token): void
    {
        $this->where('token_hash', hash('sha256', $token))->delete();
    }

    /** Kembalikan user aktif pemilik token yang masih berlaku, atau null. */
    public function userForToken(string $token): ?array
    {
        $row = $this->db->table('api_tokens t')
            ->select('u.*, r.code AS role, r.name AS role_name')
            ->join('users u', 'u.id = t.user_id')
            ->join('roles r', 'r.id = u.role_id')
            ->where('t.token_hash', hash('sha256', $token))
            ->where('t.expires_at >', date('Y-m-d H:i:s'))
            ->where('u.is_active', 1)
            ->get()->getRowArray();

        return $row ?: null;
    }
}
