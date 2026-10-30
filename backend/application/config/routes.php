<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Routing NEXA (CodeIgniter 3). Semua endpoint API di bawah /api dan mengembalikan JSON.
| Format: $route['url']['method_http'] = 'folder/controller/method';
| Hak akses diperiksa di controller lewat $this->guard().
*/
$route['default_controller']   = 'home';
$route['404_override']         = 'home/not_found';
$route['translate_uri_dashes'] = FALSE;

// ---- Tahap 1: fondasi (SUDAH) ----
$route['api/auth/login']['post']  = 'api/auth/login';
$route['api/auth/logout']['post'] = 'api/auth/logout';
$route['api/auth/me']['get']      = 'api/auth/me';
$route['api/meta']['get']         = 'api/meta/index';
$route['api/dashboard']['get']    = 'api/dashboard/index';

// ---- Tahap 2: kendaraan, dokumen, Asset Timeline (SUDAH) ----
$route['api/motors']['get']                  = 'api/motors/index';
$route['api/motors']['post']                 = 'api/motors/create';
$route['api/motors/(:num)']['get']           = 'api/motors/show/$1';
$route['api/motors/(:num)']['put']           = 'api/motors/update/$1';
$route['api/motors/(:num)/status']['post']   = 'api/motors/change_status/$1';
$route['api/motors/(:num)/documents']['post'] = 'api/documents/create/$1';
$route['api/documents/(:num)']['put']        = 'api/documents/update/$1';
$route['api/documents/(:num)']['delete']     = 'api/documents/delete/$1';

// ---- Tahap 3: inspeksi, Health Score, biaya (SUDAH) ----
$route['api/inspections']['get']    = 'api/inspections/index';
$route['api/inspections']['post']   = 'api/inspections/create';
$route['api/costs']['get']          = 'api/costs/index';
$route['api/costs']['post']         = 'api/costs/create';
$route['api/costs/(:num)']['delete'] = 'api/costs/delete/$1';

// ---- Tahap 4: inventaris & sparepart (SUDAH) ----
$route['api/inventory']['get']              = 'api/inventory/index';
$route['api/inventory']['post']             = 'api/inventory/create';
$route['api/inventory/(:num)']['put']       = 'api/inventory/update/$1';
$route['api/inventory/transactions']['get'] = 'api/inventory/transactions';
$route['api/inventory/in']['post']          = 'api/inventory/stock_in';
$route['api/inventory/out']['post']         = 'api/inventory/stock_out';

// ---- Tahap 5: penjualan, laporan, pengguna, notifikasi (BELUM) ----
// $route['api/customers']['get']             = 'api/customers/index';
// $route['api/customers']['post']            = 'api/customers/create';
// $route['api/sales']['get']                 = 'api/sales/index';
// $route['api/sales']['post']                = 'api/sales/create';
// $route['api/sales/(:num)/complete']['post'] = 'api/sales/complete/$1';
// $route['api/sales/(:num)/paid']['post']    = 'api/sales/mark_paid/$1';
// $route['api/sales/(:num)/cancel']['post']  = 'api/sales/cancel/$1';
// $route['api/reports/(:any)']['get']        = 'api/reports/show/$1';
// $route['api/users']['get']                 = 'api/users/index';
// $route['api/users']['post']                = 'api/users/create';
// $route['api/users/(:num)']['put']          = 'api/users/update/$1';
// $route['api/users/(:num)/toggle']['post']  = 'api/users/toggle/$1';
// $route['api/notifications']['get']         = 'api/notifications/index';
