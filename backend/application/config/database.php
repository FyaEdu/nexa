<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Database NEXA: SQLite (satu file). File dibuat otomatis saat migrate:
|   backend/writable/nexa.db
| Buka dengan DB Browser for SQLite untuk melihat isinya.
*/
$active_group  = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'dsn'          => '',
	'hostname'     => '',
	'username'     => '',
	'password'     => '',
	'database'     => FCPATH . 'writable/nexa.db',
	'dbdriver'     => 'sqlite3',
	'dbprefix'     => '',
	'pconnect'     => FALSE,
	'db_debug'     => (ENVIRONMENT !== 'production'),
	'cache_on'     => FALSE,
	'cachedir'     => '',
	'char_set'     => 'utf8',
	'dbcollat'     => 'utf8_general_ci',
	'swap_pre'     => '',
	'encrypt'      => FALSE,
	'compress'     => FALSE,
	'stricton'     => FALSE,
	'failover'     => array(),
	'save_queries' => TRUE
);
