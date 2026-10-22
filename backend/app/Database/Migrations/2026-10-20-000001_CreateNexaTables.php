<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Membuat seluruh tabel NEXA sesuai ERD proposal (Bab 3.3 c).
 * Skema yang sama dalam bentuk SQL ada di database/schema.sql.
 *
 * Jalankan:  php spark migrate
 * Ulang dari nol + isi data dummy:  php spark migrate:refresh && php spark db:seed DatabaseSeeder
 */
class CreateNexaTables extends Migration
{
    private function id(): array
    {
        return ['id' => ['type' => 'INTEGER', 'auto_increment' => true]];
    }

    private function fk(bool $null = false): array
    {
        return ['type' => 'INTEGER', 'null' => $null];
    }

    private function str(int $len, bool $null = true): array
    {
        return ['type' => 'VARCHAR', 'constraint' => $len, 'null' => $null];
    }

    private function money(): array
    {
        return ['type' => 'BIGINT', 'default' => 0];
    }

    private function timestamps(bool $updated = true): array
    {
        $f = ['created_at' => ['type' => 'DATETIME', 'null' => true]];
        if ($updated) {
            $f['updated_at'] = ['type' => 'DATETIME', 'null' => true];
        }

        return $f;
    }

    private function create(string $table, array $fields, array $foreignKeys = []): void
    {
        $this->forge->addField($this->id() + $fields);
        $this->forge->addKey('id', true);
        foreach ($foreignKeys as [$field, $refTable, $onDelete]) {
            $this->forge->addForeignKey($field, $refTable, 'id', '', $onDelete);
        }
        $this->forge->createTable($table, true);
    }

    public function up()
    {
        $this->create('roles', [
            'code' => $this->str(20, false) + ['unique' => true],
            'name' => $this->str(50, false),
        ]);

        $this->create('users', [
            'role_id'       => $this->fk(),
            'name'          => $this->str(100, false),
            'username'      => $this->str(50, false) + ['unique' => true],
            'email'         => $this->str(100),
            'password_hash' => $this->str(255, false),
            'is_active'     => ['type' => 'INTEGER', 'default' => 1],
        ] + $this->timestamps(), [['role_id', 'roles', '']]);

        $this->create('api_tokens', [
            'user_id'    => $this->fk(),
            'token_hash' => $this->str(64, false) + ['unique' => true],
            'expires_at' => ['type' => 'DATETIME'],
        ] + $this->timestamps(false), [['user_id', 'users', 'CASCADE']]);

        $this->create('cost_categories', [
            'name'        => $this->str(50, false) + ['unique' => true],
            'description' => $this->str(200),
        ]);

        $this->create('motors', [
            'code'            => $this->str(20, false) + ['unique' => true],
            'brand'           => $this->str(50, false),
            'model'           => $this->str(80, false),
            'year'            => ['type' => 'INTEGER', 'null' => true],
            'plate_number'    => $this->str(20),
            'color'           => $this->str(40),
            'frame_number'    => $this->str(40),
            'engine_number'   => $this->str(40),
            'purchase_date'   => ['type' => 'DATE', 'null' => true],
            'purchase_price'  => $this->money(),
            'purchase_source' => $this->str(120),
            'target_price'    => $this->money(),
            'condition_note'  => ['type' => 'TEXT', 'null' => true],
            'status'          => $this->str(20, false) + ['default' => 'masuk'],
            'health_score'    => ['type' => 'INTEGER', 'null' => true],
        ] + $this->timestamps());

        $this->create('inspections', [
            'motor_id'        => $this->fk(),
            'inspection_date' => ['type' => 'DATE'],
            'inspector'       => $this->str(100),
            'notes'           => ['type' => 'TEXT', 'null' => true],
            'score'           => ['type' => 'INTEGER'],
            'created_by'      => $this->fk(true),
        ] + $this->timestamps(false), [['motor_id', 'motors', 'CASCADE'], ['created_by', 'users', '']]);

        $this->create('inspection_items', [
            'inspection_id' => $this->fk(),
            'group_key'     => $this->str(20, false),
            'item_key'      => $this->str(10, false),
            'item_label'    => $this->str(80, false),
            'result'        => $this->str(10, false),
        ], [['inspection_id', 'inspections', 'CASCADE']]);

        $this->create('motor_costs', [
            'motor_id'    => $this->fk(),
            'category_id' => $this->fk(),
            'item'        => $this->str(120, false),
            'amount'      => $this->money(),
            'cost_date'   => ['type' => 'DATE'],
            'note'        => $this->str(255),
            'created_by'  => $this->fk(true),
        ] + $this->timestamps(false), [['motor_id', 'motors', 'CASCADE'], ['category_id', 'cost_categories', ''], ['created_by', 'users', '']]);

        $this->create('inventory_items', [
            'code'       => $this->str(20, false) + ['unique' => true],
            'name'       => $this->str(100, false),
            'unit'       => $this->str(20, false),
            'unit_price' => $this->money(),
            'stock'      => ['type' => 'INTEGER', 'default' => 0],
            'min_stock'  => ['type' => 'INTEGER', 'default' => 0],
            'location'   => $this->str(50),
        ] + $this->timestamps());

        $this->create('inventory_transactions', [
            'item_id'    => $this->fk(),
            'type'       => $this->str(5, false),
            'qty'        => ['type' => 'INTEGER'],
            'unit_price' => $this->money(),
            'trx_date'   => ['type' => 'DATE'],
            'supplier'   => $this->str(100),
            'motor_id'   => $this->fk(true),
            'note'       => $this->str(255),
            'created_by' => $this->fk(true),
        ] + $this->timestamps(false), [['item_id', 'inventory_items', ''], ['motor_id', 'motors', ''], ['created_by', 'users', '']]);

        $this->create('sparepart_usage', [
            'motor_id'       => $this->fk(),
            'item_id'        => $this->fk(),
            'transaction_id' => $this->fk(true),
            'qty'            => ['type' => 'INTEGER'],
            'unit_price'     => $this->money(),
            'total'          => $this->money(),
            'usage_date'     => ['type' => 'DATE'],
        ] + $this->timestamps(false), [['motor_id', 'motors', 'CASCADE'], ['item_id', 'inventory_items', ''], ['transaction_id', 'inventory_transactions', '']]);

        $this->create('vehicle_documents', [
            'motor_id'    => $this->fk(),
            'doc_type'    => $this->str(40, false),
            'doc_number'  => $this->str(60),
            'status'      => $this->str(20, false),
            'expiry_date' => ['type' => 'DATE', 'null' => true],
            'note'        => $this->str(255),
        ] + $this->timestamps(), [['motor_id', 'motors', 'CASCADE']]);

        $this->create('customers', [
            'name'    => $this->str(100, false),
            'phone'   => $this->str(30),
            'address' => $this->str(200),
        ] + $this->timestamps(false));

        $this->create('sales', [
            'motor_id'       => $this->fk(),
            'customer_id'    => $this->fk(),
            'booking_date'   => ['type' => 'DATE', 'null' => true],
            'sale_date'      => ['type' => 'DATE', 'null' => true],
            'price'          => $this->money(),
            'dp'             => $this->money(),
            'payment_status' => $this->str(20, false),
            'created_by'     => $this->fk(true),
        ] + $this->timestamps(), [['motor_id', 'motors', ''], ['customer_id', 'customers', ''], ['created_by', 'users', '']]);

        $this->create('asset_activities', [
            'motor_id'      => $this->fk(),
            'activity_date' => ['type' => 'DATE'],
            'type'          => $this->str(20, false),
            'status'        => $this->str(20),
            'title'         => $this->str(200, false),
            'detail'        => ['type' => 'TEXT', 'null' => true],
            'created_by'    => $this->fk(true),
        ] + $this->timestamps(false), [['motor_id', 'motors', 'CASCADE'], ['created_by', 'users', '']]);
    }

    public function down()
    {
        foreach ([
            'asset_activities', 'sales', 'customers', 'vehicle_documents', 'sparepart_usage',
            'inventory_transactions', 'inventory_items', 'motor_costs', 'inspection_items',
            'inspections', 'motors', 'cost_categories', 'api_tokens', 'users', 'roles',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
