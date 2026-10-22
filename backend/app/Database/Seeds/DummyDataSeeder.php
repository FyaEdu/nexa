<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Mengisi database dengan data dummy dari data/dummy.json.
 *
 * Isi dummy.json dibuat oleh tools/generate_dummy.py (jangan diedit manual).
 * Seeder ini mengosongkan tabel terlebih dulu, jadi aman dijalankan berulang.
 * Data dummy: 5 user (4 role), 7 motor yang mewakili semua status lifecycle,
 * 13 sparepart, biaya dari hasil wawancara (Lampiran B), 3 customer, 3 transaksi.
 */
class DummyDataSeeder extends Seeder
{
    public function run()
    {
        $file = APPPATH . 'Database/Seeds/data/dummy.json';
        $json = json_decode((string) file_get_contents($file), true);
        if (! is_array($json) || ! isset($json['order'], $json['tables'])) {
            throw new RuntimeException('File data dummy tidak valid: ' . $file);
        }

        $order = $json['order'];

        $this->db->transStart();

        // Kosongkan dari tabel anak ke induk
        $this->db->table('api_tokens')->emptyTable();
        foreach (array_reverse($order) as $table) {
            $this->db->table($table)->emptyTable();
        }

        foreach ($order as $table) {
            $rows = $json['tables'][$table] ?? [];
            if ($rows === []) {
                continue;
            }
            if ($table === 'users') {
                foreach ($rows as &$row) {
                    $row['password_hash'] = password_hash($row['password'], PASSWORD_DEFAULT);
                    unset($row['password']);
                }
                unset($row);
            }
            foreach (array_chunk($rows, 50) as $chunk) {
                $this->db->table($table)->insertBatch($chunk);
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new RuntimeException('Gagal mengisi data dummy.');
        }

        echo "Data dummy NEXA berhasil dimasukkan.\n";
        echo "Login: admin/admin123, pemilik/pemilik123, operasional/ops123, penjualan/sales123\n";
    }
}
