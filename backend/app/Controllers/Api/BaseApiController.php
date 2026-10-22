<?php

namespace App\Controllers\Api;

use App\Models\ApiTokenModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Induk semua controller API NEXA.
 *
 * Pola pemakaian di setiap method yang butuh login:
 *
 *     public function index()
 *     {
 *         if ($deny = $this->guard('motor.view')) {
 *             return $deny;               // 401 belum login / 403 tidak punya akses
 *         }
 *         $this->user;                    // user yang sedang login (id, name, role, ...)
 *         return $this->respond([...]);
 *     }
 *
 * Format respons:
 *   sukses -> JSON data apa adanya (respond / respondCreated)
 *   gagal  -> {"status":4xx,"error":4xx,"messages":{"field":"pesan"}} (bawaan ResponseTrait)
 */
abstract class BaseApiController extends Controller
{
    use ResponseTrait;

    protected BaseConnection $db;

    /** User yang sedang login, terisi setelah guard() berhasil. */
    protected ?array $user = null;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->db = db_connect();
    }

    /** Ambil body request (JSON atau form) sebagai array. */
    protected function input(): array
    {
        try {
            $json = $this->request->getJSON(true);
        } catch (Throwable) {
            $json = null;
        }
        if (is_array($json)) {
            return $json;
        }

        return $this->request->getPost() ?: [];
    }

    protected function bearerToken(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');

        return preg_match('/Bearer\s+(\S+)/i', $header, $m) ? $m[1] : null;
    }

    /**
     * Pastikan user sudah login dan (opsional) punya hak akses tertentu.
     *
     * @param string|null $permission kunci di Config\Nexa::$permissions, misalnya 'motor.edit'
     *
     * @return ResponseInterface|null null kalau lolos, response error kalau ditolak
     */
    protected function guard(?string $permission = null)
    {
        $token = $this->bearerToken();
        $user  = $token ? (new ApiTokenModel())->userForToken($token) : null;
        if (! $user) {
            return $this->failUnauthorized('Sesi berakhir atau belum login. Silakan login ulang.');
        }
        $this->user = $user;

        if ($permission !== null && ! $this->can($permission)) {
            return $this->failForbidden('Role Anda tidak punya akses ke fitur ini.');
        }

        return null;
    }

    protected function can(string $permission): bool
    {
        $roles = config('Nexa')->permissions[$permission] ?? [];

        return $this->user !== null && in_array($this->user['role'], $roles, true);
    }

    protected function userId(): ?int
    {
        return $this->user ? (int) $this->user['id'] : null;
    }
}
