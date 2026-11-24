<?php
require __DIR__ . '/../config/database.php';
wajib_admin(); // pemeriksaan otorisasi di baris paling atas

$uid = (int)$_SESSION['user_id'];
$uploadDir = __DIR__ . '/../uploads/';
$error = [];

/** Memproses file thumbnail (opsional). Mengembalikan nama file baru, null jika tidak ada upload. */
function proses_thumbnail(array &$error, string $uploadDir): ?string
{
    if (!isset($_FILES['thumbnail']) || $_FILES['thumbnail']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES['thumbnail'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $error[] = 'Upload thumbnail gagal. Coba lagi dengan file lain.';
        return null;
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        $error[] = 'Ukuran thumbnail maksimal 2 MB.';
        return null;
    }
    $info = @getimagesize($f['tmp_name']);
    $boleh = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($boleh[$info[2]])) {
        $error[] = 'Thumbnail harus berupa gambar JPG, PNG, atau WEBP.';
        return null;
    }
    $nama = bin2hex(random_bytes(12)) . '.' . $boleh[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], $uploadDir . $nama)) {
        $error[] = 'Thumbnail tidak bisa disimpan di folder uploads/.';
        return null;
    }
    return $nama;
}

function hapus_file_thumbnail(?string $nama, string $uploadDir): void
{
    if ($nama && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $nama) && is_file($uploadDir . $nama)) {
        @unlink($uploadDir . $nama);
    }
}

$kategori = $pdo->query('SELECT id, nama_kategori FROM categories ORDER BY nama_kategori')->fetchAll();
$idKategori = array_map('intval', array_column($kategori, 'id'));

$editId = input_int($_GET, 'edit');
$form = ['judul' => '', 'isi_teks' => '', 'category_id' => 0, 'thumbnail' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $aksi = input_string($_POST, 'aksi');
    if (!in_array($aksi, ['tambah', 'ubah', 'hapus'], true)) { gagal_permintaan(400, 'Tindakan tidak valid.'); }
    $id   = input_int($_POST, 'id');

    if ($aksi === 'hapus') {
        $st = $pdo->prepare("SELECT thumbnail FROM texts WHERE id = ? AND visibilitas = 'umum'");
        $st->execute([$id]);
        $lama = $st->fetchColumn();
        $pdo->prepare("DELETE FROM texts WHERE id = ? AND visibilitas = 'umum'")->execute([$id]);
        hapus_file_thumbnail($lama ?: null, $uploadDir);
        set_flash('sukses', 'Teks umum dihapus.');
        header('Location: texts.php');
        exit;
    }

    $form['judul']       = trim(input_string($_POST, 'judul'));
    $form['isi_teks']    = trim(input_string($_POST, 'isi_teks'));
    $form['category_id'] = input_int($_POST, 'category_id');

    if ($form['judul'] === '' || mb_strlen($form['judul']) > 150) {
        $error[] = 'Judul wajib diisi (maksimal 150 karakter).';
    }
    if ($form['isi_teks'] === '') {
        $error[] = 'Isi teks wajib diisi.';
    } elseif (strlen($form['isi_teks']) > 60000) {
        $error[] = 'Teks terlalu panjang (maksimal sekitar 60.000 karakter).';
    }
    if ($form['category_id'] !== 0 && !in_array($form['category_id'], $idKategori, true)) {
        $error[] = 'Kategori tidak valid.';
    }
    $kat = $form['category_id'] ?: null;

    if (!$error && $aksi === 'tambah') {
        $thumb = proses_thumbnail($error, $uploadDir);
        if (!$error) {
            $pdo->prepare("INSERT INTO texts (user_id, category_id, visibilitas, judul, isi_teks, thumbnail)
                           VALUES (?, ?, 'umum', ?, ?, ?)")
                ->execute([$uid, $kat, $form['judul'], $form['isi_teks'], $thumb]);
            set_flash('sukses', 'Teks ditambahkan ke Perpustakaan Umum.');
            header('Location: texts.php');
            exit;
        }
    }

    if (!$error && $aksi === 'ubah') {
        $st = $pdo->prepare("SELECT thumbnail FROM texts WHERE id = ? AND visibilitas = 'umum'");
        $st->execute([$id]);
        $lama = $st->fetchColumn();
        if ($lama === false) {
            $error[] = 'Teks tidak ditemukan.';
        } else {
            $baru = proses_thumbnail($error, $uploadDir);
            if (!$error) {
                $thumbAkhir = $baru ?? $lama;
                if (input_string($_POST, 'hapus_thumbnail') === '1' && $baru === null) {
                    $thumbAkhir = null;
                }
                $pdo->prepare("UPDATE texts SET category_id = ?, judul = ?, isi_teks = ?, thumbnail = ?
                               WHERE id = ? AND visibilitas = 'umum'")
                    ->execute([$kat, $form['judul'], $form['isi_teks'], $thumbAkhir, $id]);
                if ($thumbAkhir !== $lama) {
                    hapus_file_thumbnail($lama ?: null, $uploadDir);
                }
                set_flash('sukses', 'Teks diperbarui.');
                header('Location: texts.php');
                exit;
            }
        }
    }

    if ($error && $aksi === 'ubah') {
        $editId = $id;
    }
} elseif ($editId > 0) {
    $st = $pdo->prepare("SELECT judul, isi_teks, category_id, thumbnail FROM texts WHERE id = ? AND visibilitas = 'umum'");
    $st->execute([$editId]);
    $row = $st->fetch();
    if ($row) {
        $form = ['judul' => $row['judul'], 'isi_teks' => $row['isi_teks'],
                 'category_id' => (int)$row['category_id'], 'thumbnail' => $row['thumbnail']];
    } else {
        $editId = 0;
    }
}

if ($editId > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Setelah galat validasi, ambil thumbnail lama untuk ditampilkan.
    $st = $pdo->prepare("SELECT thumbnail FROM texts WHERE id = ? AND visibilitas = 'umum'");
    $st->execute([$editId]);
    $form['thumbnail'] = $st->fetchColumn() ?: null;
}

$cari = mb_substr(trim(input_string($_GET, 'q')), 0, 150);
$nomor = max(1, input_int($_GET, 'halaman', 1));
$perHalaman = 12;
$where = " WHERE t.visibilitas = 'umum'";
$param = [];
if ($cari !== '') { $where .= ' AND t.judul LIKE ?'; $param[] = '%' . $cari . '%'; }
$st = $pdo->prepare('SELECT COUNT(*) FROM texts t' . $where);
$st->execute($param);
$jumlah = (int)$st->fetchColumn();
$nomor = min($nomor, max(1, (int)ceil($jumlah / $perHalaman)));
$offset = ($nomor - 1) * $perHalaman;
$st = $pdo->prepare('SELECT t.id, t.judul, t.thumbnail, t.created_at, c.nama_kategori FROM texts t LEFT JOIN categories c ON c.id = t.category_id' . $where . " ORDER BY t.created_at DESC, t.id DESC LIMIT $perHalaman OFFSET $offset");
$st->execute($param);
$daftar = $st->fetchAll();

$judul = 'Kelola Teks Umum';
$base = '../';
include __DIR__ . '/../includes/header.php';
?>
<header class="page-heading"><div><h1>Kelola bacaan umum</h1><p>Tambahkan atau perbarui bacaan untuk semua pengguna.</p></div><span class="heading-count"><?= $jumlah ?> teks ditemukan</span></header>
<div class="admin-layout">

<section class="kartu kartu-lebar" aria-labelledby="judul-form">
    <div class="editor-heading"><?= ikon('pena') ?><div><h2 id="judul-form"><?= $editId ? 'Ubah teks umum' : 'Tambahkan bacaan' ?></h2><p>Isi judul dan teks. Kategori serta sampul bersifat opsional.</p></div></div>
    <?php if ($error): ?>
        <div class="pesan pesan-galat" role="alert">
            <ul><?php foreach ($error as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" novalidate data-guard-unsaved>
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="<?= $editId ? 'ubah' : 'tambah' ?>">
        <?php if ($editId): ?><input type="hidden" name="id" value="<?= (int)$editId ?>"><?php endif; ?>

        <label for="judul">Judul</label>
        <input type="text" id="judul" name="judul" maxlength="150" value="<?= e($form['judul']) ?>" required>

        <label for="category_id">Kategori</label>
        <select id="category_id" name="category_id">
            <option value="0">Tanpa kategori</option>
            <?php foreach ($kategori as $k): ?>
                <option value="<?= (int)$k['id'] ?>" <?= $form['category_id'] === (int)$k['id'] ? 'selected' : '' ?>>
                    <?= e($k['nama_kategori']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="isi_teks">Isi teks (pisahkan paragraf dengan satu baris kosong)</label>
        <textarea id="isi_teks" name="isi_teks" rows="6" required><?= e($form['isi_teks']) ?></textarea>

        <label for="thumbnail">Thumbnail (opsional, JPG/PNG/WEBP, maks. 2 MB)</label>
        <?php if ($editId && $form['thumbnail']): ?>
            <p><img src="../uploads/<?= e($form['thumbnail']) ?>" alt="Thumbnail saat ini" class="thumb-mini"></p>
            <label class="pilihan"><input type="checkbox" name="hapus_thumbnail" value="1"> Hapus thumbnail saat ini</label>
        <?php endif; ?>
        <input type="file" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/webp">

        <div class="aksi" style="margin-top:16px">
            <button type="submit" class="tombol"><?= $editId ? 'Simpan perubahan' : 'Tambah teks' ?></button>
            <?php if ($editId): ?><a class="tombol tombol-garis" href="texts.php">Batal</a><?php endif; ?>
        </div>
    </form>
</section>

<aside class="admin-note"><p>Pastikan kamu berhak membagikan teks. Pisahkan paragraf dengan baris kosong untuk membantu mode fokus.</p></aside>
</div>
<div class="section-heading" id="daftar"><h2>Daftar bacaan umum</h2><span class="collection-total"><?= $jumlah ?> bacaan</span></div>
<form method="get" class="library-search" role="search"><div class="search-field"><label for="q">Cari judul bacaan</label><input type="search" id="q" name="q" value="<?= e($cari) ?>" placeholder="Ketik judul bacaan"></div><button type="submit" class="tombol">Cari</button><?php if ($cari !== ''): ?><a href="texts.php#daftar" class="tautan-baca">Hapus filter</a><?php endif; ?></form>
<div class="tabel-bungkus" tabindex="0" role="region" aria-label="Daftar teks umum">
<table>
    <thead>
        <tr><th scope="col">Sampul</th><th scope="col">Judul</th><th scope="col">Kategori</th><th scope="col">Dibuat</th><th scope="col">Aksi</th></tr>
    </thead>
    <tbody>
    <?php if (!$daftar): ?>
        <tr><td colspan="5">Belum ada teks umum.</td></tr>
    <?php endif; ?>
    <?php foreach ($daftar as $t): ?>
        <tr>
            <td data-label="Sampul">
                <?php if ($t['thumbnail']): ?>
                    <img src="../uploads/<?= e($t['thumbnail']) ?>" alt="Sampul <?= e($t['judul']) ?>" class="thumb-mini">
                <?php else: ?>-<?php endif; ?>
            </td>
            <td data-label="Judul"><a href="../read.php?id=<?= (int)$t['id'] ?>"><?= e($t['judul']) ?></a></td>
            <td data-label="Kategori"><?= e($t['nama_kategori'] ?? 'Tanpa kategori') ?></td>
            <td data-label="Dibuat"><?= e(date('d M Y', strtotime($t['created_at']))) ?></td>
            <td data-label="Aksi">
                <div class="aksi">
                    <a class="tombol tombol-garis" href="texts.php?edit=<?= (int)$t['id'] ?>">Ubah</a>
                    <form method="post" class="form-inline"
                          data-confirm="Teks umum ini akan dihapus dari perpustakaan. Bookmark terkait ikut terhapus. Tindakan ini tidak bisa dibatalkan.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                        <button type="submit" class="tombol tombol-bahaya">Hapus</button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php paginasi($nomor, $jumlah, $perHalaman, ['q' => $cari], 'texts.php'); ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
