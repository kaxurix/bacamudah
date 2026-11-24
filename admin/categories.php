<?php
require __DIR__ . '/../config/database.php';
wajib_admin(); // pemeriksaan otorisasi di baris paling atas

$error = [];
$editId = input_int($_GET, 'edit');
$editNama = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $aksi = input_string($_POST, 'aksi');
    if (!in_array($aksi, ['tambah', 'ubah', 'hapus'], true)) { gagal_permintaan(400, 'Tindakan tidak valid.'); }
    $id   = input_int($_POST, 'id');
    $nama = trim(input_string($_POST, 'nama_kategori'));

    // Pemeriksaan duplikat dan penulisan diserialisasi tanpa mengubah data lama.
    $namaKunci = 'baca-kategori-' . substr(hash('sha256', $DB_NAME), 0, 32);
    $kunci = $pdo->prepare('SELECT GET_LOCK(?, 5)');
    $kunci->execute([$namaKunci]);
    if ((int)$kunci->fetchColumn() !== 1) { gagal_permintaan(503, 'Kategori sedang diperbarui. Silakan coba lagi.'); }
    register_shutdown_function(function () use ($pdo, $namaKunci): void {
        try { $pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$namaKunci]); }
        catch (Throwable $error) { error_log((string)$error); }
    });

    if ($aksi === 'hapus') {
        // Teks yang memakai kategori ini otomatis menjadi "tanpa kategori" (ON DELETE SET NULL).
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        set_flash('sukses', 'Kategori dihapus.');
        header('Location: categories.php');
        exit;
    }

    if ($nama === '' || mb_strlen($nama) > 50) {
        $error[] = 'Nama kategori wajib diisi (maksimal 50 karakter).';
    }

    if (!$error && in_array($aksi, ['tambah', 'ubah'], true)) {
        $cek = $pdo->prepare('SELECT id FROM categories WHERE (nama_kategori = ? OR slug = ?) AND id <> ?');
        $cek->execute([$nama, buat_slug($nama), $aksi === 'ubah' ? $id : 0]);
        if ($cek->fetch()) { $error[] = 'Kategori dengan nama yang sama sudah ada. Gunakan kategori tersebut atau pilih nama lain.'; }
    }
    if (!$error && $aksi === 'tambah') {
        $pdo->prepare('INSERT INTO categories (nama_kategori, slug) VALUES (?, ?)')
            ->execute([$nama, buat_slug($nama)]);
        set_flash('sukses', 'Kategori ditambahkan.');
        header('Location: categories.php');
        exit;
    }
    if (!$error && $aksi === 'ubah') {
        $pdo->prepare('UPDATE categories SET nama_kategori = ?, slug = ? WHERE id = ?')
            ->execute([$nama, buat_slug($nama), $id]);
        set_flash('sukses', 'Kategori diperbarui.');
        header('Location: categories.php');
        exit;
    }
    if ($error && $aksi === 'ubah') {
        $editId = $id;
        $editNama = $nama;
    }
}

if ($editId > 0 && $editNama === '') {
    $st = $pdo->prepare('SELECT nama_kategori FROM categories WHERE id = ?');
    $st->execute([$editId]);
    $editNama = (string)($st->fetchColumn() ?: '');
    if ($editNama === '') {
        $editId = 0;
    }
}

$daftar = $pdo->query('SELECT c.id, c.nama_kategori, c.slug, COUNT(t.id) AS jumlah
                       FROM categories c
                       LEFT JOIN texts t ON t.category_id = c.id
                       GROUP BY c.id, c.nama_kategori, c.slug
                       ORDER BY c.nama_kategori')->fetchAll();

$judul = 'Kelola Kategori';
$base = '../';
include __DIR__ . '/../includes/header.php';
?>
<header class="page-heading"><div><h1>Kategori bacaan</h1><p>Kelompokkan bacaan agar mudah ditemukan.</p></div><span class="heading-count"><?= count($daftar) ?> kategori</span></header>
<div class="admin-layout">

<section class="kartu kartu-lebar" aria-labelledby="judul-form">
    <div class="editor-heading"><?= ikon('kategori') ?><div><h2 id="judul-form"><?= $editId ? 'Ubah kategori' : 'Kategori baru' ?></h2><p>Gunakan nama yang ringkas dan mudah dikenali.</p></div></div>
    <?php if ($error): ?>
        <div class="pesan pesan-galat" role="alert">
            <ul><?php foreach ($error as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form method="post" novalidate data-guard-unsaved>
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="<?= $editId ? 'ubah' : 'tambah' ?>">
        <?php if ($editId): ?><input type="hidden" name="id" value="<?= (int)$editId ?>"><?php endif; ?>
        <label for="nama_kategori">Nama kategori</label>
        <input type="text" id="nama_kategori" name="nama_kategori" maxlength="50" value="<?= e($editNama) ?>" required>
        <div class="aksi" style="margin-top:16px">
            <button type="submit" class="tombol"><?= $editId ? 'Simpan perubahan' : 'Tambah' ?></button>
            <?php if ($editId): ?><a class="tombol tombol-garis" href="categories.php">Batal</a><?php endif; ?>
        </div>
    </form>
</section>

<aside class="admin-note"><p>Menghapus kategori tidak menghapus bacaannya. Bacaan tersebut menjadi tanpa kategori.</p></aside>
</div>
<div class="tabel-bungkus" tabindex="0" role="region" aria-label="Daftar kategori">
<table>
    <thead>
        <tr><th scope="col">Nama</th><th scope="col">Jumlah teks</th><th scope="col">Aksi</th></tr>
    </thead>
    <tbody>
    <?php if (!$daftar): ?>
        <tr><td colspan="3">Belum ada kategori.</td></tr>
    <?php endif; ?>
    <?php foreach ($daftar as $k): ?>
        <tr>
            <td data-label="Nama"><?= e($k['nama_kategori']) ?></td>
            <td data-label="Jumlah teks"><?= (int)$k['jumlah'] ?></td>
            <td data-label="Aksi">
                <div class="aksi">
                    <a class="tombol tombol-garis" href="categories.php?edit=<?= (int)$k['id'] ?>">Ubah</a>
                    <form method="post" class="form-inline"
                          data-confirm="Kategori ini akan dihapus. Teks di dalamnya tetap ada dan menjadi tanpa kategori.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
                        <button type="submit" class="tombol tombol-bahaya">Hapus</button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
