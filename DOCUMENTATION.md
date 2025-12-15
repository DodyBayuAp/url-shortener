# Technical Documentation

This document provides a deep dive into the architecture, database schema, and customization options for the PHP URL Shortener.

*Baca ini dalam [Bahasa Indonesia](DOCUMENTATION.id.md).*

## 🏗 Architecture

The application is built as a **Single File Application (`index.php`)**. This design choice simplifies deployment and maintenance.

*   **Frontend**: HTML5, CSS3 (Bulma Framework), JavaScript (Chart.js for analytics, QRCode.js).
*   **Backend**: Native PHP (No external framework dependencies).
*   **Database**: PDO Abstraction Layer supporting MySQL, PostgreSQL, and SQLite.

### System Flow
1.  **Initialization**: Sets up database connection and creates tables if they don't exist.
2.  **Routing**: Parses `$_SERVER['REQUEST_URI']` relative to the dynamic `BASE_PATH`.
3.  **Controller Logic**: Handles specific routes (`/login`, `/admin`, `/u/{code}`).
4.  **View Rendering**: Outputs HTML directly within the functions.

## 🗄 Database Schema

The application uses four main tables with automatic indexing for optimal performance.

### 1. `users`
Stores administrator and user credentials.

| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INT/SERIAL (PK) | Unique User ID |
| `username` | VARCHAR(50) | Login username (Unique) |
| `password` | TEXT | Bcrypt hashed password |
| `is_admin` | INT | Flag (0/1) for admin status |
| `force_change_password` | INT | Flag (0/1) to force password reset |

### 2. `urls`
Stores the shortened links with indexes for fast lookups.

| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INT/SERIAL (PK) | Unique URL ID |
| `long_url` | TEXT | The original destination URL |
| `short_code` | VARCHAR(50) | The unique slug (a-z, 0-9, -, _) (Indexed) |
| `user_id` | INT | ID of the user who created the link (Indexed) |
| `created_at` | TIMESTAMP | Creation time |

**Indexes**: `idx_user_id`, `idx_short_code`

### 3. `visits`
Stores analytics data for every click with performance indexes.

| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INT/SERIAL (PK) | Unique Visit ID |
| `url_id` | INT (FK) | Reference to `urls.id` |
| `ip_address` | VARCHAR(45) | Visitor IP Address (Indexed) |
| `country` | VARCHAR(100) | Detected Country |
| `city` | VARCHAR(100) | Detected City |
| `user_agent` | TEXT | Browser User Agent string |
| `referrer` | TEXT | Referring URL |
| `created_at` | TIMESTAMP | Visit time (Indexed) |

**Indexes**: `idx_url_id`, `idx_created_at`, `idx_ip_address`, `idx_url_date` (composite)

### 4. `daily_stats` (Optional)
Summary table for fast statistics queries (created when `$enableDailySummary = true`).

| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INT/SERIAL (PK) | Unique Stat ID |
| `url_id` | INT (FK) | Reference to `urls.id` (Indexed) |
| `stat_date` | DATE | Date of statistics (Indexed) |
| `total_clicks` | INT | Total clicks for the day |
| `unique_visitors` | INT | Unique visitors for the day |
| `created_at` | TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP | Last update time |

**Indexes**: `idx_stat_date`, `idx_url_id`
**Constraint**: UNIQUE (url_id, stat_date)

## ⚙️ Configuration Options

### Database Settings

```php
$dbType = 'sqlite';  // Options: 'sqlite', 'mysql', 'pgsql'
$dbHost = 'localhost';
$dbName = 'url_shortener';
$dbUser = 'root';
$dbPass = '';
$dbPort = '';  // Optional: 3306 for MySQL, 5432 for PostgreSQL
```

### Optimization Settings

```php
// Enable automatic index creation (recommended: true)
$enableIndexes = true;

// Keep visit data for X days (0 = keep forever, recommended: 365)
$dataRetentionDays = 365;

// Enable daily statistics summary table (recommended: true for >100K visits)
$enableDailySummary = true;

// Automatically archive old data (recommended: true for >1M visits)
$autoArchiveOldData = false;
```

**Performance Impact:**
- **$enableIndexes**: Improves query speed by 10-100x. Always keep enabled.
- **$enableDailySummary**: Creates summary table for fast statistics. Enable when you have >100K visits.
- **$dataRetentionDays**: Automatically deletes old visit data. Set to 365 for 1-year retention.
- **$autoArchiveOldData**: Automatically removes data older than retention period on each page load.

### Appearance Settings

```php
$appTitle = 'URL Shortener';
$appLogo = 'logo.png';
$appFavicon = 'favicon.ico';
$appLang = 'en';  // Options: 'en' or 'id'
```

## 🛠 Key Functions

### `getBaseUrl()`
Dynamically constructs the protocol (HTTP/HTTPS) and host.

### `getGeoLocation($ip)`
Uses the free `ip-api.com` API to resolve IP addresses to Country and City.
*Note: Includes a 1-second timeout to prevent page load delays if the API is slow.*

### `generateCode($length)`
Creates a random alphanumeric string for the short code. Default length is 6 characters.

### `__($key)`
Translation helper function that retrieves localized strings based on `$appLang` setting.

## 🔒 Security Measures

*   **CSRF Protection**: All POST forms include a unique `csrf_token` validated against the user's session.
*   **Session Security**: Sessions are configured with `HttpOnly`, `Secure` (if HTTPS), and `SameSite=Strict` attributes.
*   **Input Validation**: URLs are validated using `filter_var()`. Outputs are escaped using `htmlspecialchars()` to prevent XSS.
*   **Password Hashing**: Uses `password_hash()` with `PASSWORD_BCRYPT`.

## 🎨 Customization

### Changing the Logo
Replace `logo.png` in the root directory with your own image. Update the filename in `index.php` if necessary.

### Modifying Colors
The application defines CSS variables in the `renderHeader` function:
```css
:root {
    --primary-blue: #007bff;
    --primary-yellow: #ffc107;
}
```
Edit these values in `index.php` to match your brand colors.

### Database Configuration

At the top of `index.php`, you'll find the configuration block:

```php
// --- CONFIGURATION START ---
$configured = true;
$dbType = 'sqlite'; // 'sqlite', 'mysql', or 'pgsql'
...
// Customize App Appearance
$appTitle = 'URL Shortener';
$appLogo = 'logo.png';
$appFavicon = 'favicon.ico';
$appLang = 'en';

// Database Optimization Settings
$enableIndexes = true;
$dataRetentionDays = 365;
$enableDailySummary = true;
$autoArchiveOldData = false;
// --- CONFIGURATION END ---
```

## 🔧 Auto-Configuration (Setup Wizard)

The application uses a **Self-Configuring** mechanism to stay in a single file.
When you run the Setup Wizard:
1.  The script reads its own source code (`__FILE__`).
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

## 📊 Database Performance Tips

### For High-Volume Sites (>100K visits)
1.  Enable `$enableDailySummary = true` to use summary tables
2.  Set `$dataRetentionDays = 365` to limit data growth
3.  Use MySQL or PostgreSQL instead of SQLite

### For Very High-Volume Sites (>1M visits)
1.  Enable `$autoArchiveOldData = true`
2.  Consider setting `$dataRetentionDays = 180` (6 months)
3.  Use PostgreSQL for best performance
4.  Consider implementing database partitioning (requires manual setup)

### Index Maintenance
Indexes are automatically created when `$enableIndexes = true`. For MySQL, run `OPTIMIZE TABLE visits` periodically to maintain performance.

## 🌐 Supported Databases

### SQLite
- **Pros**: Zero configuration, single file, perfect for small to medium sites
- **Cons**: Limited concurrent writes, slower for very large datasets
- **Best for**: <100K visits, development, small deployments

### MySQL
- **Pros**: Robust, widely supported, good performance
- **Cons**: Requires separate server, more complex setup
- **Best for**: 100K-10M visits, production sites

### PostgreSQL
- **Pros**: Enterprise-grade, best performance, advanced features
- **Cons**: Requires separate server, more complex setup
- **Best for**: >1M visits, enterprise deployments, high concurrency

## 📝 Default Login

*   **Username**: `admin`
*   **Password**: `admin` (You will be forced to change it on first login)
