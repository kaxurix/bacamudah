# Baca Mudah: utamakan kegiatan membaca

Status: ready-for-agent

Pengguna telah meminta pelaksanaan perbaikan dari review 24 November 2026,
termasuk menghapus bagian yang tidak penting. Tujuan tetap membantu pembaca
menempel teks, mengatur tampilannya dan membaca tanpa kehilangan konteks.

Pilihan implementasi: perbaiki aplikasi PHP native yang ada. Pertahankan warna
kertas/hijau, sampul ilustratif dan judul serif; ringkas pengantar dekoratif,
duplikasi filter dan catatan motivasi. Tidak mengganti stack atau menerbitkan.

Hasil yang diharapkan:
- Login langsung menampilkan formulir; kredensial demo tidak dikirim ke browser.
- Perpustakaan mengutamakan pencarian, daftar bacaan dan tambah teks sendiri.
- Teks antarmuka utama minimal 14 px; input minimal 16 px; dukung 320 px.
- Simpan pengaturan tanpa reload, dengan pesan sukses/gagal; fallback POST tetap ada.
- Baris baru dipertahankan; navigasi fokus terjangkau; panel modal mengisolasi latar.
- Sampul konsisten pada Bookmark dan konteks pencarian tidak hilang saat bookmark.
- Admin mempunyai pencarian dan daftar responsif, serta validasi kategori duplikat.
- Tipe input diperiksa, sesi diperkeras, percobaan login dibatasi secara persisten.
- Konfigurasi produksi memblokir akun demo, memerlukan kredensial DB khusus dan
  tidak membocorkan galat. Data yang sudah ada dipertahankan.

Perbaikan dilakukan langsung dalam sesi ini sesuai permintaan pengguna.
Prioritas adalah fungsi inti dan kemudahan, tanpa fitur tambahan seperti OCR.
