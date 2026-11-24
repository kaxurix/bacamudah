<?php
// Koneksi MySQL (PDO), session, dan fungsi bantu yang dipakai semua halaman.
// Ubah nilai di bawah ini sesuai server Anda (XAMPP: user root, password kosong).
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

function mode_produksi(): bool { return getenv('BACA_ENV') === 'production'; }

/** Pesan aman untuk HTML maupun permintaan simpan lewat JavaScript. */
function gagal_permintaan(int $status, string $pesan): void
{
    http_response_code($status);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=UTF-8');
        exit(json_encode(['sukses' => false, 'pesan' => $pesan]));
    }
    header('Content-Type: text/html; charset=UTF-8');
    exit('<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Permintaan tidak dapat diproses</title><body><main><h1>Permintaan tidak dapat diproses</h1><p>' . htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') . '</p><p>Gunakan tombol kembali di browser, lalu coba lagi.</p></main></body></html>');
}

set_exception_handler(function (Throwable $error): void {
    error_log((string)$error);
    gagal_permintaan(500, 'Terjadi kendala pada aplikasi. Silakan coba lagi beberapa saat lagi.');
});

/** Input harus berupa string, bukan array atau objek. */
function input_string(array $sumber, string $kunci, string $awal = ''): string
{
    $nilai = $sumber[$kunci] ?? $awal;
    if (!is_string($nilai) || !mb_check_encoding($nilai, 'UTF-8')) {
        gagal_permintaan(400, 'Format isian tidak valid. Silakan periksa kembali formulir.');
    }
    return $nilai;
}

function input_int(array $sumber, string $kunci, int $awal = 0): int
{
    $nilai = input_string($sumber, $kunci, (string)$awal);
    $angka = filter_var($nilai, FILTER_VALIDATE_INT);
    if ($angka === false) { gagal_permintaan(400, 'Format angka tidak valid.'); }
    return $angka;
}

$DB_HOST = getenv('BACA_DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('BACA_DB_NAME') ?: 'bacamudah';
$DB_USER = getenv('BACA_DB_USER') ?: 'root';
$DB_PASS = getenv('BACA_DB_PASS') ?: '';
$DB_PORT = getenv('BACA_DB_PORT') ?: '3306';
if (mode_produksi() && ($DB_USER === 'root' || $DB_PASS === '')) {
    gagal_permintaan(503, 'Konfigurasi database produksi belum lengkap. Hubungi pengelola.');
}

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log((string)$e);
    gagal_permintaan(503, 'Database belum dapat dihubungi. Silakan coba lagi nanti.');
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}
if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: DENY');
    header('Cache-Control: no-store');
}

/** Mengubah teks menjadi aman untuk ditampilkan di HTML. */
function e($teks): string
{
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function sudah_login(): bool
{
    return isset($_SESSION['user_id']);
}

/** Pemeriksaan autentikasi: harus sudah login. $base = awalan path ke root ('' atau '../'). */
function wajib_login(string $base = ''): void
{
    if (!sudah_login()) {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            gagal_permintaan(401, 'Sesi masukmu sudah berakhir. Masuk kembali sebelum menyimpan.');
        }
        header('Location: ' . $base . 'login.php');
        exit;
    }
}

/** Pemeriksaan otorisasi: harus berperan admin. */
function wajib_admin(): void
{
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: ../login.php');
        exit;
    }
}

/** Pesan singkat satu kali tampil (misalnya "Berhasil disimpan"). */
function set_flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function ambil_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

/** Token CSRF supaya form tidak bisa dikirim dari situs lain. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Menolak request POST yang tokennya tidak cocok. */
function csrf_periksa(): void
{
    $kirim = $_POST['csrf'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST'
        || !is_string($kirim) || $kirim === '' || empty($_SESSION['csrf'])
        || !hash_equals($_SESSION['csrf'] ?? '', $kirim)) {
        gagal_permintaan(400, 'Permintaan tidak valid atau sesi sudah berakhir. Muat ulang halaman lalu coba lagi.');
    }
}

/** Mengambil pengaturan baca milik pengguna (atau nilai awal jika belum ada). */
function ambil_pengaturan(PDO $pdo, int $userId): array
{
    $st = $pdo->prepare('SELECT font, ukuran, jarak_huruf, tinggi_baris, tema
                         FROM reading_settings WHERE user_id = ?');
    $st->execute([$userId]);
    $row = $st->fetch();
    return $row ?: [
        'font' => 'sans', 'ukuran' => 18, 'jarak_huruf' => 0.05,
        'tinggi_baris' => 1.8, 'tema' => 'normal',
    ];
}

/** Daftar font yang diizinkan (kunci disimpan di database). */
function daftar_font(): array
{
    return [
        'sans'    => 'Sans-serif (standar)',
        'serif'   => 'Serif',
        'verdana' => 'Verdana',
        'comic'   => 'Comic (santai)',
    ];
}

function buat_slug(string $teks): string
{
    $slug = strtolower(trim($teks));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-') ?: 'kategori';
}
