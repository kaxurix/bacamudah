# Baca Mudah — Ruang baca editorial

Permintaan pengguna: tingkatkan pengalaman dan visual Baca Mudah secara
menyeluruh agar matang untuk demonstrasi inovasi tugas praktikum Pemweb.
Tetap gunakan PHP native, CSS biasa, dan JavaScript tanpa framework.

## Arah yang dipilih

- Tujuan: membantu pembaca menemukan teks dan menyesuaikan cara membacanya.
- Suasana: hangat, tenang, teliti; seperti perpustakaan kecil yang dirawat.
- Struktur: navigasi samping pada desktop, navigasi ringkas pada ponsel;
  area kerja luas dengan hierarki konten yang jelas.
- Tipografi: Manrope untuk judul dan seluruh antarmuka agar konsisten dengan
  halaman login; font isi bacaan tetap mengikuti pilihan pengguna.
- Warna: kertas hangat, hijau tinta, aksen tanah liat, dengan tema pembaca
  normal, krem, serta kontras tinggi yang tetap fungsional.
- Pembeda: sampul ilustratif geometris dengan motif buku, pertumbuhan,
  dan jendela; dibuat sebagai SVG/CSS lokal, tidak memerlukan layanan gambar.
- Gerak: hanya transisi keadaan singkat, menghormati reduced motion.

Arah ini merupakan pilihan implementasi untuk memenuhi brief pengguna.
Tampilan dashboard penuh angka tidak dipilih karena informasi utama adalah
bacaan dan kenyamanan membaca; halaman promosi panjang akan menghambat tugas.

## Cakupan

1. Shell antarmuka, navigasi aktif, footer, ikon dan sistem CSS lokal.
2. Login/registrasi dengan presentasi produk dan pesan validasi yang jelas.
3. Perpustakaan dengan pencarian, kategori, estimasi baca, serta sampul.
4. Pembaca dengan penanda kemajuan, mode fokus, suara, dan pengaturan yang
   mudah dijangkau melalui ponsel maupun desktop.
5. Teks pribadi, bookmark, formulir edit dan halaman admin yang konsisten.
6. Penyempurnaan interaksi vanilla JS, keadaan kosong, dan keyboard.

Konten dan statistik hanya berasal dari data sebenarnya. Pertahankan CSRF,
prepared statements, pemeriksaan kepemilikan, password hashing, dan pola
POST/redirect/GET. Tidak mengubah proyek menjadi framework atau menambah
proses build. Tidak mengklaim manfaat medis atau penghargaan yang belum ada.

## Validasi

Lint PHP; pemeriksaan sintaks JS; browser desktop dan ponsel; daftar/login,
pencarian/filter, simpan teks pribadi, bookmark, pengaturan tersimpan, mode
fokus, akses admin, logout, empty/error states, tanpa overflow horizontal.
Periksa tangkapan layar dan kontras warna serta navigasi keyboard.
