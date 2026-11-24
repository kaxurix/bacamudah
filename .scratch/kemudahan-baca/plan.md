# Rencana implementasi kemudahan membaca

Spec: spec.md. Pelaksanaan langsung, tanpa Git (direktori bukan repositori).
Cadangan sumber: .local/backups/sebelum-perbaikan-20261124.zip.

- [x] Uji regresi: reproduksi input array, cookie, newline, bookmark, simpan
  tanpa reload, fokus mobile, isolasi modal dan ukuran kontrol.
- [x] Keamanan/form: helper string/int menolak tipe tidak sesuai, konfigurasi
  lingkungan, sesi HttpOnly/strict/SameSite, penanganan galat dan limiter DB.
  Migrasi menambah satu tabel tanpa menghapus data.
- [x] Pembaca: JSON untuk simpan pengaturan dengan fallback POST, pembatasan
  akses tetap sama, panel inert, navigasi fokus tetap terjangkau dan whitespace.
- [x] Sederhanakan login, perpustakaan dan shell; perbesar tipografi. Bookmark
  mempertahankan sampul dan filter. Admin: pencarian, duplikasi, tabel responsif.
- [x] Jalankan pengujian baru dan regresi fungsi, audit axe/screenshot desktop
  dan mobile. Perbarui README dan hasil pelaksanaan; bersihkan fixture.

Fokus verifikasi: input array, jaringan gagal saat menyimpan, font/tema besar
di 320 px, keyboard dalam modal, serta edit/hapus teks milik akun lain.

Antarmuka bersama: config/database.php memasok input_string()/input_int(),
mode_produksi(), dan koneksi PDO; simpan_pengaturan.php mengembalikan JSON
berisi sukses/pesan untuk Accept application/json, redirect untuk form biasa.

Keputusan akhir: tidak mengganti framework atau memublikasikan aplikasi.
Pemeriksaan reviewer menambahkan regresi untuk Escape di modal, rotasi layar,
password akun lama, variasi email, dan validasi sebelum mutasi bookmark.
Limiter memakai reservasi counter atomik sebelum pemeriksaan password.
