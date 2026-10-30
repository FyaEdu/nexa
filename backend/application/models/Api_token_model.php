<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Token login sederhana (Bearer). Token asli hanya dikirim sekali ke frontend;
 * di database hanya disimpan hash SHA-256-nya.
 */
class Api_token_model extends CI_Model
{
	public function issue($user_id)
	{
		$now = date('Y-m-d H:i:s');
		$this->db->where('expires_at <', $now)->delete('api_tokens');

		$hours = (int) $this->config->item('token_ttl_hours');
		$hours = $hours > 0 ? $hours : 12;
		$token = bin2hex(random_bytes(32));

		$this->db->insert('api_tokens', array(
			'user_id'    => (int) $user_id,
			'token_hash' => hash('sha256', $token),
			'expires_at' => date('Y-m-d H:i:s', time() + $hours * 3600),
			'created_at' => $now,
		));

		return $token;
	}

	public function revoke($token)
	{
		$this->db->where('token_hash', hash('sha256', $token))->delete('api_tokens');
	}

	/** User aktif pemilik token yang masih berlaku, atau NULL. */
	public function user_for_token($token)
	{
		$row = $this->db
			->select('u.*, r.code AS role, r.name AS role_name')
			->from('api_tokens t')
			->join('users u', 'u.id = t.user_id')
			->join('roles r', 'r.id = u.role_id')
			->where('t.token_hash', hash('sha256', $token))
			->where('t.expires_at >', date('Y-m-d H:i:s'))
			->where('u.is_active', 1)
			->get()->row_array();

		return $row ? $row : NULL;
	}
}
