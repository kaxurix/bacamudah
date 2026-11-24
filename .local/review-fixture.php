<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/config/database.php';
$report = json_decode(file_get_contents(__DIR__ . '/runtime/improvements/result.json'), true);
$email = $report['email'];
if (!preg_match('/^perbaikan-\d+@bacamudah\.test$/D', $email)) { exit('Fixture tidak valid'); }
if (($argv[1] ?? '') === '--legacy' || ($argv[1] ?? '') === '--restore') {
    $password = $argv[1] === '--legacy' ? str_repeat('a', 80) . 'z' : 'BacaNyaman123';
    $pdo->prepare('UPDATE users SET password = ? WHERE email = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $email]);
}
foreach ([$email, str_replace('perbaikan', 'pérbaikan', $email)] as $e) {
    $pdo->prepare('DELETE FROM login_attempts WHERE attempt_key = ?')->execute([hash('sha256', $e . '|127.0.0.1')]);
}
