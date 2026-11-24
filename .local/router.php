<?php
// Router khusus pratinjau lokal: hanya halaman aplikasi dan aset publik.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$allowedPage = preg_match('~^/(?:index|login|logout|register|read|bookmarks|bookmark_aksi|simpan_pengaturan|teks-saya|teks-edit|teks-hapus)\.php$~', $path)
    || preg_match('~^/admin/(?:index|texts|categories)\.php$~', $path);
$allowedAsset = preg_match('~^/assets/[a-zA-Z0-9/_ .-]+\.(?:css|js|woff2?|ttf|otf|svg|png|jpe?g|webp|gif|ico)$~i', $path);
$allowedUpload = preg_match('~^/uploads/[a-zA-Z0-9_-]+\.(?:png|jpe?g|webp|gif)$~i', $path);
if (preg_match('~(?:^|/)\.~', $path) || (!($allowedPage || $allowedAsset || $allowedUpload) && $path !== '/')) {
    http_response_code(404);
    echo 'Halaman tidak ditemukan.';
    return true;
}
return false;
