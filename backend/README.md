# NEXA Backend

REST API JSON untuk NEXA, dibangun dengan **CodeIgniter 3.1.13** dan **PHP 7.3**, database SQLite (`writable/nexa.db`).

Kode aplikasi ada di `application/`. Folder `system/` dan file `index.php` milik CodeIgniter 3 tidak disimpan di repo; keduanya dipasang otomatis oleh `setup.bat` / `setup.sh` (atau saat container Docker pertama kali start).

Cara menjalankan lengkap ada di `README.md` di folder induk.

`composer.json` di sini sengaja minimal dan mengunci platform ke PHP 7.3.33, supaya `composer require` (misalnya `phpoffice/phpspreadsheet`, `dompdf/dompdf` di Tahap 5) memilih versi paket yang cocok dengan PHP 7.3.
