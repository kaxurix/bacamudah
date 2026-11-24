# Baca Mudah

Web untuk membaca teks dengan tampilan yang bisa diatur sendiri oleh pembacanya, dan bisa membacakan teks lewat suara. Proyek akhir (Responsi 2) Praktikum Pemrograman Web I, Jurusan Informatika, Fakultas Teknik, Universitas Jenderal Soedirman.

> Baca Mudah adalah alat bantu baca, bukan alat medis, dan tidak menyembuhkan disleksia. Aplikasi tidak mengubah isi teks, hanya cara menampilkannya.

## Latar Belakang dan Tujuan

**Masalah.** Bagi sebagian penyandang disleksia, huruf di layar terasa terlalu dempet sehingga sulit dibedakan dan baris terasa melompat. Penyandang low vision membutuhkan huruf lebih besar dan warna yang lebih tegas. Kebutuhan tiap orang berbeda, sehingga tampilan standar tidak cocok untuk semua.

**Tujuan.**

- Menyediakan tempat membaca yang tampilannya (ukuran huruf, jarak huruf, jarak baris, jenis huruf, tema warna) dapat diatur dan diingat per akun.
- Memungkinkan pengguna membaca teks apa pun miliknya dengan cara menempelkan (copy-paste) teks, tanpa upload file.
- Menyediakan fitur bantu seperti mode fokus dan baca nyaring.

**Keterkaitan dengan SDGs.**

- **SDG 4, Pendidikan Berkualitas:** bahan belajar menjadi lebih mudah diakses oleh pembaca dengan kebutuhan berbeda.
- **SDG 10, Berkurangnya Kesenjangan:** mengurangi hambatan membaca bagi penyandang disleksia dan low vision.

## Daftar Fitur / Modul

| No | Modul | Berkas utama |
|----|-------|--------------|
| 1 | Autentikasi (daftar, login, logout, session) | `register.php`, `login.php`, `logout.php` |
| 2 | Perpustakaan Umum (daftar, cari, filter kategori) | `index.php` |
| 3 | Halaman baca (paragraf, panel pengaturan) | `read.php` |
| 4 | Teks Saya (tambah, ubah, hapus teks pribadi) | `teks-saya.php`, `teks-edit.php`, `teks-hapus.php` |
| 5 | Pengaturan tampilan (slider, font, tema, simpan ke database) | `assets/js/reader.js`, `simpan_pengaturan.php` |
| 6 | Mode fokus (sorot satu paragraf) | `assets/js/reader.js` |
| 7 | Baca nyaring (Web Speech API) | `assets/js/reader.js` |
| 8 | Bookmark | `bookmarks.php`, `bookmark_aksi.php` |
| 9 | Admin: kelola teks umum + upload thumbnail | `admin/texts.php` |
| 10 | Admin: kelola kategori | `admin/categories.php` |

### Peran dan menu

Menu navigasi menyesuaikan peran akun setelah login:

| Peran | Menu yang ditampilkan | Penggunaan utama |
|-------|------------------------|------------------|
| Pengguna | **Perpustakaan**, **Teks Saya**, **Bookmark** | Membaca teks umum, mengelola teks pribadi, dan menyimpan bacaan. |
| Admin | **Perpustakaan**, **Kelola Teks**, **Kategori** | Melihat bacaan umum, mengelola teks beserta sampulnya, dan mengatur kategori. |

Admin hanya melihat tiga menu di atas; **Teks Saya** dan **Bookmark** tidak
ditampilkan dalam navigasi admin. Pengguna biasa tidak dapat membuka halaman
pengelolaan admin. Kedua peran dapat membaca teks umum dan memakai pengaturan
baca di Perpustakaan.

## Teknologi

- PHP Native 8.x (PDO, prepared statement, session)
- MySQL / MariaDB
- HTML, CSS (variabel CSS, tema lewat atribut `data-tema`)
- JavaScript (tanpa framework), Web Speech API

Antarmuka memakai warna sage, hijau tinta, putih hangat, dan aksen terakota.
Manrope digunakan untuk judul dan antarmuka; jenis huruf isi bacaan dapat
dipilih pengguna. Font antarmuka dan ilustrasi SVG disimpan lokal. Logo di
navigasi dan footer memakai komponen yang sama, termasuk ikon buku.
Tidak memakai Bootstrap, Tailwind, React, atau proses build.

### Penyempurnaan pengalaman baca

- Perpustakaan memiliki pencarian, kategori, estimasi waktu baca, dan
  tombol bookmark langsung dari kartu bacaan. Pencarian langsung terlihat tanpa
  pengantar panjang; daftar dibagi menjadi 12 bacaan per halaman. Menambahkan
  bookmark mempertahankan pencarian, kategori, dan halaman yang sedang dibuka.
- Panel kenyamanan menyediakan pilihan cepat **Lebih lapang** dan
  **Teks besar**, selain pengaturan rinci. Perubahan langsung ditampilkan;
  tombol simpan menyimpan preferensi ke akun tanpa memuat ulang halaman. Jika
  koneksi gagal, pengaturan tetap terlihat dan penyimpanan dapat dicoba ulang.
- Mode fokus menyorot satu paragraf dan menyediakan tombol paragraf sebelumnya
  serta berikutnya. Klik ganda atau dua ketukan cepat membacakan mulai dari
  paragraf yang dipilih hingga akhir teks. Tombol **Berhenti** mengikuti scroll
  di bilah atas selama suara berjalan. Rincian interaksi ada pada panduan di bawah.
- Di ponsel, pengaturan hadir sebagai panel yang dapat dibuka dan ditutup,
  dengan fokus keyboard tetap di dalam panel dan konten latar dinonaktifkan
  selama terbuka. Menu tetap berfungsi setelah perubahan orientasi layar.
- Teks pribadi memiliki penghitung kata, konfirmasi hapus, serta daftar
  bacaan yang hanya dapat diakses pemiliknya. Teks Saya mendahulukan daftar;
  formulir dibuka melalui Tambah teks dan tetap bisa digunakan tanpa JavaScript.
- Di ponsel, kartu Perpustakaan dan Bookmark memakai sampul ringkas agar judul
  lebih cepat terlihat. Pengaturan baca dibuka dari bilah atas; area pengaturan
  dapat digulir, sedangkan tombol simpan tetap terlihat pada desktop dan ponsel.
- Baris baru di dalam teks dipertahankan. Posisi baca dalam tab yang sama dapat
  dipulihkan ketika membuka kembali bacaan; penyimpanan browser bersifat opsional.
- Bacaan terakhir dalam sesi dapat dibuka kembali dari Perpustakaan. Editor
  mengingatkan jika pengguna meninggalkan perubahan yang belum disimpan.
- Login menampilkan panel pengantar dan ilustrasi pada desktop, serta formulir
  ringkas pada ponsel. Kredensial demo tidak ditampilkan di halaman login.
- Halaman admin menyediakan pencarian, pagination, pemeriksaan kategori ganda,
  dan aksi pengelolaan yang tetap tersedia pada ponsel.
- Menu aktif, pesan kosong, validasi, dan tampilan admin mengikuti sistem
  antarmuka yang sama. Font dan ilustrasi bekerja tanpa CDN.

## Panduan Instalasi dan Konfigurasi

Kebutuhan: XAMPP / Laragon (atau PHP 8+ dan MySQL/MariaDB), ekstensi PHP
`pdo_mysql` dan `mbstring`, serta browser modern. JavaScript diperlukan untuk
mode fokus, pratinjau pengaturan, dan baca nyaring.

1. **Salin proyek.** Letakkan folder `bacamudah/` di `htdocs` (XAMPP) atau `www` (Laragon).
2. **Instalasi baru.** Buka phpMyAdmin, pilih **Import**, lalu pilih `database.sql`. Berkas ini membuat ulang database `bacamudah` dengan enam tabel dan data contoh. Untuk database yang sudah berisi data, gunakan migrasi di bawah, bukan impor ulang.
3. **Atur koneksi.** Konfigurasi ada di `config/database.php`. Tersedia variabel lingkungan `BACA_DB_HOST`, `BACA_DB_PORT`, `BACA_DB_NAME`, `BACA_DB_USER`, dan `BACA_DB_PASS`. Bawaan lokal XAMPP: host `127.0.0.1`, port `3306`, database `bacamudah`, user `root`, password kosong.
4. **Pastikan folder `uploads/` bisa ditulis** oleh web server (di Linux: `chmod 775 uploads`).

### Akun demo

| Peran | Email | Password |
|-------|-------|----------|
| Admin | `admin@bacamudah.test` | `admin123` |
| Pengguna | `user@bacamudah.test` | `user12345` |

Akun demo hanya untuk uji lokal; kredensial tidak ditampilkan di halaman login.
Dalam mode produksi kedua akun demo ditolak, termasuk variasi penulisan email
yang dicocokkan database ke akun yang sama.

### Memperbarui database yang sudah ada

Jalankan sekali dengan PHP yang digunakan aplikasi:

```powershell
C:\xampp\php\php.exe scripts/migrate.php
```

Migrasi hanya menambah tabel `login_attempts`, tanpa menghapus akun atau bacaan.
Launcher `.local/start-web.ps1` menjalankan migrasi ini secara otomatis. Jika
memakai XAMPP tanpa CLI, jalankan isi `migrations/001-login-attempts.sql` pada
database `bacamudah` melalui tab SQL phpMyAdmin.

### Konfigurasi hosting publik

Mode bawaan ditujukan untuk pengembangan lokal. Di hosting, tetapkan variabel
lingkungan `BACA_ENV=production` dan kredensial `BACA_DB_*` milik akun database
khusus dengan hak minimum. Mode produksi menolak koneksi memakai root atau
password database kosong. Buat akun admin khusus sebelum beralih ke produksi.

Gunakan HTTPS agar cookie sesi mendapat atribut Secure. Jika memakai reverse
proxy, konfigurasi web server harus meneruskan keadaan HTTPS secara tepercaya.
Apache perlu mengizinkan aturan `.htaccess`; pada Nginx atur penolakan setara
untuk folder internal dan berkas SQL/konfigurasi. Router pratinjau lokal bukan
konfigurasi otomatis untuk server produksi.

### Membuat akun admin baru

Halaman daftar selalu membuat akun berperan pengguna (disengaja, supaya tidak ada yang bisa mendaftar sendiri sebagai admin). Untuk menjadikan sebuah akun admin, buka phpMyAdmin, tabel `users`, lalu ubah kolom `role` pada baris akun tersebut dari `user` menjadi `admin`.

## Panduan Menjalankan Aplikasi

1. Jalankan **Apache** dan **MySQL** di XAMPP/Laragon.
2. Buka `http://localhost/bacamudah/` di browser.
3. Login dengan akun demo, atau daftar akun baru.

### Menggunakan akun pengguna

1. Di **Perpustakaan**, cari judul atau pilih kategori, lalu tekan **Mulai membaca**.
2. Untuk teks sendiri, buka **Teks Saya**. Daftar bacaan tersimpan tampil lebih
   dahulu. Tekan **Tambah teks**, isi judul dan tempel teks, lalu tekan
   **Simpan & mulai membaca**. Bacaan tersimpan dapat dibuka, diubah, atau dihapus
   dari daftar tersebut.
3. Di halaman baca, gunakan **Pengaturan baca** untuk mengubah jenis huruf,
   ukuran, jarak huruf, jarak baris, dan warna halaman. Pada ponsel, buka panel
   melalui tombol **Pengaturan baca** di bilah atas.
4. Pilihan **Lebih lapang** dan **Teks besar** langsung mengubah tampilan.
   Tekan **Simpan pengaturan** agar pilihan diingat per akun. **Atur ulang**
   mengembalikan tampilan ke nilai awal; simpan jika ingin memakai nilai awal
   pada kunjungan berikutnya.
5. Tekan ikon bookmark pada kartu atau halaman baca untuk menyimpan bacaan.
   Buka menu **Bookmark** untuk melihatnya kembali. Menekan ikon aktif menghapus
   bookmark, bukan teks bacaannya.
6. Untuk keluar, buka menu akun di kanan atas dan pilih **Keluar akun**.

### Fokus dan baca nyaring

| Interaksi | Hasil |
|-----------|-------|
| Klik atau ketuk paragraf sekali | Menghentikan suara yang berjalan dan memfokuskan paragraf tersebut. |
| Klik paragraf dua kali dengan mouse | Membacakan mulai dari paragraf itu, lalu melanjutkan hingga akhir teks. |
| Ketuk dua kali dengan cepat di posisi yang sama | Memulai baca nyaring pada layar sentuh; gerakan menggulir, tekan lama, dan dua jari tidak memulai suara. |
| Tombol **Bacakan teks** | Membacakan dari paragraf yang sedang difokuskan, atau dari awal jika mode fokus belum aktif. |
| Tombol **Berhenti** di bilah atas atau **Hentikan bacaan** | Menghentikan suara dan mempertahankan fokus paragraf terakhir. |
| **Enter** atau **Spasi** saat paragraf mendapat fokus keyboard | Menghentikan suara yang berjalan dan memfokuskan paragraf tersebut. |
| **Ctrl+Enter** atau **Cmd+Enter** saat paragraf mendapat fokus keyboard | Membacakan mulai dari paragraf tersebut. |
| **Esc** pada halaman baca | Menghentikan suara dan keluar dari mode fokus. Jika panel pengaturan atau navigasi ponsel sedang terbuka, hanya menutup panel/menu tersebut. |

Tombol **Berhenti** hanya muncul selama baca nyaring aktif dan tetap terjangkau
saat halaman digulir. Menutup panel pengaturan tidak menghentikan suara atau
mode fokus. Pada ponsel, tombol simpan tetap terlihat saat isi panel digulir.

Baca nyaring memakai Web Speech API dan suara yang tersedia di browser atau
sistem perangkat. Aplikasi mengutamakan suara bahasa Indonesia. Jika daftar
suara yang tersedia tidak memuat bahasa Indonesia, aplikasi memberi pesan dan
memakai suara bawaan perangkat. Jika browser tidak mendukung baca
nyaring, tombolnya dinonaktifkan; mode fokus dan pengaturan tetap dapat digunakan.

### Menggunakan akun admin

1. Login sebagai admin. Navigasi hanya berisi **Perpustakaan**, **Kelola Teks**,
   dan **Kategori**.
2. Gunakan **Perpustakaan** untuk melihat dan membaca teks umum.
3. Buka **Kelola Teks** untuk menambah, mengubah, atau menghapus teks umum,
   memilih kategori, dan mengunggah thumbnail opsional. Format gambar yang
   diterima: JPG, PNG, atau WEBP, maksimal 2 MB.
4. Buka **Kategori** untuk menambah, mengubah, atau menghapus kategori.
   Menghapus kategori tidak menghapus teks di dalamnya; teks menjadi tanpa kategori.

### Penggunaan tanpa JavaScript

Login, daftar akun, pencarian, bookmark, pengelolaan teks, dan penyimpanan
pengaturan melalui formulir tetap dapat dipakai. Formulir **Tambahkan teks**
memakai elemen `details` yang dapat dibuka langsung. Mode fokus, baca nyaring,
penghitung kata langsung, dan pratinjau pengaturan memerlukan JavaScript.

### Alternatif pratinjau lokal yang sudah disiapkan

```powershell
powershell -ExecutionPolicy Bypass -File .local/start-web.ps1
```

Buka `http://127.0.0.1:8000/`. Launcher ini disiapkan untuk lingkungan Windows
lokal dengan PHP di `C:\php84` dan MariaDB di `C:\xampp\mysql`.
Jika port 3306 belum dipakai, launcher menjalankan MariaDB dengan data di
`.local/runtime/mysql/`. Jika port tersebut sudah dipakai, launcher memakai
server database yang sedang berjalan. Database diimpor hanya jika belum berisi
tabel, lalu migrasi dijalankan tanpa menghapus data lama.

Untuk menghentikan proses yang dibuat launcher:

```powershell
powershell -ExecutionPolicy Bypass -File .local/stop-web.ps1
```

Alamat `http://localhost/bacamudah/` menggunakan Apache XAMPP, sedangkan
`http://127.0.0.1:8000/` menggunakan server PHP dari launcher. Gunakan salah satu
alur yang sesuai lingkunganmu. Untuk instalasi XAMPP/Laragon lain, ikuti panduan
instalasi biasa di atas.

Node.js dan Playwright hanya dipakai untuk verifikasi pengembangan di
`.local/`; keduanya tidak diperlukan untuk menjalankan aplikasi PHP.

## Struktur Folder

```
bacamudah/
├── config/database.php       # koneksi PDO, session, fungsi bantu
├── includes/                 # header, footer, ikon, logo, kartu bacaan
├── assets/css/style.css      # tampilan antarmuka dan tiga tema baca
├── assets/js/app.js          # navigasi, panel, formulir, konfirmasi hapus
├── assets/js/reader.js       # pengaturan, fokus, suara, interaksi sentuh
├── assets/fonts/             # font lokal dan lisensinya
├── assets/images/            # favicon dan ilustrasi buku
├── uploads/                  # thumbnail teks umum
├── admin/texts.php, categories.php
├── register.php, login.php, logout.php
├── index.php, read.php, bookmarks.php, bookmark_aksi.php
├── teks-saya.php, teks-edit.php, teks-hapus.php
├── simpan_pengaturan.php
├── scripts/migrate.php       # migrasi database yang sudah ada
├── migrations/               # SQL migrasi
├── docs/                     # panduan agen dan keputusan desain
├── .local/                   # launcher dan alat verifikasi pengembangan
└── database.sql              # skema dan data demo instalasi baru
```

## Aksesibilitas dan Keamanan

Komponen tampilan bersama berada di `includes/header.php`, `includes/footer.php`,
dan `includes/ui.php`. Seluruh gaya ditulis di
`assets/css/style.css`; interaksi menu/formulir di `assets/js/app.js`, dan
alat bantu pembaca di `assets/js/reader.js`. Lisensi font ada di `assets/fonts/`.
Keputusan desain dicatat di `docs/design/ruang-baca.md`.

- Rentang pengaturan: ukuran huruf 16 sampai 32 px, jarak huruf 0 sampai 0,12 em, jarak baris 1,5 sampai 2,0; lebar baris sekitar 65 karakter. Angka diperiksa ulang di server.
- Tersedia tema normal, krem, dan kontras tinggi. Warna teks utama diutamakan
  agar mudah dibaca; audit otomatis WCAG A/AA mencakup halaman dan keadaan utama.
  Audit otomatis tidak menggantikan uji dengan pengguna atau pembaca layar.
- Dapat dipakai dengan keyboard, ada tautan "Lewati ke konten", `lang="id"`, label teks pada semua isian.
- Password disimpan dengan `password_hash`; query memakai prepared statement; output memakai `htmlspecialchars`; form memakai token CSRF.
- Cookie sesi memakai HttpOnly, SameSite=Lax, dan strict mode; atribut Secure
  aktif melalui HTTPS. Percobaan masuk dibatasi lima kegagalan per akun/IP
  dalam lima menit dan tetap berlaku pada sesi browser yang baru.
- Isian berbentuk array atau UTF-8 tidak valid ditolak sebelum pemrosesan.
  Detail galat masuk log server, bukan halaman pengguna. Penyimpanan dengan
  sesi berakhir mengembalikan status 401 dan pesan yang dapat ditindaklanjuti.
- Halaman admin diperiksa perannya di baris paling atas; edit dan hapus teks pribadi selalu menyertakan `user_id` dari session.
- Upload thumbnail divalidasi (tipe gambar asli, maks. 2 MB, nama acak) dan folder `uploads/` melarang eksekusi skrip.

### Verifikasi pengembangan

Verifikasi otomatis terbaru pada lingkungan pengembangan ini berada di
`.local/runtime/user-evaluation/`. Skrip memerlukan Node.js, `playwright-core`
dan `axe-core` di `.local/browser/node_modules/`, serta lokasi executable
Chromium yang sesuai dengan konfigurasi skrip. Dependensi ini hanya untuk
pengembangan, bukan untuk menjalankan aplikasi PHP.

Dengan launcher aktif, jalankan dari root proyek:

```powershell
$env:BACA_TEST_BASE = 'http://127.0.0.1:8000/'
C:\php84\php.exe .local/runtime/user-evaluation/fixture.php create
node .local/runtime/user-evaluation/revision.cjs
node .local/runtime/user-evaluation/double-click.cjs
node .local/runtime/user-evaluation/touch.cjs
C:\php84\php.exe .local/runtime/user-evaluation/fixture.php cleanup
```

- `revision.cjs`: alur teks pribadi, pencarian/filter, bookmark, pengaturan,
  navigasi, tampilan ponsel, audit aksesibilitas, dan penggunaan tanpa JavaScript.
- `double-click.cjs`: klik tunggal/ganda, keyboard, tombol berhenti, urutan
  paragraf, serta penanganan galat penyedia suara.
- `touch.cjs`: ketukan sentuh, posisi teks, gulir, tekan lama, dua jari,
  orientasi layar, dan aktivasi baca nyaring melalui ketukan pengguna.

Skrip memakai akun dan bacaan sementara. Jalankan perintah `cleanup` setelah
selesai, termasuk jika pengujian terhenti. Laporan JSON dan tangkapan layar
tersimpan di `.local/runtime/user-evaluation/`.

Pengujian baca nyaring memakai simulasi Web Speech API, sedangkan gestur sentuh
memakai emulasi ponsel. Audio di perangkat fisik, pengalaman pembaca layar, serta
kenyamanan pengguna tetap perlu diuji secara manual. Ukuran layar ponsel saja
tidak menggantikan pengujian ketukan sentuh.

## Batasan

- Teks dari gambar atau hasil scan tidak bisa dipakai karena pengenalan huruf (OCR) di luar cakupan proyek.
- Kualitas dan bahasa suara baca nyaring bergantung pada perangkat dan browser.

## Tim

| Nama | NIM | Peran |
|------|-----|-------|
| (isi) | (isi) | (isi) |
| (isi) | (isi) | (isi) |
| (isi) | (isi) | (isi) |
