<?php
require __DIR__ . '/config/database.php';
wajib_login();
csrf_periksa();

$uid    = (int)$_SESSION['user_id'];
$textId = input_int($_POST, 'text_id');
$aksi   = input_string($_POST, 'aksi');
$balik  = input_string($_POST, 'balik');
if (!in_array($aksi, ['tambah', 'hapus'], true)) { gagal_permintaan(400, 'Tindakan bookmark tidak valid.'); }
// Seluruh konteks diperiksa sebelum mengubah data.
$tujuan = 'read.php?id=' . $textId;
if ($balik === 'daftar') { $tujuan = 'bookmarks.php'; }
if ($balik === 'perpustakaan') {
    $tujuan = 'index.php?' . http_build_query([
        'q' => mb_substr(input_string($_POST, 'q'), 0, 150),
        'kategori' => max(0, input_int($_POST, 'kategori')),
        'halaman' => max(1, input_int($_POST, 'halaman', 1)),
    ]) . '#koleksi';
}

// Hanya teks yang boleh dibuka pengguna ini (umum, atau pribadi miliknya) yang bisa di-bookmark.
$cek = $pdo->prepare("SELECT id FROM texts WHERE id = ? AND (visibilitas = 'umum' OR user_id = ?)");
$cek->execute([$textId, $uid]);
if (!$cek->fetch()) {
    set_flash('galat', 'Teks tidak ditemukan.');
    header('Location: index.php');
    exit;
}

if ($aksi === 'tambah') {
    $pdo->prepare('INSERT IGNORE INTO bookmarks (user_id, text_id) VALUES (?, ?)')->execute([$uid, $textId]);
    set_flash('sukses', 'Ditambahkan ke bookmark.');
} elseif ($aksi === 'hapus') {
    $pdo->prepare('DELETE FROM bookmarks WHERE user_id = ? AND text_id = ?')->execute([$uid, $textId]);
    set_flash('sukses', 'Dihapus dari bookmark.');
}

header('Location: ' . $tujuan);
exit;
