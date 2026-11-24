<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$exists = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'bacamudah'")->fetchColumn();
if ($exists > 0) {
    echo "Database bacamudah sudah berisi tabel; data dipertahankan.\n";
    exit;
}
$pdo->exec(file_get_contents(dirname(__DIR__) . '/database.sql'));
echo 'Database diimpor: ' . $pdo->query('SELECT COUNT(*) FROM bacamudah.texts')->fetchColumn() . " teks.\n";
