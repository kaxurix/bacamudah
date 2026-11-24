<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/config/database.php';
$fixture = json_decode(file_get_contents(__DIR__ . '/runtime/improvements/result.json'), true);
$st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
$st->execute([$fixture['email']]);
$summary = ['akun_uji_tersisa' => (int)$st->fetchColumn()];
$st = $pdo->prepare('SELECT COUNT(*) FROM texts WHERE judul LIKE ?');
$st->execute([$fixture['label'] . '%']);
$summary['teks_uji_tersisa'] = (int)$st->fetchColumn();
$st = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE nama_kategori = ?');
$st->execute([$fixture['label']]);
$summary['kategori_uji_tersisa'] = (int)$st->fetchColumn();
$summary['teks_umum_sebelumnya'] = (int)$pdo->query("SELECT COUNT(*) FROM texts WHERE visibilitas = 'umum'")->fetchColumn();
$summary['thumbnail_uji_tersisa'] = !empty($fixture['upload']) && file_exists(dirname(__DIR__) . '/uploads/' . basename($fixture['upload']));
file_put_contents(__DIR__ . '/runtime/improvements/cleanup.json', json_encode($summary, JSON_PRETTY_PRINT));
echo json_encode($summary, JSON_PRETTY_PRINT), PHP_EOL;
if ($summary['akun_uji_tersisa'] || $summary['teks_uji_tersisa'] || $summary['kategori_uji_tersisa'] || $summary['thumbnail_uji_tersisa']) { exit(1); }
