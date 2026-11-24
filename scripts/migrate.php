<?php
// Hanya menambah tabel pendukung; tidak menghapus atau mengubah data bacaan.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/config/database.php';
$pdo->exec(file_get_contents(dirname(__DIR__) . '/migrations/001-login-attempts.sql'));
echo "Migrasi selesai. Data yang sudah ada dipertahankan.\n";
