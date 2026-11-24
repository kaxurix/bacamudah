<?php
require __DIR__ . '/config/database.php';
wajib_login();
$uid = (int)$_SESSION['user_id'];
$id  = input_int($_GET, 'id');

// Teks umum boleh dibuka siapa saja yang login; teks pribadi hanya oleh pemiliknya.
$st = $pdo->prepare("SELECT t.id, t.judul, t.isi_teks, t.visibilitas, t.user_id, c.nama_kategori
                     FROM texts t
                     LEFT JOIN categories c ON c.id = t.category_id
                     WHERE t.id = ? AND (t.visibilitas = 'umum' OR t.user_id = ?)");
$st->execute([$id, $uid]);
$teks = $st->fetch();
if (!$teks) {
    http_response_code(404);
    set_flash('galat', 'Teks tidak ditemukan atau Anda tidak berhak membukanya.');
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/ui.php';
$_SESSION['bacaan_terakhir'] = $id;
$set = ambil_pengaturan($pdo, $uid);
$fonts = daftar_font();
if (!isset($fonts[$set['font']])) { $set['font'] = 'sans'; }
$bk = $pdo->prepare('SELECT id FROM bookmarks WHERE user_id = ? AND text_id = ?');
$bk->execute([$uid, $id]);
$sudahBookmark = (bool)$bk->fetch();
$daftarParagraf = preg_split('/\R{2,}/u', trim($teks['isi_teks']));
$judul = $teks['judul'];
$halaman = 'read';
$bodyAttr = 'data-tema="' . e($set['tema']) . '" data-font="' . e($set['font']) . '" style="--size:' . (int)$set['ukuran'] . 'px;--spacing:' . (float)$set['jarak_huruf'] . 'em;--line:' . (float)$set['tinggi_baris'] . ';"';
$skripTambahan = '<script src="assets/js/reader.js?v=' . filemtime(__DIR__ . '/assets/js/reader.js') . '"></script>';
include __DIR__ . '/includes/header.php';
?>
<div class="reader-progress" role="progressbar" aria-label="Kemajuan membaca" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span id="progress-line"></span></div>
<div class="reader-topline">
    <a class="back-link" href="<?= $teks['visibilitas'] === 'pribadi' ? 'teks-saya.php' : 'index.php' ?>"><?= ikon('kiri') ?> <?= $teks['visibilitas'] === 'pribadi' ? 'Teks Saya' : 'Perpustakaan' ?></a>
    <span><?= ikon('jam') ?> <?= waktu_baca($teks['isi_teks']) ?> menit baca <span class="meta-dot">·</span> <?= jumlah_kata($teks['isi_teks']) ?> kata</span>
    <div class="reader-topline-actions">
        <button type="button" class="tombol tombol-kecil reader-stop-button" id="btn-stop-reading" hidden><?= ikon('stop') ?> Berhenti</button>
        <button type="button" class="mobile-settings-button tombol tombol-garis" data-settings-toggle aria-controls="panel-atur" aria-expanded="false"><?= ikon('atur') ?> Pengaturan baca</button>
    </div>
</div>
<div class="baca-tata">
    <article class="baca-artikel" aria-labelledby="judul-teks" data-text-id="<?= $id ?>" data-user-id="<?= $uid ?>">
        <header class="article-heading"><span class="eyebrow"><?= e($teks['visibilitas'] === 'umum' ? ($teks['nama_kategori'] ?? 'Perpustakaan Umum') : 'RUANG PRIBADI') ?></span><h1 id="judul-teks"><?= e($teks['judul']) ?></h1>
            <div class="alat-baca" role="group" aria-label="Alat bantu baca">
                <button type="button" class="tombol tombol-kecil" id="btn-baca" aria-pressed="false"><?= ikon('suara') ?><span data-baca-label>Bacakan teks</span></button>
                <button type="button" class="tombol tombol-garis tombol-kecil" id="btn-fokus" aria-pressed="false"><?= ikon('fokus') ?><span data-fokus-label>Mode fokus</span></button>
                <form method="post" action="bookmark_aksi.php" class="form-inline bookmark-reader"><?= csrf_field() ?><input type="hidden" name="text_id" value="<?= $id ?>"><input type="hidden" name="aksi" value="<?= $sudahBookmark ? 'hapus' : 'tambah' ?>"><button type="submit" class="tombol-ikon<?= $sudahBookmark ? ' is-saved' : '' ?>" aria-label="<?= $sudahBookmark ? 'Hapus bookmark' : 'Tambah bookmark' ?>" title="<?= $sudahBookmark ? 'Hapus bookmark' : 'Simpan bacaan' ?>"><?= ikon('bookmark') ?></button></form>
            </div>
            <p id="info-suara" class="catatan info-suara" role="status" hidden></p>
        </header>
        <div class="focus-navigation" aria-label="Navigasi paragraf"><span id="focus-position" role="status">Paragraf 1 dari <?= count($daftarParagraf) ?></span><div><button type="button" class="tombol-ikon" id="focus-prev" aria-label="Paragraf sebelumnya"><?= ikon('kiri') ?></button><button type="button" class="tombol-ikon" id="focus-next" aria-label="Paragraf selanjutnya"><?= ikon('panah') ?></button></div></div>
        <div class="isi-bacaan" id="isi-bacaan">
            <?php foreach ($daftarParagraf as $i => $p): ?><p tabindex="0" data-paragraph="<?= $i ?>"><?= e($p) ?></p><?php endforeach; ?>
        </div>
        <div class="article-end"><p>Selesai membaca.</p><a href="index.php#koleksi" class="tautan-baca">Temukan bacaan berikutnya <?= ikon('panah') ?></a></div>
        <div class="reading-hint"><?= ikon('fokus') ?><span>Klik atau ketuk sekali untuk fokus, dua kali dengan cepat untuk membacakan. Hentikan suara lewat tombol Berhenti. Dengan keyboard: <kbd>Enter</kbd> untuk fokus, <kbd>Ctrl</kbd> + <kbd>Enter</kbd> untuk membacakan, <kbd>Esc</kbd> untuk berhenti dan keluar dari fokus.</span></div>
    </article>
    <aside class="panel-atur" id="panel-atur" aria-labelledby="judul-atur">
        <div class="panel-heading"><span class="panel-icon"><?= ikon('atur') ?></span><div><h2 id="judul-atur">Pengaturan baca</h2></div><button type="button" class="tombol-ikon settings-close" data-settings-close aria-label="Tutup pengaturan"><?= ikon('tutup') ?></button></div>
        <form method="post" action="simpan_pengaturan.php" id="form-atur">
            <?= csrf_field() ?><input type="hidden" name="text_id" value="<?= $id ?>">
            <div class="settings-fields">
            <div class="reading-presets"><span>Mulai dengan satu sentuhan</span><div><button type="button" data-reading-preset="lapang" aria-pressed="false">Lebih lapang</button><button type="button" data-reading-preset="besar" aria-pressed="false">Teks besar</button></div></div>
            <div class="setting-block"><label for="font">Jenis huruf</label><select id="font" name="font"><?php foreach ($fonts as $kode => $nama): ?><option value="<?= e($kode) ?>"<?= $set['font'] === $kode ? ' selected' : '' ?>><?= e($nama) ?></option><?php endforeach; ?></select></div>
            <div class="setting-block"><label for="ukuran">Ukuran huruf <output id="out-ukuran" for="ukuran"><?= (int)$set['ukuran'] ?> px</output></label><input type="range" id="ukuran" name="ukuran" min="16" max="32" step="1" value="<?= (int)$set['ukuran'] ?>"><div class="range-ends"><span>A kecil</span><span>A besar</span></div></div>
            <div class="setting-block"><label for="jarak_huruf">Jarak huruf <output id="out-jarak" for="jarak_huruf"><?= number_format((float)$set['jarak_huruf'], 2, ',', '') ?> em</output></label><input type="range" id="jarak_huruf" name="jarak_huruf" min="0" max="0.12" step="0.01" value="<?= e(number_format((float)$set['jarak_huruf'], 2, '.', '')) ?>"><div class="range-ends"><span>Rapat</span><span>Renggang</span></div></div>
            <div class="setting-block"><label for="tinggi_baris">Jarak baris <output id="out-baris" for="tinggi_baris"><?= number_format((float)$set['tinggi_baris'], 1, ',', '') ?></output></label><input type="range" id="tinggi_baris" name="tinggi_baris" min="1.5" max="2" step="0.1" value="<?= e(number_format((float)$set['tinggi_baris'], 1, '.', '')) ?>"><div class="range-ends"><span>Dekat</span><span>Lapang</span></div></div>
            <fieldset class="tema-fieldset"><legend>Warna halaman</legend><div class="theme-options"><?php foreach (['normal' => 'Terang', 'krem' => 'Kertas', 'kontras' => 'Kontras'] as $kode => $nama): ?><label class="theme-option"><input type="radio" name="tema" value="<?= e($kode) ?>"<?= $set['tema'] === $kode ? ' checked' : '' ?>><span class="theme-swatch theme-<?= e($kode) ?>">Aa</span><span><?= e($nama) ?></span></label><?php endforeach; ?></div></fieldset>
            </div>
            <div class="settings-footer">
            <div class="setting-actions"><button type="submit" class="tombol tombol-penuh"><?= ikon('centang') ?><span>Simpan pengaturan</span></button><button type="button" class="reset-button" id="btn-reset"><?= ikon('ulang') ?> Atur ulang</button></div>
            <p class="settings-note" id="settings-status" role="status">Perubahan langsung terlihat. Simpan agar diingat saat kembali.</p>
            </div>
        </form>
    </aside>
</div>
<button type="button" class="settings-scrim" data-settings-close aria-label="Tutup pengaturan" hidden></button>
<?php include __DIR__ . '/includes/footer.php'; ?>
