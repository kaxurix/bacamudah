<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$pdo = new PDO('mysql:host=127.0.0.1;dbname=bacamudah;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$emails = ['uji-ui-1795440138340@bacamudah.test', 'uji-ui-1795440249384@bacamudah.test'];
$delete = $pdo->prepare("DELETE FROM users WHERE email = ? AND nama = 'Pembaca Uji' AND role = 'user'");
foreach ($emails as $email) { $delete->execute([$email]); echo $email . ': ' . $delete->rowCount() . " akun uji dibersihkan.\n"; }
