<?php
require __DIR__ . '/config/database.php';
wajib_login();
csrf_periksa();

$uid    = (int)$_SESSION['user_id'];
$textId = input_int($_POST, 'text_id');
$balik  = $textId > 0 ? 'read.php?id=' . $textId : 'index.php';
$json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

// Pemeriksaan ulang di server: angka bisa dikirim tanpa lewat slider.
$ukuran = filter_var($_POST['ukuran'] ?? null, FILTER_VALIDATE_INT);
$jarak  = filter_var($_POST['jarak_huruf'] ?? null, FILTER_VALIDATE_FLOAT);
$baris  = filter_var($_POST['tinggi_baris'] ?? null, FILTER_VALIDATE_FLOAT);
$font   = input_string($_POST, 'font');
$tema   = input_string($_POST, 'tema');

$valid = $ukuran !== false && $ukuran >= 16 && $ukuran <= 32
      && $jarak  !== false && $jarak  >= 0    && $jarak  <= 0.12
      && $baris  !== false && $baris  >= 1.5  && $baris  <= 2.0
      && array_key_exists($font, daftar_font())
      && in_array($tema, ['normal', 'krem', 'kontras'], true);

if (!$valid) {
    if ($json) { gagal_permintaan(422, 'Pengaturan tidak valid dan tidak disimpan.'); }
    set_flash('galat', 'Pengaturan tidak valid dan tidak disimpan.');
    header('Location: ' . $balik);
    exit;
}

$jarak = round($jarak, 2);
$baris = round($baris, 1);

$sql = 'INSERT INTO reading_settings (user_id, font, ukuran, jarak_huruf, tinggi_baris, tema)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            font = VALUES(font), ukuran = VALUES(ukuran), jarak_huruf = VALUES(jarak_huruf),
            tinggi_baris = VALUES(tinggi_baris), tema = VALUES(tema)';
$pdo->prepare($sql)->execute([$uid, $font, $ukuran, $jarak, $baris, $tema]);

if ($json) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['sukses' => true, 'pesan' => 'Pengaturan berhasil disimpan.']);
    exit;
}

set_flash('sukses', 'Pengaturan tampilan disimpan.');
header('Location: ' . $balik);
exit;
