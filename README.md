# Office AI Agent —INaAI Project

Aplikasi web internal untuk **manajemen proyek, tugas pegawai, dan asisten AI perkantoran**.
Dibangun dengan Laravel 12 + Blade, dengan tampilan dashboard bergaya "INaAI" (sidebar
ringkas, kartu KPI, papan Kanban, dan timeline Gantt sederhana).

Aplikasi ini punya dua sisi:

| Sisi                        | Untuk siapa   | Isinya                                                                               |
| --------------------------- | ------------- | ------------------------------------------------------------------------------------ |
| **Dashboard Pegawai** | user biasa    | tugas milik sendiri, ubah status, unggah bukti kerja, chat dengan AI                 |
| **Dashboard Admin**   | administrator | ringkasan seluruh proyek & tugas, kelola pengguna, laporan, ekspor CSV, riwayat chat |

---

## Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Teknologi](#teknologi)
- [Kebutuhan Sistem](#kebutuhan-sistem)
- [Cara Menjalankan (Quick Start)](#cara-menjalankan-quick-start)
- [Konfigurasi `.env`](#konfigurasi-env)
- [Akun Bawaan (Hasil Seeder)](#akun-bawaan-hasil-seeder)
- [Struktur Proyek](#struktur-proyek)
- [Daftar Rute](#daftar-rute)
- [Perintah Harian](#perintah-harian)
- [Alur Kerja Git](#alur-kerja-git)
- [Konvensi Penulisan](#konvensi-penulisan)
- [Troubleshooting](#troubleshooting)
- [Deployment](#deployment)

---

## Fitur Utama

### Manajemen Proyek & Tugas

- **Proyek** punya nama, warna, rentang tanggal, penanda *berisiko*, dan persentase progres
  yang dihitung otomatis dari tugas di dalamnya.
- **Tugas** memiliki 5 status: `To Do`, `In Progress`, `Review`, `Blocked`, `Done`,
  plus prioritas (Tinggi / Sedang / Rendah), pemilik, dan reviewer.
- **Bukti kerja (evidence)**: unggah PDF, Word, Excel, atau gambar (maks. 10 MB per file).
  File hanya bisa diakses pemilik tugas, reviewer, dan admin.
- **Papan Kanban** dan **timeline Gantt** dihasilkan dari data tugas
  (lihat `app/Services/TaskMetrics.php`).

### Dashboard Admin

- Kartu KPI: total proyek, total tugas, kontributor aktif, tugas selesai.
- Overview per proyek dan per pegawai lengkap dengan bar progres bertumpuk.
- Aktivitas terbaru dan daftar proyek berisiko.
- **Laporan** dengan filter rentang waktu (7 hari, 30 hari, bulan ini, kuartal ini, semua)
  serta **ekspor CSV** (sudah ber-BOM UTF-8 supaya rapi dibuka di Excel).
- Kelola pengguna: pencarian, filter, dan aktif/nonaktifkan akun.

### Komponen UI Global

- `data-select` pada `<select>` → dropdown pencarian bergaya select2 (dengan titik warna
  opsional lewat `data-color` pada `<option>`).
- `data-upload` pada `<input type="file">` → card upload drag & drop lengkap dengan
  daftar file (ikon per jenis berkas, jenis + ukuran), hapus per file, dan validasi
  ukuran/format di sisi browser.
- `data-datepicker`, `data-timepicker`, `data-datetimepicker`, `data-daterange` pada
  `<input>` → pemilih tanggal/jam berbahasa Indonesia. Input asli menjadi penampung
  nilai mesin (`Y-m-d`, `H:i`, `Y-m-d H:i`), yang terlihat adalah kotak baca-saja
  berformat lokal. Batas opsional: `data-min`, `data-max`, `data-step` (menit).
- `data-timeline` pada sebuah card → timeline/gantt dengan filter rentang tampilan
  (Hari Ini s/d 1 Tahun); datanya lewat `<script type="application/json" data-tl-data>`.
- `data-confirm` pada `<form>`/`<a>`/`<button>` → popup dialog konfirmasi
  (`data-confirm-judul`, `-teks`, `-ok`, `-jenis`), atau `InaaiDialog.konfirmasi({...})`
  dari JavaScript.
- Semuanya otomatis aktif di semua halaman yang memakai layout `user`/`admin`.

Warna status tugas berasal dari satu sumber, `App\Models\Task::warnaStatus()`, dan
dicerminkan sebagai variabel CSS `--st-*` supaya titik kanban, badge, bar timeline,
dan grafik progres selalu memakai warna yang sama.

### Asisten AI

- Percakapan berbasis `AgentOrchestrator` (`app/Services/Agent/`) dengan dukungan
  *tools* (query database, API eksternal, MCP) dan *context engine*.
- Riwayat percakapan tersimpan dan bisa ditinjau admin.
- Model LLM dapat diganti lewat `.env` (kompatibel dengan endpoint bergaya OpenAI
  Chat Completions, mis. OpenAI atau x.ai).

---

## Teknologi

| Komponen   | Versi / Pilihan                                                            |
| ---------- | -------------------------------------------------------------------------- |
| PHP        | 8.2+                                                                       |
| Framework  | Laravel 12                                                                 |
| Tampilan   | Blade + CSS kustom (`resources/views/partials/inaai-style.blade.php`)    |
| Database   | PostgreSQL 18.4 (image`local/postgres-postgis:18.4-postgis3`)            |
| Build aset | Vite 7 + Tailwind 4 (opsional, hanya untuk halaman berbasis React/Inertia) |
| Font       | Plus Jakarta Sans (Google Fonts)                                           |

> **Catatan:** halaman dashboard hasil redesign memakai CSS inline dan **tidak memerlukan
> `npm run build`**. Vite hanya dibutuhkan bila Anda mengembangkan halaman Inertia/React
> di `resources/js/`.

---

## Kebutuhan Sistem

- PHP **8.2** atau lebih baru, beserta ekstensi standar Laravel (`pdo_pgsql`, `mbstring`, `openssl`, `fileinfo`).
- Composer 2.x
- PostgreSQL 16+ (disarankan lewat Docker)
- Node.js 20+ dan npm (opsional, untuk aset Vite)

---

## Cara Menjalankan (Quick Start)

### 1. Siapkan database

Aplikasi memakai PostgreSQL yang berjalan di container `postgres18-postgis`
(`127.0.0.1:5432`). Buat role dan database khusus aplikasi:

```bash
# ganti <password> dengan password pilihan Anda
docker exec postgres18-postgis \
  psql -U postgres -c "CREATE ROLE inaai WITH LOGIN PASSWORD '<password>';"

docker exec postgres18-postgis \
  psql -U postgres -c "CREATE DATABASE work OWNER inaai ENCODING 'UTF8';"

docker exec postgres18-postgis \
  psql -U postgres -d work -c "ALTER SCHEMA public OWNER TO inaai;"
```

Baris terakhir penting: sejak PostgreSQL 15, role biasa tidak lagi otomatis boleh
membuat tabel di schema `public`, sehingga migration akan gagal tanpa itu.

Kalau Anda memakai PostgreSQL sendiri, cukup siapkan database kosong beserta
pemiliknya lalu sesuaikan `.env` aplikasi.

### 2. Siapkan aplikasi

```bash
cd /Users/buanamac-1/Projects/Office-AI-Agent/Apps/office-ai-agent

composer install
cp .env.example .env
php artisan key:generate
```

Buka `.env`, sesuaikan bagian `DB_*` (lihat [Konfigurasi `.env`](#konfigurasi-env)).

### 3. Migrasi + data contoh

```bash
php artisan migrate --seed
php artisan storage:link      # agar bukti kerja bisa diakses lewat browser
```

### 4. Jalankan server

```bash
php artisan serve --port=8001
```

Buka [http://127.0.0.1:8001](http://127.0.0.1:8001) lalu masuk memakai salah satu
[akun bawaan](#akun-bawaan-hasil-seeder).

<details>
<summary>Jalur singkat: satu perintah</summary>

```bash
composer run setup   # install, .env, key, migrate, npm install, npm run build
php artisan serve --port=8001
```

</details>

<details>
<summary>Pengguna Windows</summary>

Jalankan `setup.bat` — skrip ini melakukan `composer install`, `key:generate`,
`migrate`, `db:seed`, dan `storage:link` secara berurutan.

</details>

---

## Konfigurasi `.env`

Bagian yang wajib diisi:

```dotenv
APP_NAME="Office AI Agent"
APP_URL=http://localhost:8001
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

# Database — sesuaikan dengan PostgreSQL Anda
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=work
DB_USERNAME=inaai
DB_PASSWORD="<password>"

# Agent / LLM
AI_API_KEY=
AI_API_URL=https://api.openai.com/v1/chat/completions
AI_MODEL=gpt-4
```

Penjelasan variabel AI (dibaca di `config/services.php`):

| Variabel       | Fungsi                                                                                |
| -------------- | ------------------------------------------------------------------------------------- |
| `AI_API_KEY` | API key penyedia LLM.**Kosongkan berarti fitur chat AI tidak akan menjawab.**   |
| `AI_API_URL` | Endpoint chat completions. Contoh alternatif:`https://api.x.ai/v1/chat/completions` |
| `AI_MODEL`   | Nama model, mis.`gpt-4` atau `grok-4`                                             |

> **Jangan pernah commit file `.env`.** File tersebut sudah masuk `.gitignore`;
> gunakan `.env.example` sebagai template bersama tim.

---

## Akun Bawaan (Hasil Seeder)

Dijalankan oleh `php artisan db:seed`.

| Peran         | Email                                                                                                                                                     | Password        |
| ------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------- |
| Administrator | `admin@office.com`                                                                                                                                      | `admin123`    |
| Pegawai       | `sari@inaai.id`, `andi@inaai.id`, `rina@inaai.id`, `dimas@inaai.id`, `maya@inaai.id`, `bagus@inaai.id`, `nadia@inaai.id`, `reza@inaai.id` | `password123` |

Seeder juga membuat 4 divisi (AI, Platform, Business Analyst, Tech Delivery),
6 proyek contoh, dan puluhan tugas dengan status beragam sehingga dashboard langsung terisi.

> ⚠️ Kredensial di atas hanya untuk pengembangan lokal. **Ganti sebelum dipakai di produksi.**

---

## Struktur Proyek

```
app/
├── Http/Controllers/
│   ├── Admin/
│   │   ├── AdminDashboardController.php   # dashboard, users, laporan, ekspor CSV
│   │   └── ProjectAdminController.php     # CRUD proyek + assign tugas
│   ├── Auth/                              # login & register
│   ├── ChatController.php                 # percakapan dengan agent AI
│   ├── DashboardController.php            # dashboard pegawai
│   └── TaskController.php                 # tugas + unggah bukti kerja
├── Models/                                # Project, Task, TaskEvidence, User, Role, ...
└── Services/
    ├── AIService.php                      # pemanggil API LLM sederhana
    ├── TaskMetrics.php                    # ringkasan status, Kanban, posisi bar Gantt
    └── Agent/                             # orchestrator, context engine, tools

database/
├── migrations/                            # skema: roles, departments, projects, tasks, ...
└── seeders/                               # data contoh siap pakai

resources/views/
├── layouts/{admin,user}.blade.php         # kerangka halaman (sidebar + topbar)
├── partials/
│   ├── inaai-style.blade.php              # design token & seluruh CSS
│   ├── {admin,user}-sidebar.blade.php
│   └── modal-assign.blade.php, pager.blade.php, th-sort.blade.php
├── admin/                                 # dashboard, proyek, laporan, users, ...
└── pekerjaan.blade.php, chat.blade.php, dashboard.blade.php

public/js/
├── inaai-table.js                         # helper sort + paginasi tabel
├── inaai-select.js                        # komponen select global (gaya select2)
└── inaai-upload.js                        # komponen card upload (drag & drop)
```

---

## Daftar Rute

### Pegawai (perlu login)

| Method       | URI                                          | Keterangan                                      |
| ------------ | -------------------------------------------- | ----------------------------------------------- |
| `GET`      | `/dashboard`                               | Ringkasan pribadi                               |
| `GET`      | `/pekerjaan`                               | Daftar tugas + Kanban + Gantt                   |
| `POST`     | `/tasks`                                   | Buat tugas (bisa sekaligus membuat proyek baru) |
| `GET`      | `/tasks/{task}`                            | Detail tugas (JSON, dipakai modal)              |
| `PATCH`    | `/tasks/{task}/status`                     | Ubah status tugas                               |
| `GET`      | `/evidence/{evidence}`                     | Unduh/lihat bukti kerja                         |
| `POST`     | `/conversations/start`                     | Mulai percakapan AI                             |
| `GET/POST` | `/conversations/{conversation}[/messages]` | Baca / kirim pesan                              |

### Admin (perlu login + role `admin`)

| Method   | URI                                                 | Keterangan                                    |
| -------- | --------------------------------------------------- | --------------------------------------------- |
| `GET`  | `/admin/dashboard`                                | KPI, overview proyek & pegawai, Kanban, Gantt |
| `GET`  | `/admin/proyek`                                   | Daftar proyek                                 |
| `GET`  | `/admin/proyek/{project}`                         | Detail proyek                                 |
| `POST` | `/admin/proyek`                                   | Tambah proyek                                 |
| `POST` | `/admin/tugas/assign`                             | Tugaskan pekerjaan ke pegawai                 |
| `GET`  | `/admin/laporan`                                  | Laporan dengan filter rentang waktu           |
| `GET`  | `/admin/laporan/ekspor`                           | Unduh laporan CSV                             |
| `GET`  | `/admin/users`                                    | Kelola pengguna                               |
| `POST` | `/admin/users/{user}/toggle-status`               | Aktif/nonaktifkan akun                        |
| `GET`  | `/admin/conversations`, `/admin/chat-histories` | Pantau percakapan AI                          |

Lihat daftar lengkap dengan:

```bash
php artisan route:list
```

---

## Perintah Harian

```bash
# Server pengembangan
php artisan serve --port=8001

# Semua service sekaligus (server + queue + log + vite)
composer run dev

# Pengujian
composer run test          # atau: php artisan test

# Format kode (Laravel Pint)
./vendor/bin/pint

# Reset database dan isi ulang data contoh
php artisan migrate:fresh --seed

# Bersihkan cache saat konfigurasi/route terasa "nyangkut"
php artisan optimize:clear
```

---

## Alur Kerja Git

- `main` — branch utama yang stabil.
- `feature/<nama>` — branch pengembangan fitur, mis. `feature/redesign-dashboard-inaai`.

Menyelaraskan branch fitur dengan `main` terbaru:

```bash
git fetch origin --prune
git checkout main && git pull
git checkout feature/<nama>
git merge main            # atau: git rebase main
```

---

## Konvensi Penulisan

### Pesan commit

Memakai [Conventional Commits](https://www.conventionalcommits.org) dengan
subjek berbahasa Indonesia:

```
tipe(cakupan): subjek

Badan seperlunya, maksimal 3 baris.
```

| Tipe | Dipakai untuk |
| --- | --- |
| `feat` | Kemampuan baru yang terasa oleh pengguna |
| `fix` | Perbaikan perilaku atau data yang salah |
| `refactor` | Menata ulang kode tanpa mengubah perilaku |
| `perf` | Perubahan yang tujuannya kecepatan |
| `style` | Tampilan atau format, tanpa perubahan logika |
| `docs` | Dokumentasi saja |
| `build` | Infrastruktur, dependensi, konfigurasi lingkungan |
| `chore` | Pekerjaan rumah tangga yang tidak masuk kategori lain |

Aturannya:

- Subjek maksimal **72 karakter** termasuk prefiks, huruf kecil setelah titik
  dua, bentuk perintah (`tambah`, `perbaiki`, `pindah`), tanpa titik di akhir.
- **Badan opsional, 0–3 baris**, dan hanya ditulis kalau alasannya tidak terbaca
  dari subjek dan diff. Rincian panjang tempatnya di deskripsi pull request.
- Cakupan (`admin`, `chat`, `ui`, `data`, …) boleh dilewati kalau perubahannya
  memang menyentuh banyak area.
- **Tanpa trailer atribusi** (`Co-Authored-By` dan sejenisnya).
- Commit merge dibiarkan apa adanya — badannya justru tempat mencatat keputusan
  resolusi konflik.

Contoh:

```
feat(admin): sambungkan seluruh halaman admin ke data sebenarnya

Halaman admin masih memakai angka contoh, jadi belum bisa dipakai
memantau apa pun.
```

```
fix(data): warna proyek jadi unik dan tanggal dibuat tidak di masa depan
```

### Kode

- **Bahasa Indonesia** untuk nama method, variabel, dan komentar yang ditulis
  sendiri (`simpanEvidence`, `daftarStatus`, `warnaAvatar`), mengikuti gaya yang
  sudah ada di repo ini. Nama bawaan framework tetap seperti aslinya.
- Komentar menjelaskan **kenapa**, bukan mengulang apa yang sudah jelas dari
  kodenya.
- Gaya kode PHP mengikuti Laravel Pint: `./vendor/bin/pint`.
- Komponen antarmuka baru diletakkan di `public/js/inaai-*.js` dan gayanya di
  `resources/views/partials/inaai-style.blade.php`, supaya satu perubahan
  berlaku di semua halaman.

---

## Troubleshooting

<details>
<summary><b>SQLSTATE[HY000] [2002] Connection refused</b></summary>

PostgreSQL belum jalan atau port salah. Cek container dan port:

```bash
docker ps --format '{{.Names}}\t{{.Ports}}'
php artisan db:show
```

Pastikan `DB_PORT` di `.env` sama dengan port yang dipetakan container (default `5432`).

</details>

<details>
<summary><b>SQLSTATE[08006] password authentication failed for user ...</b></summary>

Cocokkan `DB_USERNAME`/`DB_PASSWORD` di `.env` dengan role PostgreSQL yang Anda buat.
Reset password role bila perlu:

```bash
docker exec postgres18-postgis \
  psql -U postgres -c "ALTER ROLE inaai WITH PASSWORD '<password>';"
```

</details>

<details>
<summary><b>Upload evidence gagal / "failed to upload"</b></summary>

Batas bawaan PHP (`upload_max_filesize = 2M`, `post_max_size = 8M`) lebih kecil
daripada batas aplikasi (10 MB per file). Naikkan lewat file drop-in:

```bash
# lokasi conf.d bisa dilihat dengan: php --ini
cat > /opt/homebrew/etc/php/8.5/conf.d/99-inaai-upload.ini <<'INI'
upload_max_filesize = 12M
post_max_size = 64M
memory_limit = 256M
INI
```

Lalu jalankan ulang `php artisan serve`. Aplikasi otomatis menyesuaikan batas
validasi dengan kemampuan server (lihat `app/Support/BatasUnggah.php`), jadi
pesan error yang muncul selalu menyebutkan batas yang berlaku.

</details>

<details>
<summary><b>Bukti kerja tidak bisa dibuka (404)</b></summary>

Symlink storage belum dibuat:

```bash
php artisan storage:link
```

</details>

<details>
<summary><b>Chat AI tidak membalas</b></summary>

`AI_API_KEY` kosong atau endpoint salah. Isi `AI_API_KEY`, `AI_API_URL`, dan `AI_MODEL`,
lalu jalankan `php artisan config:clear`. Detail error terekam di `storage/logs/laravel.log`.

</details>

<details>
<summary><b>Halaman tampil tanpa gaya (CSS hilang)</b></summary>

Halaman dashboard tidak memakai Vite, jadi biasanya penyebabnya adalah cache view.
Jalankan `php artisan view:clear`. Untuk halaman React/Inertia, jalankan `npm run dev`
atau `npm run build`.

</details>

---

## Deployment

Repositori sudah menyertakan `Procfile` dan `nixpacks.toml` untuk platform berbasis
Nixpacks (Railway, Coolify, dan sejenisnya).

Perintah start bawaan:

```
php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=$PORT
```

Checklist sebelum rilis:

- [ ] `APP_ENV=production` dan `APP_DEBUG=false`
- [ ] `APP_KEY` sudah di-generate
- [ ] Kredensial database produksi terpasang
- [ ] Ganti/hapus akun seeder bawaan
- [ ] Cache produksi: `php artisan optimize` (config + route + view)
- [ ] Gunakan web server (Nginx/Apache) atau `php artisan octane`, bukan `artisan serve`, untuk trafik nyata

---

## Lisensi

Proyek internal. Framework Laravel berlisensi [MIT](https://opensource.org/licenses/MIT).
