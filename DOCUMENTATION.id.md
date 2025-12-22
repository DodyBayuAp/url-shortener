# Dokumentasi Teknis

Dokumen ini memberikan ulasan mendalam tentang arsitektur, skema database, dan opsi kustomisasi untuk Aplikasi Pemendek URL PHP.

*Read this in [English](DOCUMENTATION.md).*

## 🏗 Arsitektur

Aplikasi ini dibangun sebagai **Single File Application (`index.php`)**. Pilihan desain ini menyederhanakan penyebaran dan pemeliharaan.

*   **Frontend**: HTML5, CSS3 (Bulma Framework), JavaScript (Chart.js untuk analitik, QRCode.js).
    *   **Theming**: CSS Variables kustom dengan 5 tema bawaan (Light, Dark, Midnight, Forest, Ocean).
*   **Backend**: Native PHP (Tanpa dependensi framework eksternal).
*   **Database**: PDO Abstraction Layer yang mendukung MySQL, PostgreSQL, dan SQLite.

### Alur Sistem
1.  **Inisialisasi**: Menyiapkan koneksi database dan membuat tabel jika belum ada.
2.  **Routing**: Mem-parsing `$_SERVER['REQUEST_URI']` relatif terhadap `BASE_PATH` yang dinamis.
3.  **Logika Kontroler**: Menangani rute tertentu (`/login`, `/admin`, `/u/{code}`).
4.  **Rendering Tampilan**: Menghasilkan HTML secara langsung di dalam fungsi.

## 🗄 Skema Database

Aplikasi menggunakan empat tabel utama dengan indexing otomatis untuk performa optimal.

### 1. `users`
Menyimpan kredensial administrator dan pengguna.

| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `id` | INT/SERIAL (PK) | ID Pengguna Unik |
| `username` | VARCHAR(50) | Username Login (Unik) |
| `password` | TEXT | Password yang di-hash dengan Bcrypt |
| `api_key` | TEXT | API Key unik untuk autentikasi |
| `is_admin` | INT | Flag (0/1) untuk status admin |
| `force_change_password` | INT | Flag (0/1) untuk memaksa reset password |

### 2. `urls`
Menyimpan link yang dipendekkan dengan index untuk pencarian cepat.

| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `id` | INT/SERIAL (PK) | ID URL Unik |
| `long_url` | TEXT | URL tujuan asli |
| `short_code` | VARCHAR(50) | Slug unik (a-z, 0-9, -, _) (Terindex) |
| `user_id` | INT | ID pengguna yang membuat link (Terindex) |
| `created_at` | TIMESTAMP | Waktu pembuatan |

**Index**: `idx_user_id`, `idx_short_code`

### 3. `visits`
Menyimpan data analitik untuk setiap klik dengan index performa.

| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `id` | INT/SERIAL (PK) | ID Kunjungan Unik |
| `url_id` | INT (FK) | Referensi ke `urls.id` |
| `ip_address` | VARCHAR(45) | Alamat IP Pengunjung (Terindex) |
| `country` | VARCHAR(100) | Negara Terdeteksi |
| `city` | VARCHAR(100) | Kota Terdeteksi |
| `user_agent` | TEXT | String User Agent Browser |
| `referrer` | TEXT | URL Perujuk |
| `created_at` | TIMESTAMP | Waktu kunjungan (Terindex) |

**Index**: `idx_url_id`, `idx_created_at`, `idx_ip_address`, `idx_url_date` (komposit)

### 4. `daily_stats` (Opsional)
Tabel ringkasan untuk query statistik cepat (dibuat saat `$enableDailySummary = true`).

| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `id` | INT/SERIAL (PK) | ID Stat Unik |
| `url_id` | INT (FK) | Referensi ke `urls.id` (Terindex) |
| `stat_date` | DATE | Tanggal statistik (Terindex) |
| `total_clicks` | INT | Total klik untuk hari tersebut |
| `unique_visitors` | INT | Pengunjung unik untuk hari tersebut |
| `created_at` | TIMESTAMP | Waktu pembuatan |
| `updated_at` | TIMESTAMP | Waktu update terakhir |

**Index**: `idx_stat_date`, `idx_url_id`
**Constraint**: UNIQUE (url_id, stat_date)

## ⚙️ Opsi Konfigurasi

### Pengaturan Database

```php
$dbType = 'sqlite';  // Opsi: 'sqlite', 'mysql', 'pgsql'
$dbHost = 'localhost';
$dbName = 'url_shortener';
$dbUser = 'root';
$dbPass = '';
$dbPort = '';  // Opsional: 3306 untuk MySQL, 5432 untuk PostgreSQL
```

### Pengaturan API

```php
$apiEnabled = true;
$apiAuthType = 'jwt'; // Opsi: 'api_key', 'jwt', 'paseto'
$apiSecret = '...'; // Secret untuk tanda tangan JWT/PASETO
$apiAllowedUserAgents = 'google,Sheets,googlebot,Mozilla'; // UA yang diperbolehkan (pisahkan dengan koma)
$apiAllowedIPs = ''; // IP yang diperbolehkan (kosong = semua)
$apiTokenExpiry = 3600; // Masa berlaku token dalam detik
```

### Pengaturan Optimasi

```php
// Aktifkan pembuatan index otomatis (rekomendasi: true)
$enableIndexes = true;

// Simpan data kunjungan selama X hari (0 = simpan selamanya, rekomendasi: 365)
$dataRetentionDays = 365;

// Aktifkan tabel ringkasan statistik harian (rekomendasi: true untuk >100K kunjungan)
$enableDailySummary = true;

// Otomatis arsipkan data lama (rekomendasi: true untuk >1M kunjungan)
$autoArchiveOldData = false;
```

**Dampak Performa:**
- **$enableIndexes**: Meningkatkan kecepatan query 10-100x. Selalu aktifkan.
- **$enableDailySummary**: Membuat tabel ringkasan untuk statistik cepat. Aktifkan saat >100K kunjungan.
- **$dataRetentionDays**: Otomatis menghapus data kunjungan lama. Set 365 untuk retensi 1 tahun.
- **$autoArchiveOldData**: Otomatis menghapus data lebih lama dari periode retensi setiap page load.

### Pengaturan Tampilan

```php
$appTitle = 'URL Shortener';
$appLogo = 'logo.png';
$appFavicon = 'favicon.ico';
$appLang = 'id';  // Opsi: 'en' atau 'id'
```

## 🛠 Fungsi Utama

### `getBaseUrl()`
Secara dinamis menyusun protokol (HTTP/HTTPS) dan host.

### `getGeoLocation($ip)`
Menggunakan API gratis `ip-api.com` untuk menyelesaikan alamat IP ke Negara dan Kota.
*Catatan: Termasuk timeout 1 detik untuk mencegah penundaan pemuatan halaman jika API lambat.*

### `generateCode($length)`
Membuat string alfanumerik acak untuk kode pendek. Panjang default adalah 6 karakter.

### `__($key)`
Fungsi helper untuk mengambil string terjemahan berdasarkan pengaturan `$appLang`.

## 🔒 Tindakan Keamanan

*   **Perlindungan CSRF**: Semua form POST menyertakan `csrf_token` unik yang divalidasi terhadap sesi pengguna.
*   **Keamanan Sesi**: Sesi dikonfigurasi dengan atribut `HttpOnly`, `Secure` (jika HTTPS), dan `SameSite=Strict`.
*   **Validasi Input**: URL divalidasi menggunakan `filter_var()`. Output di-escape menggunakan `htmlspecialchars()` untuk mencegah XSS.
*   **Hash Password**: Menggunakan `password_hash()` dengan `PASSWORD_BCRYPT`.
*   **Keamanan API**: Mendukung kunci per-pengguna dan token dinamis HMAC-SHA256 untuk mencegah penggunaan ulang dan eksposur token.

## 🔌 Penggunaan & Integrasi API

Aplikasi ini menyediakan API yang tangguh untuk pemendekan URL secara programatik, dikelola melalui halaman **Pengaturan API** di dashboard.

### 📚 Dokumentasi Interaktif (Swagger UI)
Cara termudah untuk menjelajahi dan menguji API adalah menggunakan Swagger UI bawaan.
- **URL**: `http://situs-anda.com/api/docs`
- **File Spesifikasi**: `http://situs-anda.com/api-docs.json` (Dibuat otomatis)

### Endpoint
- `POST /api/login`: Autentikasi dan dapatkan token (JWT/PASETO).
- `POST /api/shorten`: Buat URL pendek baru.
- `GET /api/urls`: Lihat daftar URL Anda.
- `GET /api/stats`: Dapatkan statistik untuk URL tertentu.
- `POST /api/update`: Perbarui URL pendek.
- `POST /api/delete`: Hapus URL pendek.

### Metode Autentikasi
Anda dapat mengonfigurasi metode autentikasi di dashboard **Manajer API** (`/api`).

#### 1. API Key (Legacy)
Autentikasi berbasis kunci sederhana. Cocok untuk skrip simpel.
- **Header**: Tidak diperlukan (dikirim via query param).
- **Query Param**: `?ids=API_KEY_ANDA`
- **Contoh**: `POST /api/shorten?ids=xyz123&longurl=https://google.com`

#### 2. JWT (JSON Web Token)
Autentikasi token standar yang aman.
1.  **Login**: `POST /api/login` dengan `{"username":"admin", "password":"..."}`.
2.  **Terima**: Dapatkan `token` dalam respons JSON.
3.  **Gunakan**: Kirim header `Authorization: Bearer <token_anda>` pada request selanjutnya.

#### 3. PASETO (Platform-Agnostic Security Tokens)
Alternatif yang lebih aman daripada JWT (Versi 2, Local).
- Penggunaan identik dengan JWT (Login -> Bearer Token).
- Memerlukan ekstensi PHP `sodium`.

### Respons
- **Sukses (200)**: Objek JSON (contoh: `{"status":200, "message":"OK", "data": [...]}`).
- **Dukungan Legacy**: `/api/shorten` mengembalikan URL pendek plain text jika sukses (untuk kompatibilitas Google Sheets).
- **Error (4xx/5xx)**: Objek JSON dengan `status` dan `pesan`.

### Mengubah Logo
Ganti `logo.png` di direktori root dengan gambar Anda sendiri. Perbarui nama file di `index.php` jika perlu.

### Mengubah Warna & Tema
Aplikasi menggunakan CSS variables untuk theming. Anda dapat menemukan fungsi `renderThemeCss()` di `index.php`.

**Variable yang Tersedia:**
```css
:root {
    --primary-blue: #007bff;
    --bg-color: #f5f7fa;      /* Background utama */
    --text-color: #363636;    /* Warna teks utama */
    --box-bg: #ffffff;        /* Background Card/Box */
    --navbar-bg: #007bff;     /* Background Navbar */
    /* ... lain-lain */
}
```

**Menambahkan Tema Baru:**
1.  Cari `renderThemeCss()` di `index.php`.
2.  Tambahkan blok baru: `[data-theme='nama-tema-anda'] { ... }`.
3.  Tambahkan opsi baru ke fungsi `renderFooter()` di bagian Theme Modal.

### Konfigurasi Database

Di bagian atas file `index.php`, terdapat blok konfigurasi:

```php
// --- CONFIGURATION START ---
$configured = true;
$dbType = 'sqlite'; // 'sqlite', 'mysql', atau 'pgsql'
...
// Customize App Appearance
$appTitle = 'Direktorat SMP - URL Shortener';
$appLogo = 'logo.png';
$appFavicon = 'favicon.ico';
$appLang = 'id';

// Database Optimization Settings
$enableIndexes = true;
$dataRetentionDays = 365;
$enableDailySummary = true;
$autoArchiveOldData = false;
// --- CONFIGURATION END ---
```

## 🔧 Konfigurasi Otomatis (Setup Wizard)

Aplikasi menggunakan mekanisme **Konfigurasi Mandiri** untuk tetap berada dalam satu file.
Saat Anda menjalankan Setup Wizard:
1.  Skrip membaca kode sumbernya sendiri (`__FILE__`).
2.  Skrip mengganti blok konfigurasi di bagian atas file dengan input Anda.
3.  Skrip menyimpan konten yang dimodifikasi kembali ke `index.php`.

### Mereset Konfigurasi
Untuk menjalankan kembali Setup Wizard (misalnya, untuk beralih database):
1.  Buka `index.php` di editor teks.
2.  Cari baris `$configured = true;`.
3.  Ubah menjadi `$configured = false;`.
4.  Refresh browser Anda.

### Beralih Database
Ikuti langkah "Mereset Konfigurasi" di atas, lalu pilih tipe database baru di Wizard.

## 📊 Tips Performa Database

### Untuk Situs Volume Tinggi (>100K kunjungan)
1.  Aktifkan `$enableDailySummary = true` untuk menggunakan tabel ringkasan
2.  Set `$dataRetentionDays = 365` untuk membatasi pertumbuhan data
3.  Gunakan MySQL atau PostgreSQL daripada SQLite

### Untuk Situs Volume Sangat Tinggi (>1M kunjungan)
1.  Aktifkan `$autoArchiveOldData = true`
2.  Pertimbangkan set `$dataRetentionDays = 180` (6 bulan)
3.  Gunakan PostgreSQL untuk performa terbaik
4.  Pertimbangkan implementasi partitioning database (memerlukan setup manual)

### Pemeliharaan Index
Index dibuat otomatis saat `$enableIndexes = true`. Untuk MySQL, jalankan `OPTIMIZE TABLE visits` secara berkala untuk menjaga performa.

## 🌐 Database yang Didukung

### SQLite
- **Kelebihan**: Tanpa konfigurasi, single file, sempurna untuk situs kecil hingga menengah
- **Kekurangan**: Write konkuren terbatas, lebih lambat untuk dataset sangat besar
- **Terbaik untuk**: <100K kunjungan, development, deployment kecil

### MySQL
- **Kelebihan**: Robust, widely supported, performa bagus
- **Kekurangan**: Memerlukan server terpisah, setup lebih kompleks
- **Terbaik untuk**: 100K-10M kunjungan, situs produksi

### PostgreSQL
- **Kelebihan**: Enterprise-grade, performa terbaik, fitur advanced
- **Kekurangan**: Memerlukan server terpisah, setup lebih kompleks
- **Terbaik untuk**: >1M kunjungan, deployment enterprise, high concurrency

## 📝 Login Default

*   **Username**: `admin`
*   **Password**: `admin` (Anda akan dipaksa menggantinya saat login pertama)
