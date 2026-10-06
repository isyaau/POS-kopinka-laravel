# POS Kopinka

Aplikasi POS multi-toko — **Laravel 13 · Inertia.js · Vue 3 · Tailwind CSS v4 · PostgreSQL**.

- Komponen UI seragam (gaya shadcn), responsive mobile & PC
- Autentikasi + RBAC Spatie, multi-toko: 1 Pusat + 5 toko Kopinka (Store Switcher)
- Layout modular (sidebar + header + drawer mobile), tema gelap/terang

---

## Daftar Isi

1. [Prasyarat](#1-prasyarat)
2. [Instalasi](#2-instalasi)
3. [Akun demo](#3-akun-demo)
4. [Menjadi server toko (start otomatis saat boot)](#4-menjadi-server-toko-start-otomatis-saat-boot)
5. [Update aplikasi](#5-update-aplikasi)
6. [Pintasan papan ketik](#6-pintasan-papan-ketik)
7. [Struktur penting](#7-struktur-penting)
8. [Menambah menu baru](#8-menambah-menu-baru)
9. [Verifikasi pasca instalasi](#9-verifikasi-pasca-instalasi)

---

## 1. Prasyarat

| Komponen | Versi |
|---|---|
| PHP | 8.3+ (dikembangkan di 8.4), ekstensi: `pdo_pgsql`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `dom`, `session`, `tokenizer`, `xml`, `zip` |
| Composer | 2.x |
| Node.js + npm | 18+ (dikembangkan di 24) |
| PostgreSQL | 14+ (dikembangkan di 18) |
| Git | wajib — dipakai untuk clone dan update |

---

## 2. Instalasi

Dilakukan **sekali** di PC developer atau PC yang akan dijadikan server toko.

### 2.1 Clone & dependency

```bash
git clone https://github.com/isyaau/POS-kopinka-laravel.git
cd POS-kopinka-laravel
composer install
npm install
```

### 2.2 Konfigurasi `.env`

```bash
copy .env.example .env
php artisan key:generate
```

Lalu isi kredensial database di `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pos_kopinka
DB_USERNAME=postgres
DB_PASSWORD=***
```

### 2.3 Migrasi & seed

```bash
php artisan migrate --seed
```

Membuat seluruh tabel + `RolePermissionSeeder` (role, permission, 6 toko, dan user demo).

### 2.4 Jalankan (mode developer)

```bash
composer dev
```

Menjalankan sekaligus: `php artisan serve` (http://localhost:8000) + `queue:listen` + Vite dev server.

Atau terpisah:

```bash
php artisan serve           # web → http://localhost:8000
php artisan queue:listen    # antrian job
npm run dev                 # Vite dev server (HMR)
```

> Catatan Windows: `composer dev:logs` (Laravel Pail) tidak bisa jalan karena butuh ekstensi `pcntl` yang hanya ada di Linux/macOS.

### 2.5 Mode produksi (aset)

```bash
npm run build
```

Hasilnya di `public/build` — inilah yang dipakai saat aplikasi dijalankan sebagai server toko (Vite dev server tidak dipakai).

### 2.6 Satu perintah

```bash
composer setup
```

Menjalankan: `composer install` → salin `.env` (bila belum ada) → `key:generate` → `migrate --force` → `npm install` → `npm run build`.

Seed **belum** termasuk — jalankan sekali setelahnya:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

---

## 3. Akun demo

| Email | Password | Role | Store |
|---|---|---|---|
| `admin@kopinka.test` | `admin123` | **Admin** (prioritas) | Kopinka Pusat |
| `super-admin@kopinka.test` | `admin123` | Super Admin | Kopinka Pusat |
| `kasir@kopinka.test` | `admin123` | Kasir | Kopinka 1 |

---

## 4. Menjadi server toko (start otomatis saat boot)

Dilakukan **sekali** di PC yang akan dijadikan server, buka PowerShell **Run as administrator**:

```powershell
powershell -ExecutionPolicy Bypass -File "E:\path\ke\POS-kopinka-laravel\deploy\install.ps1"
```

Klik **Yes** pada dialog UAC.

Yang dilakukan script ini:

1. Membuat `.env.production` dari `.env` (`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=http://<IP-PC>`); bila `.env` belum ada memakai `.env.example`, dan `APP_KEY` otomatis di-generate jika masih kosong
2. Mendaftarkan Scheduled Task:
   - `Kopinka Web` → `php artisan serve --host=0.0.0.0 --port=80` (start saat boot, berjalan sebagai SYSTEM)
   - `Kopinka Queue` → `php artisan queue:work` (start saat boot)
3. Menambahkan rule firewall `POS Kopinka HTTP` (TCP 80) agar PC lain bisa mengakses
4. Set service PostgreSQL ke **Automatic**, menjalankan migrasi, lalu langsung menyalakan kedua task

Hasilnya: aplikasi bisa dibuka dari PC mana pun di jaringan yang sama melalui `http://<IP-PC-server>` tanpa harus login ke Windows.

Opsi tambahan:

```powershell
deploy\install.ps1 -Build      # sekalian npm install && npm run build
deploy\install.ps1 -NoElevate  # tanpa dialog UAC (task hanya start saat login user)
```

**Catatan penting**

- Mode developer memakai `.env`, mode server memakai `.env.production` — keduanya tidak saling menimpa.
- Set **IP statis** di PC server. Jika IP berubah, perbarui `APP_URL` di `.env.production`.
- Log service: `storage/logs/services.log`
- Gagal jalankan? Lihat `Task Scheduler` → `Kopinka Web` / `Kopinka Queue`.
- Menghapus semua (task + rule firewall): buka PowerShell **as administrator** lalu `deploy\uninstall.ps1`

---

## 5. Update aplikasi

### 5.1 Dari sisi pengembang

```bash
git push origin main
```

### 5.2 Di PC toko

Klik ganda **`deploy\update.bat`**, atau dari terminal:

```powershell
powershell -ExecutionPolicy Bypass -File deploy\update.ps1
```

Urutan yang dijalankan:

| # | Langkah | Keterangan |
|---|---|---|
| 1 | `artisan down` | Mode maintenance; dibuka lagi di langkah terakhir, **tetap jalan walau update gagal** |
| 2 | `git pull --ff-only` | **Berhenti & batal** jika ada file lokal yang diubah manual |
| 3 | `composer install --no-dev` | Dependency backend |
| 4 | `npm install` + `npm run build` | Aset frontend |
| 5 | `artisan migrate --force` | Hanya menjalankan migration **baru**; data existing tidak disentuh. Jika gagal, update langsung berhenti |
| 6 | `artisan queue:restart` | Worker reload kode baru; web reload otomatis per-request |

**Opsi tambahan**

| Opsi | Fungsi |
|---|---|
| `-Backup` | `pg_dump` ke `storage/app/backups/<nama-db>-<tanggal>.sql` **sebelum** migrate |
| `-SkipBuild` | Lewati langkah npm (jika tidak ada perubahan frontend) |
| `-NoDown` | Tanpa mode maintenance |
| `-Dev` | Ikut paket dev (phpunit, pint) |

Contoh:

```powershell
deploy\update.bat -Backup
```

**Log**

- `storage/logs/services.log` — riwayat update & service
- `storage/logs/laravel.log` — error aplikasi

---

## 6. Pintasan papan ketik

Tekan `?` di halaman mana pun untuk membuka daftar lengkap.

**Transaksi (`/transaksi`)**

| Pintasan | Fungsi |
|---|---|
| `/` | Fokus ke pencarian |
| `↑` `↓` | Pilih baris |
| `Enter` | Detail baris terpilih |
| `E` / `P` / `Del` | Edit / cetak struk / hapus baris terpilih |
| `N` | Tambah transaksi |
| `Ctrl+E` / `Ctrl+I` | Export Excel / Import |
| `Esc` | Bersihkan pencarian → batalkan pilihan |

**POS / Kasir (`/pos`)**

| Pintasan | Fungsi |
|---|---|
| `/` | Fokus pencarian produk |
| `↑` `↓` + `Enter` | Pilih produk dari hasil pencarian → ke keranjang |
| `Esc` | Bersihkan pencarian produk |
| `A` / `V` | Fokus pencarian anggota / input voucher |
| `Ctrl+Enter` | Buka konfirmasi pembayaran |
| `Enter` | Bayar & simpan → transaksi baru |
| `Ctrl+P` | Cetak struk transaksi terakhir |

---

## 7. Struktur penting

```
resources/js/
├── app.js                      → boot Inertia, theme init
├── components/
│   ├── ui/                     → komponen seragam (button, card, sheet, dll)
│   ├── Kbd.vue                 → chip pintasan papan ketik
│   ├── ShortcutHelpDialog.vue  → dialog daftar pintasan (?)
│   └── layout/                 → AppLayout, AppSidebar, AppHeader, StoreSwitcher, PageHeader, SidebarNav
├── composables/useHotkeys.js   → registry pintasan papan ketik
├── config/navigation.js        → daftar semua menu + permission
├── composables/useTheme.js     → toggle dark/light
└── Pages/                      → Auth/Login, Dashboard, POS, Produk, Laporan, Pengguna

app/
├── Models/Store.php
├── Models/User.php             → HasRoles + relasi store
├── Http/Middleware/ResolveStore.php
├── Http/Middleware/CheckPermission.php
└── Http/Controllers/Auth/AuthController.php, StoreSwitchController.php

deploy/
├── install.ps1                 → setup server toko (sekali, butuh admin)
├── update.bat / update.ps1     → update aplikasi di PC toko
├── start-web.ps1               → loop service web
├── start-queue.ps1             → loop worker queue
└── uninstall.ps1               → hapus task + rule firewall

database/migrations/            → stores, users.store_id, permission tables
database/seeders/RolePermissionSeeder.php → permission, 6 toko, user demo
```

---

## 8. Menambah menu baru

Buka `resources/js/config/navigation.js` dan tambah satu item:

```js
{ title: 'Kasir / POS', href: '/pos', icon: ShoppingCart, permission: 'transaksi.manage' },
```

Sidebar (desktop + drawer mobile) dan filter permission **otomatis** mengikuti.

---

## 9. Verifikasi pasca instalasi

1. **`/login`** → login `admin@kopinka.test`
2. **Dashboard** → sidebar + header + Store Switcher muncul
3. **Desktop** (>1024px): sidebar penuh → tombol panel ciutkan jadi ikon
4. **Mobile** (<1024px, devtools 375px): sidebar jadi drawer geser dari kiri
5. **Store Switcher** (sebagai admin): pindah pusat ↔ toko 1–5, dan "Semua Toko"
6. **RBAC**: login `kasir@kopinka.test` → menu Laporan & Pengguna tersembunyi, akses langsung `/laporan` → **403**
7. **Toggle tema** gelap/terang (ikon di header)
8. **Server toko**: buka `http://<IP-PC-server>` dari PC lain di jaringan yang sama
