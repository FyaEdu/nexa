<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['role_id', 'name', 'username', 'email', 'password_hash', 'is_active'];

    /** Cari user berdasarkan username atau email, sekalian kode role-nya. */
    public function findForLogin(string $login): ?array
    {
        $key = strtolower(trim($login));

        return $this->select('users.*, roles.code AS role, roles.name AS role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('(LOWER(users.username) = ' . $this->db->escape($key)
                . ' OR LOWER(users.email) = ' . $this->db->escape($key) . ')', null, false)
            ->orderBy('users.id')
            ->first();
    }

    /** Data user yang aman dikirim ke frontend (tanpa password_hash). */
    public static function publicData(array $u): array
    {
        return [
            'id'        => (int) $u['id'],
            'name'      => $u['name'],
            'username'  => $u['username'],
            'email'     => $u['email'] ?? null,
            'role'      => $u['role'],
            'role_name' => $u['role_name'],
            'is_active' => (int) $u['is_active'] === 1,
        ];
    }
}
