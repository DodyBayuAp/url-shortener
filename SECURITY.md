# Security Policy

## 🔒 Supported Versions

We release patches for security vulnerabilities in the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 2.x.x   | ✅ Currently supported |
| 1.x.x   | ⚠️ Security fixes only |
| < 1.0   | ❌ Not supported |

## 🚨 Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security issue, please follow these steps:

### 1. **Do NOT** Create a Public Issue
Please do not report security vulnerabilities through public GitHub issues.

### 2. Report Privately
Send an email to: **[dody.bayu@gmail.com]** with:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if you have one)

### 3. Response Timeline
- **Initial Response**: Within 48 hours
- **Status Update**: Within 7 days
- **Fix Timeline**: Depends on severity
  - Critical: 1-7 days
  - High: 7-14 days
  - Medium: 14-30 days
  - Low: 30-90 days

### 4. Disclosure Policy
- We will acknowledge your email within 48 hours
- We will provide a detailed response within 7 days
- We will work with you to understand and validate the issue
- Once fixed, we will publicly disclose the vulnerability (with credit to you, if desired)

## 🛡️ Security Best Practices

When deploying this application, please follow these security guidelines:

### Database Security
- ✅ Use strong database passwords
- ✅ Restrict database access to localhost when possible
- ✅ Regularly backup your database
- ✅ Keep your database software updated

### Application Security
- ✅ Change the default admin password immediately
- ✅ Use HTTPS in production
- ✅ Keep PHP updated to the latest stable version
- ✅ Set appropriate file permissions (644 for files, 755 for directories)
- ✅ Disable PHP error display in production
- ✅ Enable PHP error logging

### Server Security
- ✅ Keep your web server (Apache/Nginx) updated
- ✅ Use a firewall to restrict access
- ✅ Implement rate limiting to prevent abuse
- ✅ Regularly review access logs
- ✅ Use fail2ban or similar tools to prevent brute force attacks

### Configuration
```php
// Recommended php.ini settings for production
display_errors = Off
log_errors = On
error_log = /path/to/php-error.log
expose_php = Off
session.cookie_httponly = 1
session.cookie_secure = 1  // If using HTTPS
session.cookie_samesite = "Strict"
```

## 🔐 Known Security Features

This application includes the following security features:

- ✅ **CSRF Protection**: All forms include CSRF tokens
- ✅ **Password Hashing**: Bcrypt with cost factor 10
- ✅ **Secure Sessions**: HttpOnly, Secure (HTTPS), SameSite cookies
- ✅ **Input Validation**: URL validation and sanitization
- ✅ **XSS Prevention**: Output escaping with htmlspecialchars()
- ✅ **SQL Injection Prevention**: Prepared statements with PDO
- ✅ **Forced Password Change**: On first login

## 📚 Security Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [PDO Security](https://www.php.net/manual/en/pdo.prepared-statements.php)

## 🙏 Hall of Fame

We appreciate security researchers who help keep this project secure:

<!-- Security researchers will be listed here after responsible disclosure -->

---

**Thank you for helping keep this project secure!** 🛡️
