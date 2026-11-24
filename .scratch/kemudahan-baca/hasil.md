# Hasil perbaikan Baca Mudah

Tanggal: 24 November 2026. Status: selesai.

Fokus antarmuka kini pada mencari, membawa teks sendiri, dan membaca. Login
langsung menampilkan formulir. Pengantar besar, filter kategori ganda,
motivasi di sidebar, ilustrasi pengantar, dan penomoran dekoratif dihapus.
Warna kertas/hijau, font lokal, dan sampul ilustratif tetap menjadi identitas.
Implementasi tetap PHP native, CSS biasa, dan JavaScript tanpa framework.
Tidak memakai referensi komponen atau layanan gambar eksternal.

Perbaikan pembaca: kontrol lebih besar, newline dipertahankan, navigasi fokus
mengikuti layar, modal mengisolasi latar dan pulih setelah rotasi, Escape tidak
mematikan fokus saat menutup modal. Pengaturan tersimpan tanpa reload, kegagalan
bisa dicoba ulang, dan fallback form tanpa JavaScript tetap berjalan. Posisi
bacaan dapat dipulihkan di tab yang sama. Editor mengingatkan perubahan yang
belum disimpan.

Perbaikan koleksi/admin: sampul Bookmark konsisten, konteks pencarian dan halaman
dipertahankan setelah bookmark, daftar dibatasi 12 per halaman, admin memiliki
pencarian, kategori duplikat ditolak, dan aksi daftar ponsel terlihat tanpa
scroll horizontal. Pemeriksaan kategori dan penulisannya diserialisasi.

Keamanan: validasi tipe/UTF-8, pesan galat aman, cookie HttpOnly/SameSite/strict,
limiter login persisten dengan counter atomik, email akun kanonis, dan dukungan
login akun lama dengan password panjang. Kredensial demo dihapus dari HTML;
mode produksi menolak akun demo serta konfigurasi DB root/password kosong.
Database lokal mendapat tabel login_attempts melalui migrasi idempoten yang
tidak menghapus data lama. Mode produksi belum diaktifkan pada pratinjau lokal.

Validasi:
- 21/21 skenario pada `.local/test-improvements.cjs`.
- 6/6 regresi reviewer pada `.local/test-review-findings.cjs`; keenamnya
  disaksikan gagal sebelum perbaikan, lalu lulus setelah perbaikan.
- 7/7 kelompok alur akhir pada `.local/test-final-flows.cjs`, termasuk CRUD,
  konfirmasi hapus, upload, pagination, CSRF, logout, dan ukuran 320/390 px.
- 18 keadaan audit axe A/AA: 9 pada pengujian utama dan 9 pada audit tambahan;
  tidak ditemukan pelanggaran otomatis yang terdeteksi.
- Lint PHP dan pemeriksaan sintaks JavaScript dijalankan.
- Screenshot desktop/ponsel diperiksa secara visual.

Bukti JSON dan screenshot berada di `.local/runtime/improvements/`.
Cadangan sumber sebelum perbaikan: `.local/backups/sebelum-perbaikan-20261124.zip`.
Panduan migrasi dan konfigurasi hosting diperbarui di README.md.

Batas verifikasi: Chromium dan viewport emulasi; belum diuji dengan perangkat
audio Indonesia nyata, Safari/iOS, pembaca layar nyata, atau pengguna sasaran.
Audit otomatis bukan sertifikasi WCAG. Konkurensi multiworker belum diuji pada
server produksi; reservasi limiter menggunakan satu upsert atomik MySQL.
