<?php
// Ikon SVG lokal dan komponen kecil, tanpa pustaka antarmuka.
function ikon(string $nama, string $kelas = ''): string
{
    $garis = [
        'buku' => '<path d="M12 7C9 4 5 4 2 5v14c4-1 7 0 10 2m0-14c3-3 7-3 10-2v14c-4-1-7 0-10 2V7Z"/>',
        'rak' => '<path d="M4 4v16M9 4v16M14 5l5-1 3 15-5 1-3-15ZM2 20h12"/>',
        'pena' => '<path d="m16 3 5 5-12 12-6 1 1-6L16 3Zm-2 2 5 5M4 15l5 5"/>',
        'bookmark' => '<path d="M6 3h12v18l-6-4-6 4V3Z"/>',
        'cari' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'panah' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'kiri' => '<path d="M20 12H4m6 6-6-6 6-6"/>',
        'kanan' => '<path d="m9 5 7 7-7 7"/>',
        'bawah' => '<path d="m6 9 6 6 6-6"/>',
        'tambah' => '<path d="M12 5v14M5 12h14"/>',
        'tutup' => '<path d="m6 6 12 12M6 18 18 6"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'keluar' => '<path d="M9 4H4v16h5m5-13 5 5-5 5M9 12h11"/>',
        'jam' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'suara' => '<path d="m11 4-6 5H2v6h3l6 5V4Zm4 4a6 6 0 0 1 0 8m3-11a10 10 0 0 1 0 14"/>',
        'stop' => '<rect x="6" y="6" width="12" height="12" rx="1" fill="currentColor" stroke="none"/>',
        'fokus' => '<path d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5M7 12h10"/>',
        'atur' => '<path d="M4 7h7m5 0h4M4 17h3m5 0h8"/><circle cx="13" cy="7" r="2"/><circle cx="9" cy="17" r="2"/>',
        'mata' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'centang' => '<path d="m5 12 4 4L19 6"/>',
        'bantuan' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4 2c-1 .6-1.5 1-1.5 2M12 17h.01"/>',
        'kunci' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3"/>',
        'daun' => '<path d="M20 3C8 2 2 7 4 14c2 7 15 7 16-11ZM4 20 15 9"/>',
        'ulang' => '<path d="M3 10a9 9 0 1 1 2 8M3 4v6h6"/>',
        'hapus' => '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/>',
        'kategori' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'perisai' => '<path d="m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6l9-4Z"/><path d="m8 12 3 3 5-6"/>',
    ];
    return '<svg class="ikon ' . htmlspecialchars($kelas, ENT_QUOTES, 'UTF-8') . '" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($garis[$nama] ?? $garis['buku']) . '</svg>';
}

function logo_baca(): string
{
    return '<span class="logo-mark" aria-hidden="true"><svg viewBox="0 0 36 36" fill="none"><path d="M7 8c4-1 8 0 11 3 3-3 7-4 11-3v20c-4-1-8 0-11 2-3-2-7-3-11-2V8Z" stroke="currentColor" stroke-width="1.7"/><path d="M18 11v19M11 13l3 1m-3 4 3 1m8-5 3-1m-3 6 3-1" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></span><span>baca<span class="logo-light">mudah</span><span class="logo-dot">.</span></span>';
}

function jumlah_kata(string $teks): int
{
    return count(preg_split('/\s+/u', trim($teks), -1, PREG_SPLIT_NO_EMPTY));
}

function waktu_baca(string $teks): int
{
    return max(1, (int)ceil(jumlah_kata($teks) / 180));
}

function sampul_bacaan(array $teks): string
{
    if (!empty($teks['thumbnail'])) {
        return '<img class="sampul-foto" src="uploads/' . e($teks['thumbnail']) . '" alt="" loading="lazy" width="600" height="400">';
    }
    $varian = ((int)$teks['id'] - 1) % 3;
    $kategori = e($teks['nama_kategori'] ?? (($teks['visibilitas'] ?? '') === 'pribadi' ? 'Teks pribadi' : 'Bacaan pilihan'));
    $seni = [
        '<svg viewBox="0 0 320 200" fill="none"><path d="M160 177V72m0 62c-45 0-69-25-68-61 43 0 68 25 68 61Zm0-29c44 0 68-27 66-64-43 2-66 28-66 64Z" stroke="currentColor" stroke-width="1.5"/><path d="M160 161c-39 0-61-19-62-47 37-2 62 20 62 47Z" fill="currentColor" opacity=".12"/><circle cx="160" cy="100" r="84" stroke="currentColor" stroke-opacity=".2"/><path d="M76 177h168" stroke="currentColor" stroke-width="1.5"/></svg>',
        '<svg viewBox="0 0 320 200" fill="none"><path d="M85 45c28-4 52 3 75 19 24-16 48-23 75-19v112c-28-4-52 3-75 17-23-14-47-21-75-17V45Z" stroke="currentColor" stroke-width="1.5"/><path d="M160 65v108m-60-99c14 0 26 3 43 12m-43 8c14 0 26 3 43 12m-43 8c14 0 26 3 43 12m34-40c17-9 29-12 43-12m-43 32c17-9 29-12 43-12m-43 32c17-9 29-12 43-12" stroke="currentColor" stroke-width="1.5"/><circle cx="246" cy="44" r="18" fill="currentColor" opacity=".18"/><path d="m246 33 0 22m-11-11h22" stroke="currentColor"/></svg>',
        '<svg viewBox="0 0 320 200" fill="none"><path d="M103 172V82a57 57 0 0 1 114 0v90H103Z" stroke="currentColor" stroke-width="1.5"/><path d="M114 172V83a46 46 0 0 1 92 0v89M160 37v135m-46-67h92M92 173h136" stroke="currentColor" stroke-width="1.5"/><circle cx="182" cy="78" r="16" fill="currentColor" opacity=".18"/><path d="m115 150 28-27 23 18 16-12 23 22" stroke="currentColor" stroke-width="1.5"/></svg>',
    ];
    return '<div class="sampul-ilustrasi sampul-' . $varian . '" aria-hidden="true">' . $seni[$varian] . '<span class="sampul-kategori">' . $kategori . '</span></div>';
}

function kartu_bacaan(array $teks, bool $tersimpan = false, string $asal = 'perpustakaan'): void
{
    $id = (int)$teks['id'];
    $kategori = $teks['nama_kategori'] ?? (($teks['visibilitas'] ?? '') === 'pribadi' ? 'Teks pribadi' : 'Bacaan');
    ?>
    <li class="kartu-bacaan">
        <a class="sampul-link" href="read.php?id=<?= $id ?>" aria-label="Baca <?= e($teks['judul']) ?>"><?= sampul_bacaan($teks) ?></a>
        <div class="kartu-bacaan-isi">
            <div class="kartu-meta"><span><?= e($kategori) ?></span><span><?= ikon('jam') ?> <?= waktu_baca($teks['isi_teks']) ?> menit</span></div>
            <h3><a href="read.php?id=<?= $id ?>"><?= e($teks['judul']) ?></a></h3>
            <p><?= e(mb_strimwidth(preg_replace('/\s+/u', ' ', $teks['isi_teks']), 0, 112, '…')) ?></p>
            <div class="kartu-bacaan-akhir"><a class="tautan-baca" href="read.php?id=<?= $id ?>">Mulai membaca <?= ikon('panah') ?></a>
                <form method="post" action="bookmark_aksi.php" class="form-inline"><?= csrf_field() ?><input type="hidden" name="text_id" value="<?= $id ?>"><input type="hidden" name="aksi" value="<?= $tersimpan ? 'hapus' : 'tambah' ?>"><input type="hidden" name="balik" value="<?= e($asal) ?>">
                    <?php if ($asal === 'perpustakaan'): ?><input type="hidden" name="q" value="<?= e(input_string($_GET, 'q')) ?>"><input type="hidden" name="kategori" value="<?= max(0, input_int($_GET, 'kategori')) ?>"><input type="hidden" name="halaman" value="<?= max(1, input_int($_GET, 'halaman', 1)) ?>"><?php endif; ?>
                    <button class="tombol-ikon <?= $tersimpan ? 'is-saved' : '' ?>" type="submit" aria-label="<?= $tersimpan ? 'Hapus bookmark' : 'Simpan ke bookmark' ?>: <?= e($teks['judul']) ?>" title="<?= $tersimpan ? 'Hapus bookmark' : 'Simpan bacaan' ?>"><?= ikon('bookmark') ?></button></form>
            </div>
        </div>
    </li>
    <?php
}

function paginasi(int $halaman, int $jumlah, int $perHalaman, array $filter = [], string $url = 'index.php'): void
{
    $akhir = max(1, (int)ceil($jumlah / $perHalaman));
    if ($akhir === 1) { return; }
    echo '<nav class="pagination" aria-label="Halaman daftar bacaan">';
    if ($halaman > 1) { echo '<a class="tombol tombol-garis" href="' . e($url . '?' . http_build_query(array_merge($filter, ['halaman' => $halaman - 1]))) . '">Sebelumnya</a>'; }
    echo '<span>Halaman ' . $halaman . ' dari ' . $akhir . '</span>';
    if ($halaman < $akhir) { echo '<a class="tombol tombol-garis" href="' . e($url . '?' . http_build_query(array_merge($filter, ['halaman' => $halaman + 1]))) . '">Selanjutnya</a>'; }
    echo '</nav>';
}
