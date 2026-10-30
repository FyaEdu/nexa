<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends Api_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('user_model');
	}

	/** POST /api/auth/login  body: {username, password}  (username boleh diisi email) */
	public function login()
	{
		$in       = $this->input_data();
		$login    = trim(isset($in['username']) ? (string) $in['username'] : '');
		$password = isset($in['password']) ? (string) $in['password'] : '';

		if ($login === '' || $password === '') {
			return $this->fail('', 400, array('username' => 'Username/email dan password wajib diisi.'));
		}

		$user = $this->user_model->find_for_login($login);
		if ( ! $user || ! password_verify($password, $user['password_hash'])) {
			return $this->fail('Username/email atau password salah.', 401);
		}
		if ((int) $user['is_active'] !== 1) {
			return $this->fail('Akun ini dinonaktifkan. Hubungi administrator.', 403);
		}

		$this->respond(array(
			'token' => $this->api_token_model->issue((int) $user['id']),
			'user'  => User_model::public_data($user),
		));
	}

	/** POST /api/auth/logout */
	public function logout()
	{
		$token = $this->bearer_token();
		if ($token) {
			$this->api_token_model->revoke($token);
		}
		$this->respond(array('message' => 'Berhasil keluar.'));
	}

	/** GET /api/auth/me  -> data user + daftar permission miliknya */
	public function me()
	{
		if ( ! $this->guard()) {
			return;
		}

		$perms = array();
		foreach ($this->config->item('permissions') as $key => $roles) {
			if (in_array($this->user['role'], $roles, TRUE)) {
				$perms[] = $key;
			}
		}

		$this->respond(array(
			'user'        => User_model::public_data($this->user),
			'permissions' => $perms,
		));
	}
}
