<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('session.save_path', __DIR__);
require dirname(__DIR__, 3) . '/config/database.php';
$file = __DIR__ . '/fixture.json';
if (($argv[1] ?? '') === 'create') {
    if (is_file($file)) {
        $old = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = ? AND email = ?');
        $check->execute([$old['id'], $old['email']]);
        if ((int)$check->fetchColumn() !== 0) { throw new RuntimeException('Fixture lama perlu dibersihkan lebih dahulu.'); }
    }
    $email = 'evaluasi-user-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '@bacamudah.test';
    $pdo->prepare('INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)')->execute(['Pembaca Evaluasi', $email, password_hash('EvalBaca2026!', PASSWORD_DEFAULT), 'user']);
    $fixture = ['id' => (int)$pdo->lastInsertId(), 'email' => $email];
    file_put_contents($file, json_encode($fixture, JSON_PRETTY_PRINT));
    echo "Akun evaluasi sementara dibuat.\n";
} elseif (($argv[1] ?? '') === 'cleanup') {
    $fixture = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    if (!preg_match('/^evaluasi-user-\d{14}-[a-f0-9]{6}@bacamudah\.test$/D', $fixture['email'])) { throw new RuntimeException('Fixture tidak valid.'); }
    $st = $pdo->prepare('DELETE FROM users WHERE id = ? AND email = ? AND role = ?');
    $st->execute([$fixture['id'], $fixture['email'], 'user']);
    echo 'Akun evaluasi dibersihkan: ', $st->rowCount(), "\n";
    $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = ? AND email = ?');
    $st->execute([$fixture['id'], $fixture['email']]);
    if ((int)$st->fetchColumn() !== 0) { throw new RuntimeException('Fixture belum bersih.'); }
}
