<?php
require_once __DIR__ . '/ui.php';
$judul = $judul ?? 'Baca Mudah';
$base = $base ?? '';
$bodyAttr = $bodyAttr ?? 'data-tema="normal"';
$halaman = $halaman ?? pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_FILENAME);
$authPage = in_array($halaman, ['login', 'register'], true);
$modeBaca = $halaman === 'read';
$flash = ambil_flash();
$namaPengguna = $_SESSION['nama'] ?? '';
$namaDepan = explode(' ', trim($namaPengguna))[0];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$menuUtama = [['index', 'rak', 'Perpustakaan', 'index.php']];
if ($isAdmin) {
    $menuUtama[] = ['texts', 'buku', 'Kelola Teks', 'admin/texts.php'];
    $menuUtama[] = ['categories', 'kategori', 'Kategori', 'admin/categories.php'];
} else {
    $menuUtama[] = ['teks-saya', 'pena', 'Teks Saya', 'teks-saya.php'];
    $menuUtama[] = ['bookmarks', 'bookmark', 'Bookmark', 'bookmarks.php'];
}
$bookmarkCount = 0;
if (sudah_login() && !$isAdmin) {
    $b = $pdo->prepare('SELECT COUNT(*) FROM bookmarks WHERE user_id = ?');
    $b->execute([(int)$_SESSION['user_id']]);
    $bookmarkCount = (int)$b->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#244e3e">
    <meta name="description" content="Ruang baca yang mengikuti caramu. Atur tampilan, fokus satu paragraf, dan dengarkan bacaan dengan Baca Mudah.">
    <title><?= e($judul) ?> — Baca Mudah</title>
    <link rel="icon" href="<?= e($base) ?>assets/images/favicon.svg" type="image/svg+xml">
    <link rel="preload" href="<?= e($base) ?>assets/fonts/manrope-variable.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="<?= e($base) ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <script src="<?= e($base) ?>assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>" defer></script>
</head>
<body class="<?= $authPage ? 'auth-page' : 'app-page' ?> page-<?= e($halaman) ?><?= $modeBaca ? ' reader-page' : '' ?>" <?= $bodyAttr ?>>
<a class="lewati" href="#konten">Lewati ke konten</a>
<?php if ($authPage): ?>
<div class="auth-shell">
    <header class="auth-header">
        <a class="logo" href="login.php" aria-label="Baca Mudah, halaman awal"><?= logo_baca() ?></a>
        <?php if ($halaman !== 'login'): ?><span class="auth-header-note"><?= ikon('buku') ?> Ruang untuk setiap pembaca</span><?php endif; ?>
    </header>
    <main id="konten" class="auth-main">
    <?php if ($flash && $halaman !== 'login'): ?><div class="pesan pesan-<?= e($flash['tipe']) ?> auth-flash" role="status"><?= ikon('centang') ?> <?= e($flash['pesan']) ?></div><?php endif; ?>
<?php else: ?>
<div class="site-frame">
    <aside class="sidebar" id="navigasi-utama" aria-label="Navigasi utama">
        <a class="logo" href="<?= e($base) ?>index.php" aria-label="Baca Mudah, perpustakaan"><?= logo_baca() ?></a>
        <button class="tombol-ikon nav-close" type="button" data-nav-close aria-label="Tutup menu"><?= ikon('tutup') ?></button>
        <div class="sidebar-label"><?= $isAdmin ? 'Pengelolaan' : 'Ruang bacamu' ?></div>
        <nav><ul class="nav-list">
            <?php foreach ($menuUtama as [$key, $icon, $label, $url]):
                $aktif = $halaman === $key || ($key === 'teks-saya' && $halaman === 'teks-edit'); ?>
                <li><a class="nav-link<?= $aktif ? ' aktif' : '' ?>" href="<?= e($base . $url) ?>"<?= $aktif ? ' aria-current="page"' : '' ?>><?= ikon($icon) ?><span><?= e($label) ?></span><?php if ($key === 'bookmarks' && $bookmarkCount): ?><span class="nav-count"><?= $bookmarkCount ?></span><?php endif; ?></a></li>
            <?php endforeach; ?>
        </ul></nav>

    </aside>
    <button type="button" class="nav-scrim" data-nav-close aria-label="Tutup navigasi" hidden></button>
    <div class="main-shell">
        <header class="workspace-header">
            <div class="workspace-location"><button type="button" class="tombol-ikon nav-toggle" data-nav-toggle aria-expanded="false" aria-controls="navigasi-utama" aria-label="Buka menu"><?= ikon('menu') ?></button><span><?= e($modeBaca ? 'Sedang membaca' : $judul) ?></span></div>
            <div class="workspace-actions"><details class="help-menu"><summary aria-label="<?= $isAdmin ? 'Panduan admin' : 'Panduan membaca' ?>"><?= ikon('bantuan') ?><span>Panduan</span></summary><div class="help-content"><strong><?= $isAdmin ? 'Kelola perpustakaan Baca Mudah.' : 'Selamat datang di ruang bacamu.' ?></strong><?php if ($isAdmin): ?><p>Buka Perpustakaan untuk melihat bacaan. Gunakan Kelola Teks untuk menambah atau mengubah bacaan umum, dan Kategori untuk mengelompokkan bacaan.</p><?php else: ?><p>Pilih bacaan di Perpustakaan, atau tempel tulisan sendiri di Teks Saya.</p><?php endif; ?><p>Di halaman baca, gunakan pengaturan untuk mengubah huruf dan warna. Coba Mode fokus atau Bacakan teks sesuai kebutuhan.</p><p class="catatan">Aplikasi ini alat bantu baca, bukan alat medis.</p></div></details>
                <details class="account-menu"><summary aria-label="Akun <?= e($namaPengguna) ?>"><span class="avatar"><?= e(mb_strtoupper(mb_substr($namaPengguna, 0, 1))) ?></span><span class="account-name"><?= e($namaDepan) ?></span><?= ikon('bawah') ?></summary><div class="account-content"><strong><?= e($namaPengguna) ?></strong><span><?= ($_SESSION['role'] ?? '') === 'admin' ? 'Administrator' : 'Pembaca' ?></span><form method="post" action="<?= e($base) ?>logout.php"><?= csrf_field() ?><button type="submit" class="logout-button"><?= ikon('keluar') ?> Keluar akun</button></form></div></details>
            </div>
        </header>
        <main id="konten" class="wadah">
        <?php if ($flash): ?><div class="pesan pesan-<?= e($flash['tipe']) ?>" role="status"><?= ikon($flash['tipe'] === 'sukses' ? 'centang' : 'bantuan') ?><span><?= e($flash['pesan']) ?></span></div><?php endif; ?>
<?php endif; ?>
