<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Migration NEXA. Jalankan lewat CLI:  php index.php tools migrate
| Kalau menambah file migration baru, naikkan migration_version ke timestamp file terbaru.
*/
$config['migration_enabled']     = TRUE;
$config['migration_type']        = 'timestamp';
$config['migration_table']       = 'migrations';
$config['migration_auto_latest'] = FALSE;
$config['migration_version']     = 20261020000001;
$config['migration_path']        = APPPATH . 'migrations/';
