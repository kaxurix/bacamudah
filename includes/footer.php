</main>
<?php if ($authPage ?? false): ?>
    <?php if ($halaman !== 'login'): ?>
    <footer class="auth-footer"><span>Setiap pembaca berhak merasa nyaman.</span><span>Proyek Praktikum Pemweb · Unsoed</span></footer>
    <?php endif; ?>
</div>
<?php else: ?>
        <footer class="footer"><div><a class="logo footer-brand" href="<?= e($base) ?>index.php" aria-label="Baca Mudah, perpustakaan"><?= logo_baca() ?></a><span>Satu bacaan, banyak cara menikmati.</span></div></footer>
        <dialog class="confirm-dialog" id="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-message"><form method="dialog"><span class="dialog-icon"><?= ikon('hapus') ?></span><h2 id="confirm-title">Hapus data ini?</h2><p id="confirm-message"></p><div class="aksi"><button type="submit" class="tombol tombol-garis" value="batal" autofocus>Tetap simpan</button><button type="submit" class="tombol tombol-bahaya" value="hapus">Ya, hapus</button></div></form></dialog>
    </div>
</div>
<?php endif; ?>
<?php if (!empty($skripTambahan)): ?><?= $skripTambahan ?><?php endif; ?>
</body>
</html>
