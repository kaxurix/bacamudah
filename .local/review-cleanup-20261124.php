<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$report = json_decode(file_get_contents(__DIR__ . '/runtime/review-20261124/results.json'), true, 512, JSON_THROW_ON_ERROR);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=bacamudah;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if (in_array('--cleanup', $argv, true)) {
    foreach ($report['fixtures']['emails'] as $email) {
        if (!preg_match('/^review-\d+-[12]@bacamudah\.test$/D', $email)) { throw new RuntimeException('Email fixture tidak valid.'); }
        $st = $pdo->prepare('DELETE FROM users WHERE email = ? AND role = ?');
        $st->execute([$email, 'user']);
        echo 'Akun uji dibersihkan: ', $st->rowCount(), PHP_EOL;
    }
    $st = $pdo->prepare('SELECT COUNT(*) FROM texts WHERE judul LIKE ?');
    $st->execute([$report['prefix'] . '%']);
    echo 'Sisa teks fixture: ', $st->fetchColumn(), PHP_EOL;
    $st = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE nama_kategori LIKE ?');
    $st->execute([$report['prefix'] . '%']);
    echo 'Sisa kategori fixture: ', $st->fetchColumn(), PHP_EOL;
} else {
    foreach (['session.use_strict_mode', 'session.cookie_httponly', 'session.cookie_secure', 'session.cookie_samesite', 'display_errors', 'upload_max_filesize', 'post_max_size'] as $key) {
        echo $key, '=', ini_get($key), PHP_EOL;
    }
    echo 'Teks umum: ', $pdo->query("SELECT COUNT(*) FROM texts WHERE visibilitas = 'umum'")->fetchColumn(), PHP_EOL;
}
