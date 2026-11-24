<?php
require __DIR__ . '/config/database.php';
wajib_login();
$uid = (int)$_SESSION['user_id'];
$kat = max(0, input_int($_GET, 'kategori'));
$cari = mb_substr(trim(input_string($_GET, 'q')), 0, 150);
$nomor = max(1, input_int($_GET, 'halaman', 1));
$perHalaman = 12;
$kategori = $pdo->query('SELECT id, nama_kategori FROM categories ORDER BY nama_kategori')->fetchAll();
$where = " WHERE t.visibilitas = 'umum'";
$param = [];
if ($kat > 0) { $where .= ' AND t.category_id = ?'; $param[] = $kat; }
if ($cari !== '') { $where .= ' AND t.judul LIKE ?'; $param[] = '%' . $cari . '%'; }
$st = $pdo->prepare('SELECT COUNT(*) FROM texts t' . $where);
$st->execute($param);
$jumlah = (int)$st->fetchColumn();
$nomor = min($nomor, max(1, (int)ceil($jumlah / $perHalaman)));
$offset = ($nomor - 1) * $perHalaman;
$st = $pdo->prepare('SELECT t.id, t.judul, t.thumbnail, t.isi_teks, t.visibilitas, c.nama_kategori FROM texts t LEFT JOIN categories c ON c.id = t.category_id' . $where . " ORDER BY t.created_at DESC, t.id DESC LIMIT $perHalaman OFFSET $offset");
$st->execute($param);
$daftar = $st->fetchAll();
$bm = $pdo->prepare('SELECT text_id FROM bookmarks WHERE user_id = ?');
$bm->execute([$uid]);
$tersimpan = array_map('intval', $bm->fetchAll(PDO::FETCH_COLUMN));
$lanjut = null;
if (!empty($_SESSION['bacaan_terakhir'])) {
    $st = $pdo->prepare("SELECT id, judul FROM texts WHERE id = ? AND (visibilitas = 'umum' OR user_id = ?)");
    $st->execute([(int)$_SESSION['bacaan_terakhir'], $uid]);
    $lanjut = $st->fetch() ?: null;
}
$judul = 'Perpustakaan'; $halaman = 'index';
include __DIR__ . '/includes/header.php';
?>
<header class="page-heading library-heading"><div><h1>Perpustakaan</h1><p>Pilih bacaan yang ingin kamu baca.</p></div><a href="teks-saya.php?tulis=1#tulis" class="tombol tombol-garis"><?= ikon('tambah') ?> Tambah teks</a></header>
<?php if ($lanjut): ?><a class="continue-reading" href="read.php?id=<?= (int)$lanjut['id'] ?>"><span><?= ikon('buku') ?></span><div><span class="catatan">Bacaan terakhir</span><strong><?= e($lanjut['judul']) ?></strong></div><span class="continue-action">Baca lagi</span></a><?php endif; ?>
<section id="koleksi" aria-label="Koleksi bacaan">
    <form method="get" class="library-search" role="search">
        <div class="search-field"><label for="q">Cari judul</label><input type="search" id="q" name="q" value="<?= e($cari) ?>" placeholder="Ketik judul bacaan"></div>
        <div class="category-select"><label for="kategori">Kategori</label><select id="kategori" name="kategori"><option value="0">Semua kategori</option><?php foreach ($kategori as $k): ?><option value="<?= (int)$k['id'] ?>"<?= $kat === (int)$k['id'] ? ' selected' : '' ?>><?= e($k['nama_kategori']) ?></option><?php endforeach; ?></select></div>
        <button type="submit" class="tombol">Cari</button>
    </form>
    <div class="search-result-note" role="status"><span><?= $jumlah ?> bacaan<?= $cari !== '' ? ' untuk “' . e($cari) . '”' : '' ?></span><?php if ($cari !== '' || $kat > 0): ?><a href="index.php#koleksi">Hapus filter</a><?php endif; ?></div>
    <?php if (!$daftar): ?><div class="empty-state"><span class="empty-icon"><?= ikon('cari') ?></span><h2>Tidak ada bacaan yang sesuai</h2><p>Coba judul lain atau hapus filter kategori.</p><a class="tombol tombol-garis" href="index.php#koleksi">Lihat semua bacaan</a></div><?php else: ?><ul class="grid-bacaan"><?php foreach ($daftar as $t) { kartu_bacaan($t, in_array((int)$t['id'], $tersimpan, true)); } ?></ul><?php endif; ?>
    <?php paginasi($nomor, $jumlah, $perHalaman, ['q' => $cari, 'kategori' => $kat]); ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
