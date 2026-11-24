<?php
require __DIR__ . '/config/database.php';

if (sudah_login()) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $email    = trim(input_string($_POST, 'email'));
    $password = input_string($_POST, 'password');
    $st = $pdo->prepare('SELECT id, nama, email, password, role FROM users WHERE email = ?');
    $st->execute([$email]);
    $user = $st->fetch();
    $emailAkun = $user ? $user['email'] : mb_strtolower($email);

    // Batas per akun/IP tetap berlaku saat browser membuat sesi baru.
    $kunci = hash('sha256', $emailAkun . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
    // Reservasi atomik sebelum memeriksa password, termasuk antarpekerja PHP.
    $pdo->prepare('INSERT INTO login_attempts (attempt_key, attempts, expires_at) VALUES (?, 1, DATE_ADD(NOW(), INTERVAL 5 MINUTE)) ON DUPLICATE KEY UPDATE attempts = IF(expires_at <= NOW(), 1, LEAST(6, attempts + 1)), expires_at = IF(expires_at <= NOW(), VALUES(expires_at), expires_at)')->execute([$kunci]);
    $pdo->exec('DELETE FROM login_attempts WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    $cekPercobaan = $pdo->prepare('SELECT attempts, TIMESTAMPDIFF(SECOND, NOW(), expires_at) AS sisa_detik FROM login_attempts WHERE attempt_key = ? AND expires_at > NOW()');
    $cekPercobaan->execute([$kunci]);
    $batas = $cekPercobaan->fetch();
    if ($batas && (int)$batas['attempts'] > 5) {
        http_response_code(429);
        header('Retry-After: ' . max(1, (int)$batas['sisa_detik']));
        $error = 'Terlalu banyak percobaan masuk. Tunggu maksimal 5 menit, lalu coba lagi.';
    } else {

        $isDemo = in_array(mb_strtolower($emailAkun), ['admin@bacamudah.test', 'user@bacamudah.test'], true);
        if ($user && password_verify($password, $user['password']) && !(mode_produksi() && $isDemo)) {
            $pdo->prepare('DELETE FROM login_attempts WHERE attempt_key = ?')->execute([$kunci]);
            session_regenerate_id(true);
            unset($_SESSION['csrf']);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = $user['role'];
            header('Location: index.php');
            exit;
        }
        $error = 'Email atau password salah.';
    }
}

$judul = 'Login';
$halaman = 'login';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-layout login-layout">
    <section class="login-story" aria-labelledby="judul-ruang-baca">
        <div class="login-story-content">
            <div class="login-story-heading">
                <h2 id="judul-ruang-baca">Membaca nyaman,<br><span>dengan caramu.</span></h2>
                <p>Atur ukuran huruf, jarak, dan warna sesuai kebutuhanmu.</p>
            </div>
            <figure class="login-book">
                <span class="login-type-detail" aria-hidden="true">Aa</span>
                <img src="assets/images/login-book.svg?v=<?= filemtime(__DIR__ . '/assets/images/login-book.svg') ?>" alt="" width="360" height="220">
                <span class="login-sound-detail" aria-hidden="true"><?= ikon('suara') ?></span>
                <figcaption>Sesuaikan bacaan dengan kebutuhanmu.</figcaption>
            </figure>
            <ul class="login-features" aria-label="Fitur kenyamanan membaca">
                <li><?= ikon('atur') ?><div><strong>Atur tampilan</strong><span>Sesuaikan ukuran huruf, jarak, dan warna.</span></div></li>
                <li><?= ikon('fokus') ?><div><strong>Fokus satu paragraf</strong><span>Ikuti bacaan satu paragraf dalam satu waktu.</span></div></li>
                <li><?= ikon('suara') ?><div><strong>Dengarkan teks</strong><span>Gunakan baca nyaring untuk mendengarkan bacaan.</span></div></li>
            </ul>
        </div>
    </section>
    <section class="auth-form-panel" aria-labelledby="judul-login">
        <div class="auth-form-wrap">
            <div class="login-form-heading">
                <h1 id="judul-login">Selamat datang<br><span>kembali.</span></h1>
                <p class="form-intro">Masuk untuk melanjutkan bacaanmu.</p>
            </div>
            <div class="login-form-content">
            <?php if ($flash): ?><div class="pesan pesan-<?= e($flash['tipe']) ?>" role="status"><?= ikon('centang') ?><span><?= e($flash['pesan']) ?></span></div><?php endif; ?>
            <?php if ($error): ?><div class="pesan pesan-galat" role="alert"><?= ikon('bantuan') ?><span><?= e($error) ?></span></div><?php endif; ?>
            <form method="post" class="auth-form" novalidate>
                <?= csrf_field() ?>
                <label for="email">Alamat email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="nama@email.com" required autocomplete="email"<?= $error ? ' aria-invalid="true"' : '' ?>>
                <label for="password">Password</label>
                <div class="password-field"><input type="password" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password"<?= $error ? ' aria-invalid="true"' : '' ?>><button type="button" class="tombol-ikon password-toggle" data-password-toggle="password" aria-label="Tampilkan password" aria-pressed="false"><?= ikon('mata') ?></button></div>
                <button type="submit" class="tombol tombol-penuh">Masuk</button>
            </form>
            <p class="auth-switch">Baru di sini? <a href="register.php">Buat akun gratis</a></p>
            </div>
            <p class="login-footer"><?= ikon('daun') ?> Setiap pembaca berhak merasa nyaman.</p>
        </div>
    </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
