# 🔗 Simple PHP URL Shortener

<div align="center">

![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)
![Database](https://img.shields.io/badge/Database-MySQL%20%7C%20PostgreSQL%20%7C%20SQLite-blue?style=flat-square)
![Single File](https://img.shields.io/badge/Single%20File-Yes-orange?style=flat-square)
![Stars](https://img.shields.io/github/stars/DodyBayuAp/url-shortener?style=flat-square)

**A lightweight, powerful, and single-file URL shortener written in PHP.**  
Designed for simplicity, ease of deployment, and robust functionality without the bloat of frameworks.

[Features](#-key-features) • [Demo](#-demo) • [Installation](#-installation--setup) • [Documentation](DOCUMENTATION.md) • [Contributing](CONTRIBUTING.md)

*Read this in [Bahasa Indonesia](README.id.md).*


</div>

---

## 📸 Demo

<div align="center">

### Dashboard Overview
![Dashboard](screenshots/dashboard.png)

### Setup Wizard
<img src="screenshots/setup-wizard.png" width="600" alt="Setup Wizard">

### Analytics, QR Codes & API Docs
<table>
  <tr>
    <td><img src="screenshots/analytics.png" alt="Analytics"></td>
    <td><img src="screenshots/qr-code.png" alt="QR Code"></td>
  </tr>
  <tr>
    <td colspan="2"><img src="screenshots/API-Docs.png" alt="API Documentation"></td>
  </tr>
</table>

</div>

---

## 🌟 Key Features

### 🚀 **Easy Deployment**
- **Single File Application**: Everything in one `index.php` file
- **Zero Dependencies**: No composer, no frameworks, just PHP
- **Auto-Configuration**: Interactive setup wizard on first run
- **Dynamic Path Handling**: Works in root domain or subdirectories

### 📊 **Comprehensive Analytics**
- Track clicks, unique visitors, and geographic location (Country/City)
- Device type, OS, and browser detection
- Interactive charts powered by Chart.js
- Referrer tracking and time-based analytics

### 🗄️ **Database Flexibility**
- **SQLite**: Zero configuration, perfect for small to medium sites
- **MySQL**: Robust production-ready option
- **PostgreSQL**: Enterprise-grade performance
- Auto-creates indexes for optimal query performance

### 🔌 **API & Integration**
- **Per-User API Keys**: Each user gets a unique key for API access
- **Flexible Authentication**: Supports Legacy API Keys, JWT (JSON Web Tokens), and PASETO (Platform-Agnostic Security Tokens)
- **Auto-Generated Docs**: Built-in Swagger UI with automatic OpenAPI 3.0 specification generation
- **Plain Text Output**: Optimized for Google Sheets `IMPORTDATA`
- **Whitelisting**: Configurable IP and User-Agent restrictions

### 🎨 **Modern Interface**
- Clean, responsive design with Bulma CSS
- Mobile-friendly dashboard
- **Multi-Theme Support**: 5 Built-in themes (Light, Dark, Midnight, Forest, Ocean) with a persistent theme selector.
- Bilingual: English & Indonesian

### 🔒 **Security First**
- CSRF protection on all forms
- Bcrypt password hashing
- Secure session handling
- Input validation and XSS prevention

### ⚡ **Performance Optimized**
- Built-in database indexing (10-100x faster queries)
- Daily statistics summary for high-volume sites
- Configurable data retention management
- Handles millions of visits efficiently

### 🎁 **Bonus Features**
- **QR Code Generation**: Instant QR codes for all short links
- **API Access**: Robust endpoint for shortening URLs externally (Google Sheets, bots, etc.) with full Swagger UI documentation
- **API Manager**: Dedicated admin page to configure API Authentication, IP Whitelisting, and Token Expiry
- **User Management**: Multi-user support with role-based access
- **Custom Short Codes**: Use your own memorable codes (supports `a-z`, `0-9`, `-`, `_`)
- **Bulk Operations**: Manage multiple URLs efficiently

---

## 🆚 Why Choose This?

| Feature | This Project | YOURLS | Polr | Shlink |
|---------|-------------|--------|------|--------|
| **Single File** | ✅ | ❌ | ❌ | ❌ |
| **Zero Config (SQLite)** | ✅ | ❌ | ❌ | ✅ |
| **Multi-Database** | ✅ (3 types) | ✅ (MySQL only) | ✅ (MySQL only) | ✅ (Multiple) |
| **Built-in Analytics** | ✅ Advanced | ✅ Basic | ✅ Basic | ✅ Advanced |
| **QR Codes** | ✅ | ❌ | ❌ | ✅ |
| **API Auth (JWT/PASETO)** | ✅ | ❌ | ❌ | 🟡 (JWT Only) |
| **Swagger UI Docs** | ✅ | ❌ | ❌ | ✅ |
| **Setup Complexity** | 🟢 Easy | 🟡 Medium | 🟡 Medium | 🟡 Medium |
| **Dependencies** | None | Many | Many | Many |
| **File Size** | ~80KB | ~5MB | ~10MB | ~20MB |

### 🔒 API Security Comparison

| Feature | This Project | Standard PHP Scripts |
|---------|--------------|----------------------|
| **Authentication** | PASETO / JWT / API Key | Legacy API Key only |
| **Token Expiry** | Configurable | Unlimited |
| **IP Whitelisting** | Built-in | None |
| **User-Agent Filter** | Built-in | None |
| **Documentation** | Auto-generated Swagger | Manual / None |
| **Rate Limiting** | Configurable | Basic / None |


**Perfect for:**
- 🏠 Self-hosters who want simplicity
- 🚀 Quick deployments without complex setup
- 📱 Small to medium businesses
- 🎓 Educational projects and learning
- 💼 Internal company link management

---

## 🚀 Installation & Setup

### Requirements
*   PHP 7.4 or higher
*   PDO Extension (usually included)
*   Web Server (Apache/Nginx) with URL rewriting *(optional but recommended)*
*   Database: MySQL 5.7+, PostgreSQL 9.5+, or SQLite 3.x

### Quick Start (3 Steps)

#### 1️⃣ **Download**
```bash
# Clone the repository to wwww or public_html folder
git clone https://github.com/DodyBayuAp/url-shortener
cd url-shortener

# Or download and extract the ZIP file
```

#### 2️⃣ **Run Setup Wizard**
Open your browser and navigate to the installation directory:
```
http://localhost/url-shortener/
```

The **Setup Wizard** will guide you through:
- **Choose Database**: SQLite (instant), MySQL, or PostgreSQL
- **Configure**: Enter credentials if using MySQL/PostgreSQL
- **Install**: Click "Save & Install" - done!

#### 3️⃣ **Login**
```
Username: admin
Password: admin
```
*You'll be prompted to change the password on first login.*

---

### ⚡ PHP Built-in Server (Development)

```bash
cd url-shortener
php -S localhost:8000
```

Open `http://localhost:8000` in your browser.

---

### 🐳 Docker Deployment

#### Option 1: Using Docker Compose (Recommended)
```bash
docker-compose up -d
```

#### Option 2: Build and Run Manually

**Linux/Mac:**
```bash
# Build the image
docker build -t url-shortener .

# Run the container
docker run -d -p 8080:80 --name url-shortener \
  -v $(pwd)/data:/var/www/html/data \
  url-shortener
```

**Windows PowerShell:**
```powershell
# Build the image
docker build -t url-shortener .

# Run the container
docker run -d -p 8080:80 --name url-shortener -v ${PWD}/data:/var/www/html/data url-shortener
```

Access the application at `http://localhost:8080`

See [Docker Guide](deploy/docker.md) for advanced configurations.


---

### 🌐 One-Click Deploy

[![Deploy to Heroku](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy?template=https://github.com/DodyBayuAp/url-shortener)
[![Deploy on Railway](https://railway.app/button.svg)](https://railway.app/new/template?template=https://github.com/DodyBayuAp/url-shortener)

See [Deployment Guides](deploy/) for more platforms.

---

## 🔧 Web Server Configuration

### Apache (.htaccess)
The included `.htaccess` file handles URL rewriting automatically:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### Nginx
Add this to your server block:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

See [nginx.conf.example](nginx.conf.example) for complete configuration.

---

## 📖 Documentation

- **[Technical Documentation](DOCUMENTATION.md)** - Database schema, architecture, customization
- **[Contributing Guide](CONTRIBUTING.md)** - How to contribute to this project
- **[Changelog](CHANGELOG.md)** - Version history and updates
- **[Deployment Guides](deploy/)** - Platform-specific deployment instructions

---

## 🤝 Contributing

We welcome contributions! Whether it's:
- 🐛 Bug reports
- 💡 Feature suggestions
- 📝 Documentation improvements
- 🔧 Code contributions

Please read our [Contributing Guide](CONTRIBUTING.md) to get started.

---

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

**TL;DR**: Free to use, modify, and distribute for personal or commercial projects.

---

## 💖 Support the Project

Mining code is hard work. If you like this project, please consider buying me a coffee!

<a href="https://paypal.me/DodyBayuArtaputra">
  <img src="https://img.shields.io/badge/PayPal-00457C?style=for-the-badge&logo=paypal&logoColor=white" alt="PayPal">
</a>
<a href="https://ko-fi.com/dodybayuap">
  <img src="https://img.shields.io/badge/Ko--fi-F16061?style=for-the-badge&logo=ko-fi&logoColor=white" alt="Ko-fi">
</a>
<a href="https://trakteer.id/dody_bayu_artaputra/tip">
  <img src="https://img.shields.io/badge/Trakteer-Donate-red?style=for-the-badge&logo=ko-fi&logoColor=white" alt="Trakteer">
</a>

---

## ⭐ Show Your Support

If you find this project useful, please consider:
- ⭐ **Starring this repository** to show your support
- 🐦 Sharing it on social media
- 🔗 Using it in your projects
- 🤝 Contributing improvements

**Every star motivates us to keep improving!** 🚀

---

## 🙏 Acknowledgments

- Built with ❤️ using native PHP
- UI powered by [Bulma CSS](https://bulma.io/)
- Charts by [Chart.js](https://www.chartjs.org/)
- QR Codes by [QRCode.js](https://davidshimjs.github.io/qrcodejs/)
- Geolocation by [IP-API](https://ip-api.com/)

---

<div align="center">

**Made with ❤️ for the open-source community**

[⬆ Back to Top](#-simple-php-url-shortener)

</div>
