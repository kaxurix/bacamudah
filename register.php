<?php
require __DIR__ . '/config/database.php';

if (sudah_login()) {
    header('Location: index.php');
    exit;
}

$error = [];
$nama = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $nama     = trim(input_string($_POST, 'nama'));
    $email    = trim(input_string($_POST, 'email'));
    $password = input_string($_POST, 'password');
    $ulang    = input_string($_POST, 'password2');

    if ($nama === '' || mb_strlen($nama) > 100) {
        $error[] = 'Nama wajib diisi (maksimal 100 karakter).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $error[] = 'Format email tidak valid.';
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        $error[] = 'Password minimal 8 karakter dan tidak boleh terlalu panjang.';
    }
    if ($password !== $ulang) {
        $error[] = 'Pengulangan password tidak sama.';
    }

    if (!$error) {
        $cek = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $cek->execute([$email]);
        if ($cek->fetch()) {
            $error[] = 'Email sudah terdaftar. Silakan login.';
        }
    }

    if (!$error) {
        // Halaman daftar selalu membuat akun berperan "user".
        $ins = $pdo->prepare('INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, \'user\')');
        $ins->execute([$nama, $email, password_hash($password, PASSWORD_DEFAULT)]);
        set_flash('sukses', 'Akun berhasil dibuat. Silakan login.');
        header('Location: login.php');
        exit;
    }
}

$judul = 'Daftar';
$halaman = 'register';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-layout">
    <section class="auth-form-panel" aria-labelledby="judul-daftar">
        <div class="auth-form-wrap">
            <a class="back-link" href="login.php"><?= ikon('kiri') ?> Kembali ke login</a>
            <h1 id="judul-daftar">Buat akun</h1>
            <p class="form-intro">Buat akun untuk menyimpan teks dan pengaturan baca yang sesuai denganmu.</p>
            <?php if ($error): ?><div class="pesan pesan-galat" role="alert"><ul><?php foreach ($error as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" class="auth-form" novalidate>
                <?= csrf_field() ?>
                <label for="nama">Nama</label><input type="text" id="nama" name="nama" maxlength="100" value="<?= e($nama) ?>" placeholder="Kami boleh memanggilmu siapa?" required autocomplete="name">
                <label for="email">Alamat email</label><input type="email" id="email" name="email" maxlength="100" value="<?= e($email) ?>" placeholder="nama@email.com" required autocomplete="email">
                <label for="password">Password <span class="label-optional">minimal 8 karakter</span></label>
                <div class="password-field"><input type="password" id="password" name="password" minlength="8" placeholder="Buat password yang aman" required autocomplete="new-password"><button type="button" class="tombol-ikon password-toggle" data-password-toggle="password" aria-label="Tampilkan password" aria-pressed="false"><?= ikon('mata') ?></button></div>
                <label for="password2">Ulangi password</label><div class="password-field"><input type="password" id="password2" name="password2" placeholder="Masukkan sekali lagi" required autocomplete="new-password"><button type="button" class="tombol-ikon password-toggle" data-password-toggle="password2" aria-label="Tampilkan ulangi password" aria-pressed="false"><?= ikon('mata') ?></button></div>
                <button type="submit" class="tombol tombol-penuh">Buat akun</button>
            </form>
            <p class="auth-switch">Sudah punya akun? <a href="login.php">Masuk di sini <?= ikon('kanan') ?></a></p>
            <p class="auth-privacy"><?= ikon('kunci') ?> Pengaturan tersimpan. Bacaan pribadi tetap pribadi.</p>
        </div>
    </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
