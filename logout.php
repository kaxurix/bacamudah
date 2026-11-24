<?php
require __DIR__ . '/config/database.php';

// Keluar hanya lewat tombol (POST) dengan token CSRF yang benar.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
header('Location: login.php');
exit;
