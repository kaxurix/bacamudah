<?php
require __DIR__ . '/config/database.php';
wajib_login();
csrf_periksa();

$uid = (int)$_SESSION['user_id'];
$id  = input_int($_POST, 'id');

// Query selalu menyertakan user_id dari session, jadi teks milik orang lain tidak bisa dihapus.
$del = $pdo->prepare("DELETE FROM texts WHERE id = ? AND user_id = ? AND visibilitas = 'pribadi'");
$del->execute([$id, $uid]);

if ($del->rowCount() > 0) {
    set_flash('sukses', 'Teks berhasil dihapus.');
} else {
    set_flash('galat', 'Teks tidak ditemukan atau bukan milik Anda.');
}
header('Location: teks-saya.php');
exit;
