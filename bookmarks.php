<?php
require __DIR__ . '/config/database.php';
wajib_login();
$uid = (int)$_SESSION['user_id'];

// Bookmark milik pengguna, hanya untuk teks yang memang boleh ia buka.
$st = $pdo->prepare("SELECT t.id, t.judul, t.thumbnail, t.visibilitas, t.isi_teks, c.nama_kategori, b.created_at AS waktu
                     FROM bookmarks b
                     JOIN texts t ON t.id = b.text_id
                     LEFT JOIN categories c ON c.id = t.category_id
                     WHERE b.user_id = ? AND (t.visibilitas = 'umum' OR t.user_id = ?)
                     ORDER BY b.created_at DESC");
$st->execute([$uid, $uid]);
$daftar = $st->fetchAll();

$judul = 'Bookmark';
$halaman = 'bookmarks';
include __DIR__ . '/includes/header.php';
?>
<header class="page-heading"><div><h1>Bookmark</h1><p>Bacaan yang kamu simpan untuk dibuka kembali.</p></div><span class="heading-count"><?= count($daftar) ?> bacaan tersimpan</span></header>
<?php if (!$daftar): ?>
    <div class="empty-state"><span class="empty-icon"><?= ikon('bookmark') ?></span><h2>Belum ada bookmark</h2><p>Tekan ikon bookmark pada bacaan untuk menyimpannya di sini.</p><a class="tombol" href="index.php#koleksi">Lihat perpustakaan</a></div>
<?php else: ?>
    <ul class="grid-bacaan"><?php foreach ($daftar as $t) { kartu_bacaan($t, true, 'daftar'); } ?></ul>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
