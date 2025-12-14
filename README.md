# Simple PHP URL Shortener

A lightweight, powerful, and single-file URL shortener written in PHP. Designed for simplicity, ease of deployment, and robust functionality without the bloat of frameworks.

![Dashboard Preview](tutwuri.png)

## 🌟 Key Features

*   **Dynamic Path Handling**: Works flawlessly in the root domain (`example.com`) or any subdirectory (`example.com/u/`) without configuration changes.
*   **Comprehensive Analytics**: Track clicks, unique visitors, geographic location (Country/City), device type, OS, browser, and referrers with interactive charts (Chart.js).
*   **Admin Dashboard**: Clean, responsive interface built with Bulma CSS.
*   **User Management**: Multi-user support. Admins can manage users, reset passwords, and force password changes.
*   **QR Code Generation**: Instantly generate and download QR codes for your short links.
*   **Database Flexibility**: Supports **MySQL**, **PostgreSQL**, and **SQLite** (default). Auto-creates indexes for optimal performance.
*   **Performance Optimized**: Built-in database indexing, daily statistics summary, and automatic data retention management.
*   **Secure**: Includes CSRF protection, secure session handling, and bcrypt password hashing.
*   **Responsive Design**: Mobile-friendly interface for managing links on the go.
*   **Internationalization**: Full support for English and Indonesian languages.

## 🚀 Installation & Setup

### Requirements
*   PHP 7.4 or higher
*   PDO Extension (for MySQL, PostgreSQL, or SQLite)
*   Web Server (Apache/Nginx) with URL Rewriting enabled (optional but recommended for clean URLs)
*   Database: MySQL 5.7+, PostgreSQL 9.5+, or SQLite 3.x

### Quick Start

1.  **Download/Clone**:
    Download the source code and place it in your web server's public directory.

    Download the source code and place it in your web server's public directory.

2.  **Configuration & Setup**:
    Open your browser and navigate to the folder (e.g., `http://localhost/u/`).
    
    You will be greeted by the **Setup Wizard**.
    *   **Choose Database**: Select **SQLite** for instant setup (no config needed), **MySQL** for robust production use, or **PostgreSQL** for enterprise-grade performance.
    *   **Install**: Click "Simpan & Install". The application will automatically configure itself.

3.  **Login**:
    *   **Username**: `admin`
    *   **Password**: `admin`
    
    *Note: You will be forced to change the password upon first login for security.*

### Running with PHP Built-in Server
If you don't have a web server installed, you can use PHP's built-in server.
1.  Open your terminal/command prompt.
2.  Navigate to the project directory.
3.  Run the command:
    ```bash
    php -S localhost:8000
    ```
4.  Open your browser and visit `http://localhost:8000`.

## 🔧 Web Server Configuration

To ensure short links like `example.com/u/abc123` redirect correctly to `index.php`, you may need a rewrite rule if not using the query parameter style.

**Apache (.htaccess)**
The included `.htaccess` file handles this automatically:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Nginx**
Add this to your server block:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 📖 Documentation

For detailed technical documentation, including database schema and code structure, please refer to [DOCUMENTATION.md](DOCUMENTATION.md).

## 📄 License

Open Source. Feel free to modify and use for personal or commercial projects.
