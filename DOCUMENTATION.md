# Technical Documentation

This document provides a deep dive into the architecture, database schema, and customization options for the PHP URL Shortener.

## 🏗 Architecture

The application is built as a **Single File Application (`index.php`)**. This design choice simplifies deployment and maintenance.

*   **Frontend**: HTML5, CSS3 (Bulma Framework), JavaScript (Chart.js for analytics, QRCode.js).
3.  **Konfigurasi Tambahan (Opsi)**
    Anda dapat mengubah judul, logo, dan favicon dengan mengedit variabel konfigurasi di bagian atas `index.php`:
    ```php
    // Customize App Appearance
    $appTitle = 'URL Shortener';
    $appLogo = 'logo.png';
    $appFavicon = 'favicon.ico';
    ```

## Penggunaan
**Login Default**:
*   **Username**: `admin`
*   **Password**: `admin` (Anda akan diminta menggantinya saat login pertama)
*   **Manajemen User**: Admin dapat mengelola pengguna lain (tambah, hapus, reset password, promote/demote admin).
*   **Statistik Lengkap**: Melacak jumlah klik, referer, lokasi, browser, dan device.
*   **QR Code**: Generate QR Code untuk setiap short link.
*   **Responsif**: Tampilan mobile-friendly menggunakan Bulma CSS.
*   **Backend**: Native PHP (No external framework dependencies).
*   **Database**: PDO Abstraction Layer supporting MySQL and SQLite.

### System Flow
1.  **Initialization**: Sets up database connection and creates tables if they don't exist.
2.  **Routing**: Parses `$_SERVER['REQUEST_URI']` relative to the dynamic `BASE_PATH`.
3.  **Controller Logic**: Handles specific routes (`/login`, `/admin`, `/u/{code}`).
4.  **View Rendering**: Outputs HTML directly within the functions.

## 🗄 Database Schema

The application uses three main tables.

### 1. `users`
Stores administrator and user credentials.

| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INT (PK) | Unique User ID |
| `username` | VARCHAR(50) | Login username (Unique) |
| `password` | TEXT | Bcrypt hashed password |
| `force_change_password` | INT | Flag (0/1) to force password reset |

### 2. `urls`
Stores the shortened links.

| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INT (PK) | Unique URL ID |
| `long_url` | TEXT | The original destination URL |
| `short_code` | VARCHAR(50) | The unique slug for the short link |
| `user_id` | INT | ID of the user who created the link |
| `created_at` | TIMESTAMP | Creation time |

### 3. `visits`
Stores analytics data for every click.

| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INT (PK) | Unique Visit ID |
| `url_id` | INT (FK) | Reference to `urls.id` |
| `ip_address` | VARCHAR(45) | Visitor IP Address |
| `country` | VARCHAR(100) | Detected Country |
| `city` | VARCHAR(100) | Detected City |
| `user_agent` | TEXT | Browser User Agent string |
| `referrer` | TEXT | Referring URL |
| `created_at` | TIMESTAMP | Visit time |

## 🛠 Key Functions

### `getBaseUrl()`
Dynamically constructs the protocol (HTTP/HTTPS) and host.

### `getGeoLocation($ip)`
Uses the free `ip-api.com` API to resolve IP addresses to Country and City. 
*Note: Includes a 1-second timeout to prevent page load delays if the API is slow.*

### `generateCode($length)`
Creates a random alphanumeric string for the short code. Default length is 6 characters.

## 🔒 Security Measures

*   **CSRF Protection**: All POST forms include a unique `csrf_token` validated against the user's session.
*   **Session Security**: Sessions are configured with `HttpOnly`, `Secure` (if HTTPS), and `SameSite=Strict` attributes.
*   **Input Validation**: URLs are validated using `filter_var()`. Outputs are escaped using `htmlspecialchars()` to prevent XSS.
*   **Password Hashing**: Uses `password_hash()` with `PASSWORD_BCRYPT`.

3. **Kustomisasi**:
    - Ubah file **logo.png** dengan logo pilihan Anda.
    - Ubah file **favicon.ico** untuk ikon tab browser.
    - Edit `index.php` untuk mengubah judul aplikasi (`$appTitle`).

## Struktur File
- `index.php`: File utama aplikasi (Core Logic, UI, Database).
- `logo.png`: Logo aplikasi.

## 🎨 Customization

### Changing the Logo
Replace `tutwuri.png` in the root directory with your own image. Update the filename in `index.php` if necessary.

### Modifying Colors
The application defines CSS variables in the `renderHeader` function:
```css
:root { 
    --primary-blue: #007bff; 
    --primary-yellow: #ffc107; 
}
```
Edit these values in `index.php` to match your brand colors.

## 🔧 Auto-Configuration (Setup Wizard)

The application uses a **Self-Configuring** mechanism to stay in a single file.
When you run the Setup Wizard:
1.  The script reads its own source code (`__FILE__`).
### Konfigurasi Database & Tampilan
Di bagian atas file `index.php`, terdapat blok konfigurasi:

```php
// --- CONFIGURATION START ---
$configured = true;
$dbType = 'sqlite'; // atau 'mysql'
...
// Customize App Appearance
$appTitle = 'URL Shortener';
$appLogo = 'logo.png';
$appFavicon = 'favicon.ico';
```
2.  It replaces the configuration block at the top of the file with your input.
3.  It saves the modified content back to `index.php`.

### Resetting Configuration
To re-run the Setup Wizard (e.g., to switch databases):
1.  Open `index.php` in a text editor.
2.  Find the line `$configured = true;`.
3.  Change it to `$configured = false;`.
4.  Refresh your browser.

### Switching Databases
Follow the "Resetting Configuration" steps above, then choose your new database type in the Wizard.
