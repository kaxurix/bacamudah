<?php
require __DIR__ . '/config/database.php';
wajib_login();
$uid = (int)$_SESSION['user_id'];

$error = [];
$judulIsi = '';
$isi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $judulIsi = trim(input_string($_POST, 'judul'));
    $isi      = trim(input_string($_POST, 'isi_teks'));

    if ($judulIsi === '' || mb_strlen($judulIsi) > 150) {
        $error[] = 'Judul wajib diisi (maksimal 150 karakter).';
    }
    if ($isi === '') {
        $error[] = 'Isi teks wajib diisi. Tempel teks yang ingin dibaca.';
    } elseif (strlen($isi) > 60000) {
        $error[] = 'Teks terlalu panjang (maksimal sekitar 60.000 karakter).';
    }

    if (!$error) {
        $ins = $pdo->prepare("INSERT INTO texts (user_id, category_id, visibilitas, judul, isi_teks)
                              VALUES (?, NULL, 'pribadi', ?, ?)");
        $ins->execute([$uid, $judulIsi, $isi]);
        set_flash('sukses', 'Teks berhasil disimpan.');
        header('Location: read.php?id=' . (int)$pdo->lastInsertId());
        exit;
    }
}

// Teks Saya: hanya teks pribadi milik pengguna yang login.
$st = $pdo->prepare("SELECT id, judul, isi_teks, created_at FROM texts
                     WHERE user_id = ? AND visibilitas = 'pribadi'
                     ORDER BY created_at DESC");
$st->execute([$uid]);
$daftar = $st->fetchAll();

$bukaEditor = (bool)$error || input_int($_GET, 'tulis') === 1;

$judul = 'Teks Saya';
$halaman = 'teks-saya';
include __DIR__ . '/includes/header.php';
?>
<header class="page-heading"><div><h1>Teks Saya</h1><p>Buka teks tersimpan atau tambahkan bacaan baru.</p></div><a href="teks-saya.php?tulis=1#tulis" class="tombol"><?= ikon('tambah') ?> Tambah teks</a></header>
<div class="private-layout">
    <section class="private-list" aria-labelledby="judul-daftar">
        <div class="private-list-heading"><h2 id="judul-daftar">Bacaan tersimpan</h2><span><?= count($daftar) ?> bacaan</span></div>
        <?php if (!$daftar): ?><div class="empty-state private-empty"><span class="empty-icon"><?= ikon('buku') ?></span><h3>Belum ada teks pribadi</h3><p>Tambahkan catatan atau artikel untuk mulai membaca.</p><a href="teks-saya.php?tulis=1#tulis" class="tautan-baca">Tambahkan teks pertama <?= ikon('panah') ?></a></div>
        <?php else: ?><ul class="daftar-baris"><?php foreach ($daftar as $t): ?><li class="kartu baris"><div><h3><a href="read.php?id=<?= (int)$t['id'] ?>"><?= e($t['judul']) ?></a></h3><p class="meta"><?= waktu_baca($t['isi_teks']) ?> menit baca <span class="meta-dot">·</span> <?= e(date('d M Y', strtotime($t['created_at']))) ?></p><p><?= e(mb_strimwidth(preg_replace('/\s+/u', ' ', $t['isi_teks']), 0, 105, '…')) ?></p></div><div class="aksi"><a class="tautan-baca" href="read.php?id=<?= (int)$t['id'] ?>">Baca lagi <?= ikon('panah') ?></a><a class="tombol-ikon" href="teks-edit.php?id=<?= (int)$t['id'] ?>" aria-label="Ubah <?= e($t['judul']) ?>"><?= ikon('pena') ?></a><form method="post" action="teks-hapus.php" class="form-inline" data-confirm="Teks “<?= e($t['judul']) ?>” akan dihapus beserta bookmark terkait. Tindakan ini tidak bisa dibatalkan."><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button type="submit" class="tombol-ikon tombol-hapus" aria-label="Hapus <?= e($t['judul']) ?>"><?= ikon('hapus') ?></button></form></div></li><?php endforeach; ?></ul><?php endif; ?>
    </section>
    <details class="private-composer" id="tulis"<?= $bukaEditor ? ' open' : '' ?>>
        <summary><?= ikon('tambah') ?><h2 id="judul-form">Tambahkan teks</h2><?= ikon('bawah') ?></summary>
        <section class="kartu editor-card" aria-labelledby="judul-form">
            <p class="catatan">Tempel teks yang ingin dibaca. Teks ini hanya bisa diakses olehmu.</p>
            <?php if ($error): ?><div class="pesan pesan-galat" role="alert"><ul><?php foreach ($error as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" novalidate data-guard-unsaved>
                <?= csrf_field() ?>
                <label for="judul">Judul bacaan</label><input type="text" id="judul" name="judul" maxlength="150" value="<?= e($judulIsi) ?>" placeholder="Beri judul yang mudah kamu temukan" required>
                <label for="isi_teks">Teks yang ingin dibaca</label><textarea id="isi_teks" name="isi_teks" rows="8" placeholder="Tempel atau tulis teksmu di sini…&#10;&#10;Pisahkan tiap paragraf dengan satu baris kosong agar lebih nyaman dibaca." required><?= e($isi) ?></textarea>
                <div class="editor-meta"><output id="editor-count" for="isi_teks">0 kata</output></div>
                <div class="editor-submit"><button type="submit" class="tombol">Simpan &amp; mulai membaca <?= ikon('panah') ?></button></div>
            </form>
            <p class="catatan">Gunakan teks yang bisa disalin, bukan gambar atau hasil pindai.</p>
        </section>
    </details>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
