<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
}

/**
 * Induk semua controller API NEXA (application/controllers/api/*).
 *
 * Pola pemakaian di setiap method yang butuh login:
 *
 *     public function index()
 *     {
 *         if ( ! $this->guard('motor.view')) {
 *             return;                     // respons 401/403 sudah dikirim
 *         }
 *         $this->user;                    // user yang sedang login (id, name, role, ...)
 *         $this->respond(array(...));
 *     }
 *
 * Format respons:
 *   sukses -> JSON data apa adanya:  $this->respond($data) / $this->respond($data, 201)
 *   gagal  -> {"status":4xx,"error":4xx,"messages":{"field":"pesan"}}
 *             $this->fail('pesan', 404)  atau  $this->fail('pesan', 422, array('field' => 'pesan'))
 */
class Api_Controller extends MY_Controller
{
	/** @var array|null User yang sedang login, terisi setelah guard() berhasil */
	protected $user = NULL;

	public function __construct()
	{
		parent::__construct();
		$this->load->model('api_token_model');

		// Pustaka composer (mis. PhpSpreadsheet & Dompdf di Tahap 5)
		if (is_file(FCPATH . 'vendor/autoload.php')) {
			require_once FCPATH . 'vendor/autoload.php';
		}
	}

	/** Body request (JSON atau form) sebagai array. */
	protected function input_data()
	{
		$json = json_decode($this->input->raw_input_stream, TRUE);
		if (is_array($json)) {
			return $json;
		}
		$post = $this->input->post();

		return is_array($post) ? $post : array();
	}

	protected function respond($data, $code = 200)
	{
		$this->output
			->set_status_header($code)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	protected function fail($message, $code = 400, $fields = NULL)
	{
		$this->respond(array(
			'status'   => $code,
			'error'    => $code,
			'messages' => $fields ? $fields : array('error' => $message),
		), $code);
	}

	protected function bearer_token()
	{
		$header = (string) $this->input->get_request_header('Authorization', TRUE);
		if ($header === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$header = $_SERVER['HTTP_AUTHORIZATION'];
		}

		return preg_match('/Bearer\s+(\S+)/i', $header, $m) ? $m[1] : NULL;
	}

	/**
	 * Pastikan user login dan (opsional) punya hak akses tertentu.
	 *
	 * @param string|null $permission kunci di config/nexa.php ($config['permissions']), mis. 'motor.edit'
	 * @return bool TRUE kalau lolos. Kalau FALSE, respons error sudah dikirim, cukup "return;".
	 */
	protected function guard($permission = NULL)
	{
		$token = $this->bearer_token();
		$user  = $token ? $this->api_token_model->user_for_token($token) : NULL;
		if ( ! $user) {
			$this->fail('Sesi berakhir atau belum login. Silakan login ulang.', 401);
			return FALSE;
		}
		$this->user = $user;

		if ($permission !== NULL && ! $this->can($permission)) {
			$this->fail('Role Anda tidak punya akses ke fitur ini.', 403);
			return FALSE;
		}

		return TRUE;
	}

	protected function can($permission)
	{
		$perms = $this->config->item('permissions');
		$roles = isset($perms[$permission]) ? $perms[$permission] : array();

		return $this->user !== NULL && in_array($this->user['role'], $roles, TRUE);
	}

	protected function user_id()
	{
		return $this->user ? (int) $this->user['id'] : NULL;
	}

	/**
	 * Tolak request kalau method HTTP-nya bukan $method ('post', 'put', 'delete').
	 * Perlu karena CodeIgniter 3 tetap bisa memanggil method controller langsung lewat URL.
	 */
	protected function only($method)
	{
		if ($this->input->method() !== $method) {
			$this->fail('Method HTTP tidak diizinkan untuk endpoint ini.', 405);
			return FALSE;
		}

		return TRUE;
	}

	/** Tanggal valid berformat YYYY-MM-DD */
	protected function valid_date($value)
	{
		$d = DateTime::createFromFormat('Y-m-d', (string) $value);

		return $d && $d->format('Y-m-d') === $value;
	}

	/** Bilangan bulat >= $min (menerima angka maupun teks angka) */
	protected function valid_int($value, $min = 0)
	{
		if (is_int($value)) {
			return $value >= $min;
		}
		if (is_float($value)) {
			return floor($value) == $value && $value >= $min;
		}

		return is_string($value) && preg_match('/^-?\d+$/', trim($value)) && (int) $value >= $min;
	}

	/** Ambil nilai string dari array input, sudah di-trim */
	protected function str($in, $key, $default = '')
	{
		return isset($in[$key]) && $in[$key] !== NULL ? trim((string) $in[$key]) : $default;
	}

	/** Respons 422 dengan pesan per field kalau $errors tidak kosong */
	protected function fail_if_errors($errors)
	{
		if ( ! empty($errors)) {
			$this->fail('', 422, $errors);
			return TRUE;
		}

		return FALSE;
	}

	protected function status_label($key)
	{
		$st = $this->config->item('statuses');

		return isset($st[$key]) ? $st[$key] : $key;
	}
}
