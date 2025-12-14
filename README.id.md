# Simple PHP URL Shortener (Bahasa Indonesia)

Aplikasi pemendek URL yang ringan, kuat, dan single-file yang ditulis dalam PHP. Dirancang untuk kesederhanaan, kemudahan deployment, dan fungsionalitas yang handal tanpa bloat framework.

*Baca dalam [Bahasa Inggris](README.md).*

![Dashboard Preview](tutwuri.png)

## 🌟 Fitur Utama

*   **Penanganan Path Dinamis**: Bekerja sempurna di domain root (`example.com`) atau sub-direktori (`example.com/u/`) tanpa perubahan konfigurasi.
*   **Analitik Lengkap**: Lacak klik, pengunjung unik, lokasi geografis (Negara/Kota), jenis perangkat, OS, browser, dan referer dengan grafik interaktif (Chart.js).
*   **Dashboard Admin**: Antarmuka bersih dan responsif yang dibangun dengan Bulma CSS.
*   **Manajemen User**: Dukungan multi-user. Admin dapat mengelola pengguna, reset password, dan memaksa pergantian password.
*   **QR Code Generator**: Buat dan unduh kode QR secara instan untuk link pendek Anda.
*   **Fleksibilitas Database**: Mendukung **MySQL**, **PostgreSQL**, dan **SQLite** (default). Otomatis membuat index untuk performa optimal.
*   **Optimasi Performa**: Index database built-in, ringkasan statistik harian, dan manajemen retensi data otomatis.
*   **Aman**: Termasuk perlindungan CSRF, penanganan sesi yang aman, dan hashing password bcrypt.
*   **Desain Responsif**: Antarmuka ramah seluler untuk mengelola link saat bepergian.
*   **Internasionalisasi**: Dukungan penuh untuk bahasa Inggris dan Indonesia.

## 🚀 Instalasi & Setup

### Persyaratan
*   PHP 7.4 atau lebih tinggi
*   Ekstensi PDO (untuk MySQL, PostgreSQL, atau SQLite)
*   Web Server (Apache/Nginx) dengan URL Rewriting diaktifkan (opsional tapi disarankan untuk URL yang bersih)
*   Database: MySQL 5.7+, PostgreSQL 9.5+, atau SQLite 3.x

### Mulai Cepat

1.  **Download/Clone**:
    Unduh kode sumber dan letakkan di direktori publik server web Anda.

2.  **Konfigurasi & Setup**:
    Buka browser Anda dan navigasikan ke folder tersebut (misalnya, `http://localhost/u/`).
    
    Anda akan disambut oleh **Setup Wizard**.
    *   **Pilih Database**: Pilih **SQLite** untuk setup instan (tanpa konfigurasi), **MySQL** untuk penggunaan produksi yang handal, atau **PostgreSQL** untuk performa tingkat enterprise.
    *   **Install**: Klik "Simpan & Install". Aplikasi akan mengonfigurasi dirinya sendiri secara otomatis.

3.  **Login**:
    *   **Username**: `admin`
    *   **Password**: `admin`
    
    *Catatan: Anda akan dipaksa untuk mengganti password saat login pertama demi keamanan.*

### Menjalankan dengan PHP Built-in Server
Jika Anda tidak memiliki server web yang terinstal, Anda dapat menggunakan server bawaan PHP.
1.  Buka terminal/command prompt Anda.
2.  Navigasikan ke direktori proyek.
3.  Jalankan perintah:
    ```bash
    php -S localhost:8000
    ```
4.  Buka browser Anda dan kunjungi `http://localhost:8000`.

## 🔧 Konfigurasi Server Web

Untuk memastikan link pendek seperti `example.com/u/abc123` dialihkan dengan benar ke `index.php`, Anda mungkin memerlukan aturan rewrite.

**Apache (.htaccess)**
File `.htaccess` yang disertakan menangani ini secara otomatis:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Nginx**
Tambahkan ini ke blok server Anda:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 📖 Dokumentasi

Untuk dokumentasi teknis yang mendetail, termasuk skema database dan struktur kode, silakan merujuk ke [DOCUMENTATION.id.md](DOCUMENTATION.id.md).

## 📄 Lisensi

Open Source. Bebas untuk memodifikasi dan menggunakan untuk proyek pribadi atau komersial.
