<?php
/*
| Router untuk server bawaan PHP:  php -S localhost:8080 server.php
| Semua request diteruskan ke CodeIgniter (index.php), jadi URL API tidak
| perlu memakai "index.php" dan tidak butuh Apache/.htaccess.
| File di folder backend sengaja TIDAK dilayani langsung, supaya database
| (writable/nexa.db) dan kode tidak bisa diunduh lewat browser.
*/
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
chdir(__DIR__);
require __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
