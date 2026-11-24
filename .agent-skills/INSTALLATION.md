# Instalasi skill Baca Mudah

54 direktori skill lengkap telah disalin pada 23 November 2026, termasuk
189 berkas yang cocok dengan SHA-256 sumber. Inventaris dan hash terperinci
ada di [manifest.json](manifest.json); daftar skill ada di [README.md](README.md).

| Sumber | Revisi yang dipasang | Jumlah |
| --- | --- | --- |
| https://github.com/mattpocock/skills | abeeaf95660ba9e8b7c753eb38f441ca707a8823 | 39 |
| https://github.com/obra/superpowers | 22f0b463e7a5e579904dd6211a520321035526f7 | 15 |

Instalasi memakai fungsi validasi dan penyalinan dari skill-installer bawaan
Codex terhadap arsip GitHub pada revisi tersebut. Sumber lengkap beserta
lisensinya dipertahankan di `.local/skill-sources/`.

## Lokasi dan aktivasi

Paket berada di `.agent-skills/<nama>/`, khusus proyek ini. Lokasi native
`.agents/skills`, `.codex`, dan lokasi global berada di luar izin tulis sesi.
Karena itu, paket belum didaftarkan ke menu skill native. `AGENTS.md`
yang telah diterapkan menautkan inventaris sehingga agen dapat membuka
skill yang relevan secara manual, termasuk pada giliran berikutnya.

Konfigurasi Matt Pocock tersedia untuk ditinjau di `.local/setup-draft/`:
issue Markdown lokal, label bawaan, satu konteks dokumentasi, dan `AGENTS.md`.
Konfigurasi telah diterapkan ke `AGENTS.md` dan `docs/agents/` setelah
pengguna menyetujui draf dan meminta pengembangan UI pada giliran berikutnya.

## Verifikasi dan batasan

- Semua 54 SKILL.md dan 189 berkas pendukung cocok dengan sumber.
- Referensi relatif diperiksa; tautan contoh dalam template bukan dependensi
  berkas yang harus ada. Tidak ada berkas pendukung sumber yang dihilangkan.
- Skrip setiap skill tidak dieksekusi secara massal. Skill khusus TypeScript,
  Claude Code, layanan eksternal, atau Git memerlukan lingkungan yang sesuai.
- Delapan skill Matt berada di `in-progress` dan bersifat eksperimental;
  empat lainnya berada di `misc` dan tidak termasuk paket utama plugin.
- Bash tersedia melalui Git for Windows. Proyek belum diinisialisasi sebagai
  repositori Git, sehingga alur cabang, worktree, commit, dan PR belum siap.
- Tidak ada hook global, pengaturan izin, atau konfigurasi model yang diubah.

## Pratinjau web

Jalankan `powershell -ExecutionPolicy Bypass -File .local/start-web.ps1`
dan buka http://127.0.0.1:8000/. Launcher memakai PHP 8.4 dan MariaDB dari
XAMPP dengan data terpisah di `.local/runtime/mysql/`. Impor awal hanya
dilakukan jika database bacamudah belum berisi tabel.

Pemeriksaan browser berhasil untuk login pengguna dan admin, perpustakaan,
halaman baca, halaman pengelolaan teks admin, dan lebar layar ponsel.
Hasilnya berada di `.local/runtime/preview-check.json`.

Gunakan `powershell -ExecutionPolicy Bypass -File .local/stop-web.ps1`
untuk menghentikan proses milik launcher tanpa menghapus data.
