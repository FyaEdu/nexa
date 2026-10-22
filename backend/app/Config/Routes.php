<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Routing NEXA. Semua endpoint API berada di bawah /api dan mengembalikan JSON.
 * Hak akses diperiksa di masing-masing controller lewat $this->guard().
 *
 * @var RouteCollection $routes
 */
$routes->get('/', static fn () => service('response')->setJSON([
    'app'     => 'NEXA API',
    'status'  => 'berjalan',
    'catatan' => 'Buka aplikasi di http://localhost:5173 (frontend Vue).',
]));

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    // ---- Tahap 1: fondasi (SUDAH) ----
    $routes->post('auth/login', 'AuthController::login');
    $routes->post('auth/logout', 'AuthController::logout');
    $routes->get('auth/me', 'AuthController::me');
    $routes->get('meta', 'MetaController::index');
    $routes->get('dashboard', 'DashboardController::index');

    // ---- Tahap 2: kendaraan, dokumen, Asset Timeline (BELUM) ----
    // $routes->get('motors', 'MotorController::index');
    // $routes->post('motors', 'MotorController::create');
    // $routes->get('motors/(:num)', 'MotorController::show/$1');
    // $routes->put('motors/(:num)', 'MotorController::update/$1');
    // $routes->post('motors/(:num)/status', 'MotorController::changeStatus/$1');
    // $routes->post('motors/(:num)/documents', 'DocumentController::create/$1');
    // $routes->put('documents/(:num)', 'DocumentController::update/$1');
    // $routes->delete('documents/(:num)', 'DocumentController::delete/$1');

    // ---- Tahap 3: inspeksi, Health Score, biaya (BELUM) ----
    // $routes->get('inspections', 'InspectionController::index');
    // $routes->post('inspections', 'InspectionController::create');
    // $routes->get('costs', 'CostController::index');
    // $routes->post('costs', 'CostController::create');
    // $routes->delete('costs/(:num)', 'CostController::delete/$1');

    // ---- Tahap 4: inventaris & sparepart (BELUM) ----
    // $routes->get('inventory', 'InventoryController::index');
    // $routes->post('inventory', 'InventoryController::create');
    // $routes->put('inventory/(:num)', 'InventoryController::update/$1');
    // $routes->get('inventory/transactions', 'InventoryController::transactions');
    // $routes->post('inventory/in', 'InventoryController::stockIn');
    // $routes->post('inventory/out', 'InventoryController::stockOut');

    // ---- Tahap 5: penjualan, laporan, pengguna, notifikasi (BELUM) ----
    // $routes->get('customers', 'CustomerController::index');
    // $routes->post('customers', 'CustomerController::create');
    // $routes->get('sales', 'SaleController::index');
    // $routes->post('sales', 'SaleController::create');
    // $routes->post('sales/(:num)/complete', 'SaleController::complete/$1');
    // $routes->post('sales/(:num)/paid', 'SaleController::markPaid/$1');
    // $routes->post('sales/(:num)/cancel', 'SaleController::cancel/$1');
    // $routes->get('reports/(:segment)', 'ReportController::show/$1');
    // $routes->get('users', 'UserController::index');
    // $routes->post('users', 'UserController::create');
    // $routes->put('users/(:num)', 'UserController::update/$1');
    // $routes->post('users/(:num)/toggle', 'UserController::toggle/$1');
    // $routes->get('notifications', 'NotificationController::index');
});
