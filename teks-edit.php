<?php
require __DIR__ . '/config/database.php';
wajib_login();
$uid = (int)$_SESSION['user_id'];
$id  = isset($_GET['id']) ? input_int($_GET, 'id') : input_int($_POST, 'id');

// Pemeriksaan kepemilikan: hanya teks pribadi milik pengguna yang login.
$st = $pdo->prepare("SELECT id, judul, isi_teks FROM texts
                     WHERE id = ? AND user_id = ? AND visibilitas = 'pribadi'");
$st->execute([$id, $uid]);
$teks = $st->fetch();
if (!$teks) {
    set_flash('galat', 'Teks tidak ditemukan atau bukan milik Anda.');
    header('Location: teks-saya.php');
    exit;
}

$error = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $judulBaru = trim(input_string($_POST, 'judul'));
    $isiBaru   = trim(input_string($_POST, 'isi_teks'));
    $teks['judul'] = $judulBaru;
    $teks['isi_teks'] = $isiBaru;

    if ($judulBaru === '' || mb_strlen($judulBaru) > 150) {
        $error[] = 'Judul wajib diisi (maksimal 150 karakter).';
    }
    if ($isiBaru === '') {
        $error[] = 'Isi teks wajib diisi.';
    } elseif (strlen($isiBaru) > 60000) {
        $error[] = 'Teks terlalu panjang (maksimal sekitar 60.000 karakter).';
    }

    if (!$error) {
        $up = $pdo->prepare("UPDATE texts SET judul = ?, isi_teks = ?
                             WHERE id = ? AND user_id = ? AND visibilitas = 'pribadi'");
        $up->execute([$judulBaru, $isiBaru, $id, $uid]);
        set_flash('sukses', 'Teks berhasil diperbarui.');
        header('Location: read.php?id=' . $id);
        exit;
    }
}

$judul = 'Ubah Teks';
$halaman = 'teks-edit';
include __DIR__ . '/includes/header.php';
?>
<a class="back-link" href="teks-saya.php"><?= ikon('kiri') ?> Kembali ke Teks Saya</a>
<header class="page-heading"><div><h1>Ubah teks</h1><p>Perbarui tulisanmu, lalu lanjutkan membaca dengan nyaman.</p></div></header>
<section class="kartu kartu-lebar editor-card" aria-labelledby="judul-form">
    <div class="editor-heading"><?= ikon('pena') ?><div><h2 id="judul-form">Ubah teks</h2><p>Perubahan hanya tersimpan setelah kamu menekan tombol simpan.</p></div></div>
    <?php if ($error): ?><div class="pesan pesan-galat" role="alert"><ul><?php foreach ($error as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" novalidate data-guard-unsaved><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>">
        <label for="judul">Judul bacaan</label><input type="text" id="judul" name="judul" maxlength="150" value="<?= e($teks['judul']) ?>" required>
        <label for="isi_teks">Teks yang ingin dibaca</label><textarea id="isi_teks" name="isi_teks" rows="14" required><?= e($teks['isi_teks']) ?></textarea>
        <div class="editor-meta"><span><?= ikon('kunci') ?> Hanya kamu yang bisa mengakses teks ini.</span><output id="editor-count" for="isi_teks">0 kata</output></div>
        <div class="aksi"><button type="submit" class="tombol"><?= ikon('centang') ?> Simpan perubahan</button><a class="tombol tombol-garis" href="teks-saya.php">Batal</a></div>
    </form>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
