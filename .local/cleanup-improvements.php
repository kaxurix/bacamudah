<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/config/database.php';
$files = array_slice($argv, 1) ?: ['result.json'];
foreach ($files as $file) {
    if (!in_array($file, ['baseline.json', 'result.json'], true)) { exit('Nama laporan tidak valid.'); }
    $report = json_decode(file_get_contents(__DIR__ . '/runtime/improvements/' . $file), true, 512, JSON_THROW_ON_ERROR);
    if (!preg_match('/^perbaikan-\d+@bacamudah\.test$/D', $report['email'])) { exit('Akun uji tidak valid.'); }
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$report['email']]);
    $st = $pdo->prepare("SELECT thumbnail FROM texts WHERE (judul = ? OR judul LIKE ?) AND visibilitas = 'umum'");
    $st->execute([$report['label'] . ' umum', $report['label'] . ' halaman %']);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $thumb) {
        if ($thumb && preg_match('/^[a-f0-9]{24}\.png$/D', $thumb)) { @unlink(dirname(__DIR__) . '/uploads/' . $thumb); }
    }
    $pdo->prepare('DELETE FROM texts WHERE judul = ? OR judul LIKE ?')->execute([$report['label'] . ' umum', $report['label'] . ' halaman %']);
    $pdo->prepare('DELETE FROM categories WHERE nama_kategori = ?')->execute([$report['label']]);
    echo $file, ': fixture dibersihkan.', PHP_EOL;
}
