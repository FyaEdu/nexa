<?php

namespace App\Controllers\Api;

use App\Models\ApiTokenModel;
use App\Models\UserModel;

class AuthController extends BaseApiController
{
    /** POST /api/auth/login  body: {username, password}  (username boleh diisi email) */
    public function login()
    {
        $in       = $this->input();
        $login    = trim((string) ($in['username'] ?? ''));
        $password = (string) ($in['password'] ?? '');

        if ($login === '' || $password === '') {
            return $this->failValidationErrors(['username' => 'Username/email dan password wajib diisi.']);
        }

        $user = (new UserModel())->findForLogin($login);
        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return $this->failUnauthorized('Username/email atau password salah.');
        }
        if ((int) $user['is_active'] !== 1) {
            return $this->failForbidden('Akun ini dinonaktifkan. Hubungi administrator.');
        }

        $token = (new ApiTokenModel())->issue((int) $user['id']);

        return $this->respond([
            'token' => $token,
            'user'  => UserModel::publicData($user),
        ]);
    }

    /** POST /api/auth/logout */
    public function logout()
    {
        $token = $this->bearerToken();
        if ($token) {
            (new ApiTokenModel())->revoke($token);
        }

        return $this->respond(['message' => 'Berhasil keluar.']);
    }

    /** GET /api/auth/me  -> data user + daftar permission miliknya */
    public function me()
    {
        if ($deny = $this->guard()) {
            return $deny;
        }

        $perms = [];
        foreach (config('Nexa')->permissions as $key => $roles) {
            if (in_array($this->user['role'], $roles, true)) {
                $perms[] = $key;
            }
        }

        return $this->respond([
            'user'        => UserModel::publicData($this->user),
            'permissions' => $perms,
        ]);
    }
}
