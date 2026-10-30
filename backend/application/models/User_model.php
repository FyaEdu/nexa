<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
	/** Cari user berdasarkan username atau email (tidak peka huruf besar/kecil), sekalian kode role-nya. */
	public function find_for_login($login)
	{
		$key = strtolower(trim($login));

		return $this->db
			->select('users.*, roles.code AS role, roles.name AS role_name')
			->from('users')
			->join('roles', 'roles.id = users.role_id')
			->where('(LOWER(users.username) = ' . $this->db->escape($key)
				. ' OR LOWER(users.email) = ' . $this->db->escape($key) . ')', NULL, FALSE)
			->order_by('users.id')
			->limit(1)
			->get()->row_array();
	}

	/** Data user yang aman dikirim ke frontend (tanpa password_hash). */
	public static function public_data(array $u)
	{
		return array(
			'id'        => (int) $u['id'],
			'name'      => $u['name'],
			'username'  => $u['username'],
			'email'     => isset($u['email']) ? $u['email'] : NULL,
			'role'      => $u['role'],
			'role_name' => $u['role_name'],
			'is_active' => (int) $u['is_active'] === 1,
		);
	}
}
