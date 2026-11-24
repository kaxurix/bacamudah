# Baca Mudah

Proyek PHP native 8.x, MySQL/MariaDB, HTML, CSS, dan JavaScript tanpa framework.
Baca README.md untuk fitur, struktur, dan akun demo. Gunakan bahasa Indonesia
untuk komunikasi dan dokumentasi proyek.

## Menjalankan aplikasi lokal

Jalankan `powershell -ExecutionPolicy Bypass -File .local/start-web.ps1`.
Buka http://127.0.0.1:8000/. Data pratinjau disimpan di `.local/runtime/mysql/`.
Gunakan `.local/stop-web.ps1` untuk menghentikan proses yang dibuat launcher.

## Agent skills

### Issue tracker

Issue dan spesifikasi disimpan sebagai Markdown lokal di `.scratch/<fitur>/`.
Lihat `docs/agents/issue-tracker.md`.

### Triage labels

Gunakan label bawaan `needs-triage`, `needs-info`, `ready-for-agent`,
`ready-for-human`, dan `wontfix`. Lihat `docs/agents/triage-labels.md`.

### Domain docs

Gunakan satu konteks: `GLOSSARY.md` dan `docs/adr/` di root proyek.
Baca `docs/agents/domain.md`. Berkas domain dibuat saat diperlukan.

### Skill yang terpasang

Semua direktori skill lengkap dari mattpocock/skills dan obra/superpowers
tersedia di `.agent-skills/`. Baca `.agent-skills/README.md` untuk daftar nama,
deskripsi, dan tautan SKILL.md, lalu buka SKILL.md yang sesuai sebelum
menggunakannya. Nama skill yang diminta pengguna merujuk pada direktori
`.agent-skills/<nama>/SKILL.md`.

Pada awal sesi, baca `.agent-skills/using-superpowers/SKILL.md` dan referensi
Codex yang ditunjuknya. Gunakan skill sesuai tugas; pemasangan semua skill
tidak berarti mengeksekusi semua skrip atau mengubah stack aplikasi.

Folder `.agents` dan `.codex` dilindungi pada sesi instalasi; pemasangan
proyek ini memakai pointer AGENTS.md, bukan registrasi menu skill native.
Manifest sumber, revisi, dan hash berkas ada di `.agent-skills/manifest.json`.
Repositori sumber lengkap disimpan di `.local/skill-sources/` untuk referensi
di luar direktori skill. Ikuti instruksi sistem dan pengguna jika panduan
skill tidak sesuai dengan izin atau kemampuan sesi yang sebenarnya.

Skill Matt dalam `in-progress` bersifat eksperimental; skill khusus Claude
Code atau TypeScript hanya digunakan jika tugas dan lingkungan sesuai.
Alur Git/worktree memerlukan repositori Git; jangan membuat remote atau
mempublikasikan sesuatu hanya karena suatu skill menyarankannya.
