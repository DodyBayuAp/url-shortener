<?php
// --- CONFIGURATION START ---
$configured = true;
$dbType = 'sqlite'; // Database type: 'sqlite', 'mysql', or 'pgsql'
$dbHost = 'localhost';
$dbName = 'url_shortener';
$dbUser = 'root';
$dbPass = '';
$dbPort = ''; // Optional: MySQL default 3306, PostgreSQL default 5432

// Customize App Appearance
$appTitle = 'URL Shortener'; // Nama Aplikasi
$appLogo = 'logo.png';       // Nama file logo (harus ada di folder yang sama)
$appFavicon = 'favicon.ico'; // Nama file favicon (harus ada di folder yang sama)
$appLang = 'en';             // Language: 'en' or 'id'

// Database Optimization Settings
$enableIndexes = true;        // Enable automatic index creation (recommended: true)
$dataRetentionDays = 365;     // Keep visit data for X days (0 = keep forever, recommended: 365)
$enableDailySummary = true;   // Enable daily statistics summary table (recommended: true for >100K visits)
$autoArchiveOldData = false;  // Automatically archive old data (recommended: true for >1M visits)

// API Settings
$apiEnabled = true;
$apiAllowedUserAgents = 'google,Sheets,googlebot,Mozilla'; // Default allowed user agents
$apiAllowedIPs = ''; // Comma separated IPs, empty means all IPs allowed
$apiTokenExpiry = 3600; // Token lifetime in seconds (default 1 hour)
// --- CONFIGURATION END ---

// Helper for Translation
function __($key) {
    global $appLang;
    static $translations = null;
    if ($translations === null) {
        $translations = getTranslations();
    }
    return $translations[$appLang][$key] ?? ($translations['en'][$key] ?? $key);
}

// Helper for Error Pages (Early Declaration)
function renderErrorPage($title, $message, $showRetry = false) {
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>" . __('error_title') . " - $title</title>";
    echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css'>";
    echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>";
    echo "<style>body { background-color: #f5f7fa; min-height: 100vh; display: flex; align-items: center; justify-content: center; }</style>";
    echo "</head><body>";
    echo "<div class='container'><div class='columns is-centered'><div class='column is-6'>";
    echo "<div class='box has-text-centered p-6'>";
    echo "<div class='icon is-large has-text-danger mb-4'><i class='fas fa-exclamation-triangle fa-3x'></i></div>";
    echo "<h1 class='title is-4'>$title</h1>";
    echo "<p class='subtitle is-6'>$message</p>";
    if ($showRetry) {
        echo "<a href='" . $_SERVER['REQUEST_URI'] . "' class='button is-primary mt-4'>Coba Lagi</a>";
    }
    echo "</div></div></div></div>";
    echo "</body></html>";
    exit;
}

// PHP CLI Server Static File Handling
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

// Determine BASE_PATH
$scriptPath = $_SERVER['SCRIPT_NAME'];
$basePath = dirname($scriptPath);
$basePath = str_replace('\\', '/', $basePath);
if ($basePath === '/') {
    $basePath = '';
} else {
    $basePath = rtrim($basePath, '/');
}
define('BASE_PATH', $basePath);

// --- SETUP WIZARD LOGIC ---
if (!$configured) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $type = $_POST['db_type'] ?? 'sqlite';
        $host = $_POST['db_host'] ?? 'localhost';
        $name = $_POST['db_name'] ?? 'url_shortener';
        $user = $_POST['db_user'] ?? 'root';
        $pass = $_POST['db_pass'] ?? '';
        $port = $_POST['db_port'] ?? '';

        // Test Connection
        try {
            if ($type === 'mysql') {
                $portStr = $port ?: '3306';
                $dsn = "mysql:host=$host;port=$portStr;dbname=$name;charset=utf8mb4";
                try {
                    $pdoTest = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                } catch (PDOException $e) {
                    if ($e->getCode() == 1049) { // Unknown database
                        $dsnNoDb = "mysql:host=$host;port=$portStr;charset=utf8mb4";
                        $pdoTest = new PDO($dsnNoDb, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                        $pdoTest->exec("CREATE DATABASE IF NOT EXISTS `$name`");
                        $dsn = "mysql:host=$host;port=$portStr;dbname=$name;charset=utf8mb4";
                        $pdoTest = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    } else {
                        throw $e;
                    }
                }
            } elseif ($type === 'pgsql') {
                $portStr = $port ?: '5432';
                $dsn = "pgsql:host=$host;port=$portStr;dbname=$name";
                try {
                    $pdoTest = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                } catch (PDOException $e) {
                    // Try to create database if it doesn't exist
                    if (strpos($e->getMessage(), 'does not exist') !== false) {
                        $dsnNoDb = "pgsql:host=$host;port=$portStr;dbname=postgres";
                        $pdoTest = new PDO($dsnNoDb, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                        $pdoTest->exec("CREATE DATABASE $name");
                        $dsn = "pgsql:host=$host;port=$portStr;dbname=$name";
                        $pdoTest = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    } else {
                        throw $e;
                    }
                }
            } else {
                // SQLite
                $dbFile = __DIR__ . '/url_shortener.sqlite';
                if (!is_writable(__DIR__)) {
                   throw new Exception("Directory tidak writable. Tidak bisa membuat file SQLite.");
                }
            }

            // Connection is OK. Update this file dynamically.
            $content = file_get_contents(__FILE__);
            
            $updates = [
                'configured' => 'true',
                'dbType' => "'$type'",
                'dbHost' => "'$host'",
                'dbName' => "'$name'",
                'dbUser' => "'$user'",
                'dbPass' => "'$pass'",
                'dbPort' => "'$port'"
            ];

            $newContent = $content;
            foreach ($updates as $key => $val) {
                $pattern = '/\$' . $key . '\s*=\s*.*?;/';
                $newContent = preg_replace($pattern, '$' . $key . ' = ' . $val . ';', $newContent);
            }

            if ($newContent === null || $newContent === $content) {
                throw new Exception("Gagal mengupdate konfigurasi file.");
            }

            if (file_put_contents(__FILE__, $newContent) === false) {
                throw new Exception("Gagal menulis ke file index.php.");
            }

            // Redirect to self
            header('Location: ' . BASE_PATH . '/');
            exit;

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }

    // Render Setup Form
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>" . __('setup_wizard') . "</title>";
    if (file_exists(__DIR__ . '/' . $appFavicon)) {
        echo "<link rel='icon' href='" . BASE_PATH . "/$appFavicon' type='image/x-icon'>"; 
    }
    echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css'>";
    echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>";
    echo "</head><body>";
    echo "<section class='hero is-fullheight is-light'>";
    echo "<div class='hero-body'><div class='container'><div class='columns is-centered'><div class='column is-5'>";
    echo "<div class='box'>";
    echo "<div class='has-text-centered mb-5'>";
    if (file_exists(__DIR__ . '/' . $appLogo)) {
        echo "<img src='$appLogo' alt='Logo' style='height:80px'>";
    }
    echo "<h1 class='title is-4 mt-3'>$appTitle</h1><p class='subtitle is-6'>" . __('setup_config') . "</p>";
    echo "</div>";
    
    if ($error) echo "<div class='notification is-danger'>$error</div>";

    echo "<form method='post'>";
    echo "<div class='field'><label class='label'>" . __('db_type') . "</label><div class='control'>";
    echo "<div class='select is-fullwidth'><select name='db_type' id='dbType' onchange='toggleFields()'>";
    echo "<option value='sqlite'>" . __('sqlite_opt') . "</option><option value='mysql'>" . __('mysql_opt') . "</option><option value='pgsql'>PostgreSQL</option>";
    echo "</select></div></div></div>";

    echo "<div id='mysqlFields' style='display:none;'>";
    echo "<div class='field'><label class='label'>" . __('db_host') . "</label><div class='control has-icons-left'><input class='input' type='text' name='db_host' value='localhost'><span class='icon is-small is-left'><i class='fas fa-server'></i></span></div></div>";
    echo "<div class='field'><label class='label'>" . __('db_name') . "</label><div class='control has-icons-left'><input class='input' type='text' name='db_name' value='url_shortener'><span class='icon is-small is-left'><i class='fas fa-database'></i></span></div></div>";
    echo "<div class='field'><label class='label'>" . __('db_user') . "</label><div class='control has-icons-left'><input class='input' type='text' name='db_user' value='root'><span class='icon is-small is-left'><i class='fas fa-user'></i></span></div></div>";
    echo "<div class='field'><label class='label'>" . __('db_pass') . "</label><div class='control has-icons-left'><input class='input' type='password' name='db_pass'><span class='icon is-small is-left'><i class='fas fa-key'></i></span></div></div>";
    echo "<div class='field'><label class='label'>Port (Optional)</label><div class='control has-icons-left'><input class='input' type='text' name='db_port' placeholder='3306 for MySQL, 5432 for PostgreSQL'><span class='icon is-small is-left'><i class='fas fa-plug'></i></span></div></div>";
    echo "</div>";

    echo "<div class='field mt-5'><button class='button is-primary is-fullwidth' type='submit'>" . __('save_install') . "</button></div>";
    echo "</form>";
    
    echo "</div></div></div></div></div></section>";
    
    echo "<script>
    function toggleFields() {
        var type = document.getElementById('dbType').value;
        document.getElementById('mysqlFields').style.display = (type === 'mysql' || type === 'pgsql') ? 'block' : 'none';
    }
    </script>";
    echo "</body></html>";
    exit;
}

if ($dbType === 'mysql') {
    $port = $dbPort ?: '3306';
    $dsn = "mysql:host=$dbHost;port=$port;dbname=$dbName;charset=utf8mb4";
    $user = $dbUser;
    $pass = $dbPass;
} elseif ($dbType === 'pgsql') {
    $port = $dbPort ?: '5432';
    $dsn = "pgsql:host=$dbHost;port=$port;dbname=$dbName";
    $user = $dbUser;
    $pass = $dbPass;
} elseif ($dbType === 'sqlite') {
    $dbFile = __DIR__ . '/url_shortener.sqlite';
    $dsn = "sqlite:$dbFile";
    $user = null;
    $pass = null;
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user ?? null, $pass ?? null, $options);

    // Buat tabel users
    if ($dbType === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password TEXT NOT NULL,
            is_admin INTEGER DEFAULT 0,
            force_change_password INTEGER DEFAULT 0
        );");
    } elseif ($dbType === 'pgsql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password TEXT NOT NULL,
            is_admin INTEGER DEFAULT 0,
            force_change_password INTEGER DEFAULT 0
        );");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            password TEXT NOT NULL,
            is_admin INTEGER DEFAULT 0,
            force_change_password INTEGER DEFAULT 0
        );");
    }

    // Migration: Add columns if not exists
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN force_change_password INTEGER DEFAULT 0");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin INTEGER DEFAULT 0");
        $pdo->exec("UPDATE users SET is_admin = 1 WHERE username = 'admin'");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN api_key VARCHAR(64)");
    } catch (Exception $e) {}
    
    // Ensure all users have an api_key
    $stmt = $pdo->query("SELECT id FROM users WHERE api_key IS NULL OR api_key = ''");
    $usersWithoutKey = $stmt->fetchAll();
    foreach ($usersWithoutKey as $u) {
        $key = bin2hex(random_bytes(16));
        $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?")->execute([$key, $u['id']]);
    }

    // Buat tabel urls
    if ($dbType === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS urls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            long_url TEXT NOT NULL,
            short_code VARCHAR(50) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            user_id INTEGER DEFAULT 1,
            INDEX idx_user_id (user_id),
            INDEX idx_short_code (short_code)
        );");
    } elseif ($dbType === 'pgsql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS urls (
            id SERIAL PRIMARY KEY,
            long_url TEXT NOT NULL,
            short_code VARCHAR(50) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            user_id INTEGER DEFAULT 1
        );");
        // Create indexes separately for PostgreSQL
        try {
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_urls_user_id ON urls(user_id)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_urls_short_code ON urls(short_code)");
        } catch (Exception $e) {}
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS urls (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            long_url TEXT NOT NULL,
            short_code VARCHAR(50) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            user_id INTEGER DEFAULT 1
        );");
        // Create indexes for SQLite
        try {
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_urls_user_id ON urls(user_id)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_urls_short_code ON urls(short_code)");
        } catch (Exception $e) {}
    }

    // Migration: Add user_id to urls if not exists
    try {
        $pdo->exec("ALTER TABLE urls ADD COLUMN user_id INTEGER DEFAULT 1");
    } catch (Exception $e) {}

    // Buat tabel visits (Statistik) dengan optimasi
    if ($dbType === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            url_id INTEGER,
            ip_address VARCHAR(45),
            country VARCHAR(100),
            city VARCHAR(100),
            user_agent TEXT,
            referrer TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_url_id (url_id),
            INDEX idx_created_at (created_at),
            INDEX idx_ip_address (ip_address),
            INDEX idx_url_date (url_id, created_at),
            FOREIGN KEY(url_id) REFERENCES urls(id) ON DELETE CASCADE
        );");
    } elseif ($dbType === 'pgsql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS visits (
            id SERIAL PRIMARY KEY,
            url_id INTEGER,
            ip_address VARCHAR(45),
            country VARCHAR(100),
            city VARCHAR(100),
            user_agent TEXT,
            referrer TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(url_id) REFERENCES urls(id) ON DELETE CASCADE
        );");
        // Create indexes for PostgreSQL
        if ($enableIndexes) {
            try {
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_url_id ON visits(url_id)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_created_at ON visits(created_at)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_ip_address ON visits(ip_address)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_url_date ON visits(url_id, created_at)");
            } catch (Exception $e) {}
        }
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS visits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            url_id INTEGER,
            ip_address VARCHAR(45),
            country VARCHAR(100),
            city VARCHAR(100),
            user_agent TEXT,
            referrer TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(url_id) REFERENCES urls(id) ON DELETE CASCADE
        );");
        // Create indexes for SQLite
        if ($enableIndexes) {
            try {
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_url_id ON visits(url_id)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_created_at ON visits(created_at)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_ip_address ON visits(ip_address)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_url_date ON visits(url_id, created_at)");
            } catch (Exception $e) {}
        }
    }

    // Buat tabel daily_stats untuk summary (jika enabled)
    if ($enableDailySummary) {
        if ($dbType === 'mysql') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS daily_stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                url_id INT,
                stat_date DATE,
                total_clicks INT DEFAULT 0,
                unique_visitors INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_url_date (url_id, stat_date),
                INDEX idx_stat_date (stat_date),
                INDEX idx_url_id (url_id),
                FOREIGN KEY(url_id) REFERENCES urls(id) ON DELETE CASCADE
            );");
        } elseif ($dbType === 'pgsql') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS daily_stats (
                id SERIAL PRIMARY KEY,
                url_id INTEGER,
                stat_date DATE,
                total_clicks INTEGER DEFAULT 0,
                unique_visitors INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (url_id, stat_date),
                FOREIGN KEY(url_id) REFERENCES urls(id) ON DELETE CASCADE
            );");
            try {
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_daily_stats_date ON daily_stats(stat_date)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_daily_stats_url ON daily_stats(url_id)");
            } catch (Exception $e) {}
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS daily_stats (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                url_id INTEGER,
                stat_date DATE,
                total_clicks INTEGER DEFAULT 0,
                unique_visitors INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (url_id, stat_date),
                FOREIGN KEY(url_id) REFERENCES urls(id) ON DELETE CASCADE
            );");
            try {
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_daily_stats_date ON daily_stats(stat_date)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_daily_stats_url ON daily_stats(url_id)");
            } catch (Exception $e) {}
        }
    }

    // Auto cleanup old data (jika enabled)
    if ($autoArchiveOldData && $dataRetentionDays > 0) {
        try {
            if ($dbType === 'mysql') {
                $pdo->exec("DELETE FROM visits WHERE created_at < DATE_SUB(NOW(), INTERVAL $dataRetentionDays DAY)");
            } elseif ($dbType === 'pgsql') {
                $pdo->exec("DELETE FROM visits WHERE created_at < NOW() - INTERVAL '$dataRetentionDays days'");
            } else {
                $pdo->exec("DELETE FROM visits WHERE created_at < datetime('now', '-$dataRetentionDays days')");
            }
        } catch (Exception $e) {}
    }

    // Tambah pengguna admin jika belum ada
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        // Default password: admin
        $hashedPassword = password_hash('admin', PASSWORD_BCRYPT);
        // Force change password = 1, is_admin = 1
        $pdo->prepare("INSERT INTO users (username, password, force_change_password, is_admin) VALUES (?, ?, 1, 1)")->execute(['admin', $hashedPassword]);
    }
} catch (PDOException $e) {
    renderErrorPage("Gagal Koneksi Database", "Tidak dapat terhubung ke database. Pastikan konfigurasi benar.<br>Error: " . htmlspecialchars($e->getMessage()), true);
}

// Fungsi Generate Kode Unik
function generateCode($length = 6) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

// Mendapatkan URL Basis Dinamis
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'];
}

// Fungsi Geo Location Sederhana (Timeout Cepat)
function getGeoLocation($ip) {
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return ['country' => 'Localhost', 'city' => 'Localhost'];
    }
    
    // Gunakan ip-api.com (Gratis, non-SSL ok)
    $url = "http://ip-api.com/json/$ip?fields=country,city";
    
    $ctx = stream_context_create(['http' => ['timeout' => 1]]); // Timeout 1 detik
    $json = @file_get_contents($url, false, $ctx);
    
    if ($json) {
        $data = json_decode($json, true);
        if ($data) {
            return [
                'country' => $data['country'] ?? 'Unknown',
                'city' => $data['city'] ?? 'Unknown'
            ];
        }
    }
    
    return ['country' => 'Unknown', 'city' => 'Unknown'];
}

// Routing Sederhana
// Secure Session
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']), // True if HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

function validateCsrf() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die(__('error_csrf'));
    }
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Helper untuk render view sederhana
function renderHeader($title) {
    global $appFavicon; // Access global config
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>$title</title>";
    if (file_exists(__DIR__ . '/' . $appFavicon)) {
        echo "<link rel='icon' href='" . BASE_PATH . "/$appFavicon' type='image/x-icon'>";
    }
    echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css'>";
    echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>";
    echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'></script>";
    echo "<style>
            :root { --primary-blue: #007bff; /* Tut Wuri Blue Approx */ --primary-yellow: #ffc107; }
            body { background-color: #f5f7fa; min-height: 100vh; }
            .hero-body { padding-top: 3rem; padding-bottom: 3rem; }
            .navbar.is-info { background-color: var(--primary-blue); }
            .button.is-primary { background-color: var(--primary-blue); }
            .button.is-primary:hover { background-color: #0056b3; }
            .title { color: #363636; }
            @media screen and (max-width: 768px) {
                .table.is-responsive-cards thead { display: none; }
                .table.is-responsive-cards tbody tr { display: block; margin-bottom: 1rem; border: 1px solid #dbdbdb; border-radius: 4px; padding: 1rem; background: white; box-shadow: 0 2px 3px rgba(10, 10, 10, 0.1); }
                .table.is-responsive-cards tbody td { display: flex; justify-content: space-between; border: none; padding: 0.5rem 0; text-align: right; }
                .table.is-responsive-cards tbody td:before { content: attr(data-label); font-weight: bold; margin-right: 1rem; text-align: left; }
                .table.is-responsive-cards tbody td:last-child { border-bottom: none; }
            }
          </style>";
    echo "</head><body>";
}

function renderFooter() {
    echo "</body></html>";
}

// 0. API Endpoint (Query String)
if ($uri === BASE_PATH . '/api.php' || $uri === BASE_PATH . '/api-shorten') {
    if (!$apiEnabled) {
        http_response_code(403);
        die("ERROR: API is disabled.");
    }

    // Security Check: User-Agent
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $isAllowedUA = false;
    $allowedUAs = explode(',', $apiAllowedUserAgents);
    foreach ($allowedUAs as $allowed) {
        if (stripos($ua, trim($allowed)) !== false) {
            $isAllowedUA = true;
            break;
        }
    }
    if (!$isAllowedUA) {
        http_response_code(403);
        die("ERROR: Access Denied (User-Agent).");
    }

    // Security Check: IP Whitelist
    if (!empty($apiAllowedIPs)) {
        $clientIP = $_SERVER['REMOTE_ADDR'];
        $allowedIPs = explode(',', $apiAllowedIPs);
        if (!in_array($clientIP, array_map('trim', $allowedIPs))) {
            http_response_code(403);
            die("ERROR: Access Denied (IP).");
        }
    }

    // Security Check: Token (ids) or Session
    $token = $_GET['ids'] ?? '';
    $identifiedUser = null;

    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
        // Option 1: Logged in session
        $identifiedUser = [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'api_key' => '' // We don't need the key for session-based access
        ];
    } elseif (!empty($token)) {
        // Option 2: Token-based
        // Support both static API Key and Dynamic Token
        
        // Find user by static key first
        $stmt = $pdo->prepare("SELECT id, username, api_key FROM users WHERE api_key = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $identifiedUser = $user;
        } else {
            // Try as Dynamic Token: base64_encode(timestamp . ":" . hmac(timestamp, user_api_key))
            $decoded = base64_decode($token);
            if ($decoded && strpos($decoded, ':') !== false) {
                list($ts, $hash) = explode(':', $decoded, 2);
                if (abs(time() - (int)$ts) <= $apiTokenExpiry) {
                    // We need to find which user this token belongs to. 
                    // To do this efficiently, we might need a uid parameter, 
                    // but if not provided, we must check all users (not recommended for large DBs).
                    // For now, let's look for a user ID hint in the token or just check all (small scale).
                    $stmtAll = $pdo->query("SELECT id, username, api_key FROM users");
                    while ($userCandidate = $stmtAll->fetch()) {
                        $expectedHash = hash_hmac('sha256', (string)$ts, $userCandidate['api_key']);
                        if (hash_equals($expectedHash, $hash)) {
                            $identifiedUser = $userCandidate;
                            break;
                        }
                    }
                }
            }
        }
    }

    if (!$identifiedUser) {
        http_response_code(403);
        die("ERROR: Invalid or expired token (ids required if not logged in).");
    }

    // Parameters
    $longUrl = $_GET['longurl'] ?? '';
    $unique = $_GET['unique'] ?? '';

    if (empty($longUrl) || !filter_var($longUrl, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        die("ERROR: Invalid longurl.");
    }

    if (empty($unique)) {
        $unique = generateCode();
    } else {
        // Validasi short code
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $unique)) {
            http_response_code(400);
            die("ERROR: Invalid unique code format.");
        }
        // Cek duplikat
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM urls WHERE short_code = ?");
        $stmt->execute([$unique]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            die("ERROR: Unique code already taken.");
        }
    }

    // Save
    try {
        $stmt = $pdo->prepare("INSERT INTO urls (long_url, short_code, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$longUrl, $unique, $identifiedUser['id']]);
        
        $baseUrl = getBaseUrl();
        echo $baseUrl . BASE_PATH . "/" . $unique;
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        die("ERROR: " . $e->getMessage());
    }
}

// 0.5 API Settings Page
if ($uri === BASE_PATH . '/api') {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || empty($_SESSION['is_admin'])) {
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }

    if ($method === 'POST') {
        validateCsrf();
        
        if (isset($_POST['regenerate_my_key'])) {
            $newKey = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?");
            $stmt->execute([$newKey, $_SESSION['user_id']]);
            header('Location: ' . BASE_PATH . '/api?updated=1');
            exit;
        }

        $enabled = isset($_POST['api_enabled']) ? 'true' : 'false';
        $uas = $_POST['api_allowed_ua'] ?? $apiAllowedUserAgents;
        $ips = $_POST['api_allowed_ips'] ?? $apiAllowedIPs;
        $expiry = $_POST['api_expiry'] ?? $apiTokenExpiry;

        // Self-modify config
        $content = file_get_contents(__FILE__);
        
        $configs = [
            'apiEnabled' => $enabled,
            'apiAllowedUserAgents' => "'$uas'",
            'apiAllowedIPs' => "'$ips'",
            'apiTokenExpiry' => (int)$expiry
        ];

        foreach ($configs as $key => $val) {
            $pattern = '/\$' . $key . '\s*=\s*.*?;/';
            $content = preg_replace($pattern, '$' . $key . ' = ' . $val . ';', $content);
        }

        file_put_contents(__FILE__, $content);
        header('Location: ' . BASE_PATH . '/api?updated=1');
        exit;
    }

    // Get current user's API key
    $stmt = $pdo->prepare("SELECT api_key FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUserKey = $stmt->fetchColumn();

    renderHeader(__('api_title'));
    echo "<nav class='navbar is-info' style='background-color: #007bff;'><div class='container'><div class='navbar-brand'><a class='navbar-item' href='" . BASE_PATH . "/admin'><b>&larr; " . __('back_dashboard') . "</b></a></div></div></nav>";

    echo "<section class='section'><div class='container'><div class='columns is-centered'><div class='column is-8'>";
    echo "<h1 class='title'>" . __('api_title') . "</h1>";
    
    if (isset($_GET['updated'])) echo "<div class='notification is-success'>Settings updated.</div>";

    // User's API Key Section
    echo "<div class='box'><h3 class='title is-5'>" . __('your_api_key') . "</h3>";
    echo "<div class='field has-addons'><div class='control is-expanded'><input class='input' type='text' value='$currentUserKey' readonly id='myApiKey'></div>";
    echo "<div class='control'><button class='button is-info' onclick='copyToClipboard(document.getElementById(\"myApiKey\").value)'>" . __('copy') . "</button></div></div>";
    echo "<form method='post' onsubmit='return confirm(\"" . __('confirm_regen_key') . "\")'><input type='hidden' name='regenerate_my_key' value='1'>";
    echo csrfField();
    echo "<button class='button is-warning is-small mt-2' type='submit'>" . __('regen_key_btn') . "</button></form>";
    echo "</div>";

    // Global Admin Settings (Only shown to admin)
    echo "<div class='box'><h3 class='title is-5'>" . __('global_api_settings') . "</h3>";
    echo "<form method='post'>";
    echo csrfField();
    echo "<div class='field'><label class='checkbox'><input type='checkbox' name='api_enabled' " . ($apiEnabled ? 'checked' : '') . "> " . __('api_enabled') . "</label></div>";
    echo "<div class='field'><label class='label'>" . __('api_allowed_ua') . "</label><div class='control'><input class='input' type='text' name='api_allowed_ua' value='$apiAllowedUserAgents'></div><p class='help'>Comma separated.</p></div>";
    echo "<div class='field'><label class='label'>" . __('api_allowed_ips') . "</label><div class='control'><input class='input' type='text' name='api_allowed_ips' value='$apiAllowedIPs'></div><p class='help'>Comma separated (empty for all).</p></div>";
    echo "<div class='field'><label class='label'>" . __('api_expiry') . "</label><div class='control'><input class='input' type='number' name='api_expiry' value='$apiTokenExpiry'></div></div>";
    echo "<div class='field mt-5'><button class='button is-primary is-fullwidth' type='submit'>" . __('save_pass') . "</button></div>";
    echo "</form>";
    echo "</div>";

    // Example URL Section
    $ts = time();
    $hash = hash_hmac('sha256', (string)$ts, $currentUserKey);
    $testToken = base64_encode($ts . ":" . $hash);
    $baseUrl = getBaseUrl();
    
    // Dynamic Token URL (Recommended)
    $exampleUrlToken = $baseUrl . BASE_PATH . "/api-shorten?ids=" . $testToken . "&longurl=https://google.com&unique=test" . rand(100,999);
    // Session-based URL (Optional)
    $exampleUrlSession = $baseUrl . BASE_PATH . "/api-shorten?longurl=https://google.com&unique=test_session" . rand(100,999);

    echo "<div class='box'><h3 class='title is-5'>" . __('api_usage_url') . "</h3>";
    
    echo "<label class='label is-small'>" . __('example_token_url') . "</label>";
    echo "<div class='field has-addons'><div class='control is-expanded'><input class='input is-small' id='apiExample' value='$exampleUrlToken' readonly></div>";
    echo "<div class='control'><button class='button is-small is-info' onclick='copyToClipboard(document.getElementById(\"apiExample\").value)'>" . __('copy') . "</button></div></div>";
    echo "<p class='help mb-3'>Token dynamic valid $apiTokenExpiry sec.</p>";

    echo "<label class='label is-small'>" . __('example_session_url') . "</label>";
    echo "<div class='field has-addons'><div class='control is-expanded'><input class='input is-small' id='apiExampleSession' value='$exampleUrlSession' readonly></div>";
    echo "<div class='control'><button class='button is-small is-info' onclick='copyToClipboard(document.getElementById(\"apiExampleSession\").value)'>" . __('copy') . "</button></div></div>";
    echo "<p class='help mb-3'>" . __('session_url_help') . "</p>";

    echo "<a href='$exampleUrlToken' target='_blank' class='button is-small is-link is-outlined'>" . __('api_test') . "</a>";
    echo "</div>";

    echo "</div></div></div></section>";
    echo "<script>function copyToClipboard(text) { navigator.clipboard.writeText(text).then(() => { alert('" . __('link_copied') . "'); }); }</script>";
    renderFooter();
    exit;
}

// ROUTING LOGIC

// 1. Logout
if ($uri === BASE_PATH . '/logout') {
    session_destroy();
    header('Location: ' . BASE_PATH . '/login');
    exit;
}

// 2. Login
if ($uri === BASE_PATH . '/login') {
    // 2.1 Auto-redirect if already logged in
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
        header('Location: ' . BASE_PATH . '/admin');
        exit;
    }

    if ($method === 'POST') {
        validateCsrf();
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;

            // Cek force_change_password
            if (isset($user['force_change_password']) && $user['force_change_password'] == 1) {
                $_SESSION['force_change'] = true;
                header('Location: ' . BASE_PATH . '/change-password');
                exit;
            }

            header('Location: ' . BASE_PATH . '/admin');
            exit;
        } else {
            $error = "Username atau password salah.";
        }
    }

    renderHeader("Login");
    echo "<section class='hero is-fullheight'>";
    echo "<div class='hero-body'>";
    echo "<div class='container'>";
    echo "<div class='columns is-centered'>";
    echo "<div class='column is-5-tablet is-4-desktop is-3-widescreen'>";
    echo "<div class='box has-text-centered'>";
    if (file_exists(__DIR__ . '/' . $appLogo)) {
        echo "<figure class='image is-128x128 is-inline-block mb-4'><img src='$appLogo' alt='Logo Details'></figure>";
    }
    echo "<h1 class='title has-text-centered'>" . __('login') . "</h1>";
    if (isset($error)) echo "<div class='notification is-danger is-light'>$error</div>";
    echo "<form method='post'>";
    echo csrfField();
    echo "<div class='field'><label class='label has-text-left'>" . __('username') . "</label><div class='control has-icons-left'><input class='input' type='text' name='username' placeholder='" . __('username') . "' required><span class='icon is-small is-left'><i class='fas fa-user'></i></span></div></div>";
    echo "<div class='field'><label class='label has-text-left'>" . __('password') . "</label><div class='control has-icons-left'><input class='input' type='password' name='password' placeholder='" . __('password') . "' required><span class='icon is-small is-left'><i class='fas fa-lock'></i></span></div></div>";
    echo "<div class='field'><button class='button is-primary is-fullwidth' type='submit'>" . __('login_btn') . "</button></div>";
    echo "</form>";
    // echo "<p class='has-text-centered mt-4'>Belum punya akun? <a href='/u/register'>Daftar</a></p>";
    echo "</div>"; // box
    echo "</div>"; // column
    echo "</div>"; // columns
    echo "</div>"; // container
    echo "</div>"; // hero-body
    echo "</section>";
    renderFooter();
    exit;
}

// 2.5 Change Password (Force Change)
if ($uri === BASE_PATH . '/change-password') {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }

    $message = "";
    if ($method === 'POST') {
        validateCsrf();
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        
        if (strlen($newPass) < 6) {
            $message = "<div class='notification is-warning'>" . __('msg_pass_short') . "</div>";
        } elseif ($newPass !== $confirmPass) {
            $message = "<div class='notification is-danger'>" . __('msg_pass_mismatch') . "</div>";
        } else {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            // Update password and reset force_change_password
            $stmt = $pdo->prepare("UPDATE users SET password = ?, force_change_password = 0 WHERE username = ?");
            if ($stmt->execute([$hashed, $_SESSION['username']])) {
                unset($_SESSION['force_change']);
                $message = "<div class='notification is-success'>" . __('msg_pass_reset') . "</div><script>setTimeout(() => window.location.href='" . BASE_PATH . "/admin', 2000);</script>";
            } else {
                $message = "<div class='notification is-danger'>Fail.</div>";
            }
        }
    }

    renderHeader(__('change_pass'));
    echo "<section class='hero is-fullheight'>";
    echo "<div class='hero-body'>";
    echo "<div class='container'>";
    echo "<div class='columns is-centered'>";
    echo "<div class='column is-5-tablet is-4-desktop is-3-widescreen'>";
    echo "<div class='box'>";
    echo "<h1 class='title has-text-centered'>" . __('change_pass') . "</h1>";
    echo "<p class='subtitle has-text-centered is-6 mb-4'>" . __('must_change_pass') . "</p>";
    if (!empty($message)) echo $message;
    echo "<form method='post'>";
    echo csrfField();
    echo "<div class='field'><label class='label'>" . __('new_pass') . "</label><div class='control'><input class='input' type='password' name='new_password' required></div></div>";
    echo "<div class='field'><label class='label'>" . __('confirm_pass') . "</label><div class='control'><input class='input' type='password' name='confirm_password' required></div></div>";
    echo "<div class='field'><button class='button is-warning is-fullwidth' type='submit'>" . __('save_pass') . "</button></div>";
    echo "</form>";
    echo "<div class='has-text-centered mt-4'><a href='" . BASE_PATH . "/logout'>" . __('logout') . "</a></div>";
    echo "</div>";
    echo "</div></div></div></div>";
    echo "</section>";
    renderFooter();
    exit;
}

// 3. Register (Restricted to Logged in Users or Removed)
// Modified so that only logged-in users can access it, or just remove it if admin only
// For now, let's keep the block but add access control
if ($uri === BASE_PATH . '/register') {
    // Check if logged in
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }

    if ($method === 'POST') {
        validateCsrf();
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (strlen($password) < 6) {
            $error = __('msg_pass_short');
        } else {
            // Cek username
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = __('msg_username_taken');
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                if ($stmt->execute([$username, $hashedPassword])) {
                    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
                         header('Location: ' . BASE_PATH . '/users');
                    } else {
                         header('Location: ' . BASE_PATH . '/login');
                    }
                    exit;
                } else {
                    $error = __('msg_register_fail');
                }
            }
        }
    }

    renderHeader(__('register'));
    echo "<section class='hero is-fullheight'>";
    echo "<div class='hero-body'>";
    echo "<div class='container'>";
    echo "<div class='columns is-centered'>";
    echo "<div class='column is-5-tablet is-4-desktop is-3-widescreen'>";
    echo "<div class='box'>";
    echo "<h1 class='title has-text-centered'>" . __('register') . "</h1>";
    if (isset($error)) echo "<div class='notification is-danger is-light'>$error</div>";
    echo "<form method='post'>";
    echo csrfField();
    echo "<div class='field'><label class='label'>" . __('username') . "</label><div class='control has-icons-left'><input class='input' type='text' name='username' placeholder='" . __('username') . "' required><span class='icon is-small is-left'><i class='fas fa-user'></i></span></div></div>";
    echo "<div class='field'><label class='label'>" . __('password') . "</label><div class='control has-icons-left'><input class='input' type='password' name='password' placeholder='" . __('password') . "' required><span class='icon is-small is-left'><i class='fas fa-lock'></i></span></div></div>";
    echo "<div class='field'><button class='button is-primary is-fullwidth' type='submit'>" . __('register_btn') . "</button></div>";
    echo "</form>";
    echo "<p class='has-text-centered mt-4'>" . __('have_account') . " <a href='" . BASE_PATH . "/login'>" . __('login') . "</a></p>";
    echo "</div>"; // box
    echo "</div>"; // column
    echo "</div>"; // columns
    echo "</div>"; // container
    echo "</div>"; // hero-body
    echo "</section>";
    renderFooter();
    exit;
}

// 3.5 Update URL
if ($uri === BASE_PATH . '/update') {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }

    if ($method === 'POST') {
        $id = $_POST['id'] ?? '';
        $longUrl = $_POST['long_url'] ?? '';
        $shortCode = $_POST['short_code'] ?? '';

        if (!empty($id) && filter_var($longUrl, FILTER_VALIDATE_URL) && !empty($shortCode)) {
            // Validasi karakter short_code
            if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $shortCode)) {
                 // Error handling untuk karakter tidak valid (silent fail for now or redirect with error in future)
                 // Idealnya kita store error di session, tapi untuk minimal changes kita skip update saja
            } else {
                // Cek duplikat short_code selain id ini
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM urls WHERE short_code = ? AND id != ?");
                $stmt->execute([$shortCode, $id]);
                if ($stmt->fetchColumn() > 0) {
                     // Handle error appropriately or redirect with error
                } else {
                    $stmt = $pdo->prepare("UPDATE urls SET long_url = ?, short_code = ? WHERE id = ?");
                    $stmt->execute([$longUrl, $shortCode, $id]);
                }
            }
        }
    }
    header('Location: ' . BASE_PATH . '/admin');
    exit;
}

// 3.6 Delete URL (Converted to POST)
if ($uri === BASE_PATH . '/delete') {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }

    if ($method === 'POST') {
        validateCsrf();
        $id = $_POST['id'] ?? '';
        if (!empty($id)) {
            $stmt = $pdo->prepare("DELETE FROM urls WHERE id = ?");
            $stmt->execute([$id]);
        }
    }
    header('Location: ' . BASE_PATH . '/admin');
    exit;
}



// 4. Statistics Page
if ($uri === BASE_PATH . '/stats') {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }

    $id = $_GET['id'] ?? '';
    if (empty($id)) {
        header('Location: ' . BASE_PATH . '/admin');
        exit;
    }

    // Get URL Info
    $stmt = $pdo->prepare("SELECT * FROM urls WHERE id = ?");
    $stmt->execute([$id]);
    $urlData = $stmt->fetch();

    if (!$urlData) {
        die("URL tidak ditemukan.");
    }

    // Get Visits Data
    $stmt = $pdo->prepare("SELECT * FROM visits WHERE url_id = ? ORDER BY created_at DESC");
    $stmt->execute([$id]);
    $visits = $stmt->fetchAll();

    // Aggregation Logic
    $totalClicks = count($visits);
    $uniqueClicks = count(array_unique(array_column($visits, 'ip_address')));
    
    // Time Distribution (Last 7 Days)
    $clicksByDate = [];
    $browsers = [];
    $os = [];
    $devices = [];
    $countries = [];
    $referrers = [];
    $cities = [];

    foreach ($visits as $v) {
        // Date
        $date = date('Y-m-d', strtotime($v['created_at']));
        $clicksByDate[$date] = ($clicksByDate[$date] ?? 0) + 1;

        // Country
        $c = $v['country'] ?: 'Unknown';
        $countries[$c] = ($countries[$c] ?? 0) + 1;
        
        // City
        $ct = $v['city'] ?: 'Unknown';
        $cities[$ct] = ($cities[$ct] ?? 0) + 1;

        // Referrer
        $ref = $v['referrer'] ?: 'Direct';
        // Simplify referrer (domain only)
        if ($ref !== 'Direct') {
            $parsedRef = parse_url($ref, PHP_URL_HOST);
            $ref = $parsedRef ?: 'Other';
        }
        $referrers[$ref] = ($referrers[$ref] ?? 0) + 1;

        // Simple UA Parsing
        $ua = $v['user_agent'] ?: '';
        
        // Browser
        if (strpos($ua, 'Firefox') !== false) $br = 'Firefox';
        elseif (strpos($ua, 'Chrome') !== false) $br = 'Chrome';
        elseif (strpos($ua, 'Safari') !== false) $br = 'Safari';
        elseif (strpos($ua, 'Edge') !== false) $br = 'Edge';
        elseif (strpos($ua, 'Opera') !== false) $br = 'Opera';
        else $br = 'Other';
        $browsers[$br] = ($browsers[$br] ?? 0) + 1;

        // OS
        if (strpos($ua, 'Windows') !== false) $sys = 'Windows';
        elseif (strpos($ua, 'Mac') !== false) $sys = 'MacOS';
        elseif (strpos($ua, 'Linux') !== false) $sys = 'Linux';
        elseif (strpos($ua, 'Android') !== false) $sys = 'Android';
        elseif (strpos($ua, 'iOS') !== false) $sys = 'iOS';
        else $sys = 'Other';
        $os[$sys] = ($os[$sys] ?? 0) + 1;

        // Device
        if (strpos($ua, 'Mobile') !== false || strpos($ua, 'Android') !== false || strpos($ua, 'iPhone') !== false) $dev = 'Mobile';
        else $dev = 'Desktop';
        $devices[$dev] = ($devices[$dev] ?? 0) + 1;
    }

    // Sort Data
    arsort($countries); arsort($cities); arsort($referrers); arsort($browsers); arsort($os);

    renderHeader(__('stats_title') . ": " . $urlData['short_code']);
    
    // Inline Chart.js
    echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";

    echo "<section class='section'>";
    echo "<div class='container'>";
    echo "<a href='" . BASE_PATH . "/admin' class='button is-light mb-4'><span class='icon'><i class='fas fa-arrow-left'></i></span><span>" . __('back_dashboard') . "</span></a>";
    
    echo "<h1 class='title'>" . __('stats_title') . "</h1>";
    echo "<p class='subtitle'>" . __('target') . ": <a href='{$urlData['long_url']}' target='_blank'>{$urlData['long_url']}</a></p>";

    // Summary Cards
    echo "<div class='columns is-multiline'>";
    echo "<div class='column is-3'><div class='notification is-primary'><p class='heading'>" . __('total_clicks') . "</p><p class='title'>$totalClicks</p></div></div>";
    echo "<div class='column is-3'><div class='notification is-info'><p class='heading'>" . __('unique_clicks') . "</p><p class='title'>$uniqueClicks</p></div></div>";
    echo "<div class='column is-3'><div class='notification is-warning'><p class='heading'>" . __('top_country') . "</p><p class='title'>" . (array_key_first($countries) ?? '-') . "</p></div></div>";
    echo "<div class='column is-3'><div class='notification is-success'><p class='heading'>" . __('main_device') . "</p><p class='title'>" . (array_key_first($devices) ?? '-') . "</p></div></div>";
    echo "</div>";

    // Charts Row 1
    echo "<div class='columns'>";
    
    // Timeline
    echo "<div class='column is-8'>";
    echo "<div class='box'><h3 class='title is-5'>" . __('click_trend') . "</h3><canvas id='chartTimeline'></canvas></div>";
    echo "</div>";
    
    // Referrers
    echo "<div class='column is-4'>";
    echo "<div class='box'><h3 class='title is-5'>" . __('traffic_source') . "</h3><canvas id='chartReferrers'></canvas></div>";
    echo "</div>";
    echo "</div>"; // columns

    // Charts Row 2
    echo "<div class='columns'>";
    // Geo
    echo "<div class='column is-4'>";
    echo "<div class='box'><h3 class='title is-5'>" . __('location_country') . "</h3><canvas id='chartCountry'></canvas></div>";
    echo "</div>";
    // OS
    echo "<div class='column is-4'>";
    echo "<div class='box'><h3 class='title is-5'>" . __('operating_system') . "</h3><canvas id='chartOS'></canvas></div>";
    echo "</div>";
    // Browser
    echo "<div class='column is-4'>";
    echo "<div class='box'><h3 class='title is-5'>" . __('browser') . "</h3><canvas id='chartBrowser'></canvas></div>";
    echo "</div>";
    echo "</div>"; // columns
    
    
    // Raw Data Table (Last 50)
    echo "<div class='box mt-6'>";
    echo "<h3 class='title is-5'>" . __('visit_history') . "</h3>";
    echo "<div class='table-container'>";
    echo "<table class='table is-striped is-fullwidth is-narrow'>";
    echo "<thead><tr><th>" . __('time') . "</th><th>IP</th><th>" . __('location') . "</th><th>OS/" . __('browser') . "</th><th>" . __('source') . "</th></tr></thead>";
    echo "<tbody>";
    $count = 0;
    foreach ($visits as $v) {
        if ($count++ >= 50) break;
        echo "<tr>";
        echo "<td>{$v['created_at']}</td>";
        echo "<td>" . substr($v['ip_address'], 0, -3) . "***" . "</td>"; // Mask IP
        echo "<td>{$v['city']}, {$v['country']}</td>";
        echo "<td><span class='tag is-light'>" . htmlspecialchars(substr($v['user_agent'], 0, 50)) . "...</span></td>";
        echo "<td>" . htmlspecialchars($v['referrer'] ?: '-') . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table></div></div>";

    echo "</div></section>";

    // JSON Data for Charts
    $jsonDateLabels = json_encode(array_keys($clicksByDate));
    $jsonDateValues = json_encode(array_values($clicksByDate));
    $jsonRefLabels = json_encode(array_keys($referrers));
    $jsonRefValues = json_encode(array_values($referrers));
    $jsonCountryLabels = json_encode(array_keys($countries));
    $jsonCountryValues = json_encode(array_values($countries));
    $jsonOSLabels = json_encode(array_keys($os));
    $jsonOSValues = json_encode(array_values($os));
    $jsonBrowserLabels = json_encode(array_keys($browsers));
    $jsonBrowserValues = json_encode(array_values($browsers));

    echo "<script>
    const ctxTimeline = document.getElementById('chartTimeline').getContext('2d');
    new Chart(ctxTimeline, {
        type: 'line',
        data: {
            labels: $jsonDateLabels,
            datasets: [{
                label: '" . __('click_count') . "',
                data: $jsonDateValues,
                borderColor: '#007bff',
                tension: 0.1,
                fill: true
            }]
        }
    });

    const ctxRef = document.getElementById('chartReferrers').getContext('2d');
    new Chart(ctxRef, {
        type: 'doughnut',
        data: {
            labels: $jsonRefLabels,
            datasets: [{
                data: $jsonRefValues,
                backgroundColor: ['#ffc107', '#28a745', '#17a2b8', '#dc3545', '#6c757d']
            }]
        }
    });

    const ctxCountry = document.getElementById('chartCountry').getContext('2d');
    new Chart(ctxCountry, {
        type: 'bar',
        data: {
            labels: $jsonCountryLabels,
            datasets: [{
                label: '" . __('visitors') . "',
                data: $jsonCountryValues,
                backgroundColor: '#17a2b8'
            }]
        }
    });

    const ctxOS = document.getElementById('chartOS').getContext('2d');
    new Chart(ctxOS, {
        type: 'pie',
        data: {
            labels: $jsonOSLabels,
            datasets: [{
                data: $jsonOSValues,
                backgroundColor: ['#007bff', '#6610f2', '#6f42c1', '#e83e8c', '#dc3545']
            }]
        }
    });

    const ctxBrowser = document.getElementById('chartBrowser').getContext('2d');
    new Chart(ctxBrowser, {
        type: 'pie',
        data: {
            labels: $jsonBrowserLabels,
            datasets: [{
                data: $jsonBrowserValues,
                backgroundColor: ['#fd7e14', '#ffc107', '#28a745', '#20c997', '#007bff']
            }]
        }
    });
    </script>";

    renderFooter();
    exit;
}

// 5. Admin Dashboard
if ($uri === BASE_PATH . '/admin') {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }

    $username = $_SESSION['username'];
    $message = "";

    // Handle Create URL
    if ($method === 'POST') {
        validateCsrf();
        $longUrl = $_POST['long_url'] ?? '';
        $shortCode = $_POST['short_code'] ?? '';

        if (filter_var($longUrl, FILTER_VALIDATE_URL)) {
            if (empty($shortCode)) {
                $shortCode = generateCode();
            }

            // Validasi Karakter Short Code
            if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $shortCode)) {
                $message = "<p style='color:red;'>" . __('error_code_invalid') . " (Hanya huruf, angka, - dan _)</p>";
            } else {
                // Cek duplikat
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM urls WHERE short_code = ?");
                $stmt->execute([$shortCode]);
                if ($stmt->fetchColumn() > 0) {
                    $message = "<p style='color:red;'>" . sprintf(__('error_code_used'), $shortCode) . "</p>";
                } else {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO urls (long_url, short_code, user_id) VALUES (?, ?, ?)");
                        $stmt->execute([$longUrl, $shortCode, $_SESSION['user_id']]);
                        $baseUrl = getBaseUrl();
                        $message = "<p style='color:green;'>" . __('success_created') . " <a href='$baseUrl" . BASE_PATH . "/$shortCode' target='_blank'>$baseUrl" . BASE_PATH . "/$shortCode</a></p>";
                    } catch (Exception $e) {
                        $message = "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
                    }
                }
            }
        } else {
            $message = "<p style='color:red;'>" . __('error_invalid_url') . "</p>";
        }
    }

    // Tampilan Dashboard
    renderHeader($appTitle);
    
    // Navbar
    echo "<nav class='navbar is-info' role='navigation' aria-label='main navigation' style='background-color: #007bff;'>";
    echo "<div class='container'>";
    echo "<div class='navbar-brand'>";
    echo "<a class='navbar-item' href='#'>";
    if (file_exists(__DIR__ . '/' . $appLogo)) {
        echo "<img src='$appLogo' alt='Logo' style='max-height: 40px; margin-right: 10px;'>";
    }
    echo "<b>$appTitle</b>";
    echo "</a>";
    echo "<a role='button' class='navbar-burger' aria-label='menu' aria-expanded='false' data-target='navbarBasic'>";
    echo "<span aria-hidden='true'></span><span aria-hidden='true'></span><span aria-hidden='true'></span>";
    echo "</a>";
    echo "</div>"; // navbar-brand
    
    echo "<div id='navbarBasic' class='navbar-menu'>";
    echo "<div class='navbar-end'>";
    echo "<div class='navbar-item'>" . __('hello') . ", $username</div>";
    
    echo "<div class='navbar-item'>";
    echo "<div class='buttons'>";
    
    // Admin buttons
    if (!empty($_SESSION['is_admin'])) {
        echo "<a class='button is-warning is-small' href='" . BASE_PATH . "/users'>";
        echo "<span class='icon is-small'><i class='fas fa-users-cog'></i></span>";
        echo "<span>" . __('manage_users') . "</span>";
        echo "</a>";
        echo "<a class='button is-success is-small' href='" . BASE_PATH . "/api'>";
        echo "<span class='icon is-small'><i class='fas fa-code'></i></span>";
        echo "<span>" . __('api_manage') . "</span>";
        echo "</a>";
    }
    
    // Logout button (Rightmost)
    echo "<a class='button is-light is-small is-outlined' href='" . BASE_PATH . "/logout'>" . __('logout') . "</a>";
    
    echo "</div>"; // buttons
    echo "</div>"; // navbar-item

    echo "</div>"; // navbar-end
    echo "</div>"; // navbar-menu
    echo "</div>"; // container
    echo "</nav>";

    // Main Content
    echo "<section class='section'>";
    echo "<div class='container'>";
    
    echo "<div class='columns'>";
    echo "<div class='column is-8 is-offset-2'>";
    
    // Create URL Card
    echo "<div class='card mb-6'>";
    echo "<div class='card-content'>";
    echo "<p class='title is-4'>" . __('create_url') . "</p>";
    if(!empty($message)) echo "<div class='content'>$message</div>";
    echo "<form method='post'>";
    echo csrfField();
    echo "<div class='field has-addons'>";
    echo "<div class='control is-expanded'><input class='input' type='text' name='long_url' placeholder='" . __('long_url_ph') . "' required></div>";
    echo "<div class='control'><input class='input' type='text' name='short_code' placeholder='" . __('short_code_ph') . "'></div>";
    echo "<div class='control'><button class='button is-info' type='submit'>" . __('shorten_btn') . "</button></div>";
    echo "</div>";
    echo "</form>";
    echo "</div>"; // card-content
    echo "</div>"; // card

    // Tabel Data dengan Pagination dan Search
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 5;
    $offset = ($page - 1) * $limit;
    $search = $_GET['q'] ?? '';

    // Ambil data (Filter by User)
    $sql = "SELECT * FROM urls WHERE user_id = ?";
    $params = [$_SESSION['user_id']];
    
    if (!empty($search)) {
        $sql .= " AND (long_url LIKE ? OR short_code LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $urls = $stmt->fetchAll();

    // Hitung total data untuk pagination (Filter by User)
    $sqlCount = "SELECT COUNT(*) FROM urls WHERE user_id = ?";
    $countParams = [$_SESSION['user_id']];
    if (!empty($search)) {
        $sqlCount .= " AND (long_url LIKE ? OR short_code LIKE ?)";
        $countParams[] = "%$search%";
        $countParams[] = "%$search%";
    }
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($countParams);
    $totalRows = $stmtCount->fetchColumn();
    $totalPages = ceil($totalRows / $limit);

    echo "<div class='box'>";
    echo "<h2 class='subtitle'>" . __('history') . "</h2>";
    
    // Search Bar
    echo "<form method='get' class='mb-4'>";
    echo "<div class='field has-addons'>";
    echo "<div class='control is-expanded'>";
    echo "<input class='input' type='text' name='q' placeholder='" . __('search_ph') . "' value='" . htmlspecialchars($search) . "'>";
    echo "</div>";
    echo "<div class='control'>";
    echo "<button class='button is-info' type='submit'><span class='icon'><i class='fas fa-search'></i></span></button>";
    echo "</div>";
    if (!empty($search)) {
        echo "<div class='control'>";
        echo "<a href='" . BASE_PATH . "/admin' class='button is-light'>" . __('reset') . "</a>";
        echo "</div>";
    }
    echo "</div>";
    echo "</form>";

    echo "<div class='table-container'>";
    echo "<table class='table is-striped is-hoverable is-fullwidth is-responsive-cards'>";
    // Removed ID Column, Added Actions Column
    echo "<thead><tr><th>" . __('original_url') . "</th><th>" . __('short_url') . "</th><th width='200'>" . __('actions') . "</th></tr></thead>";
    echo "<tbody>";
    foreach ($urls as $u) {
        $shortLink = getBaseUrl() . BASE_PATH . "/" . $u['short_code'];
        $longUrlDisplay = htmlspecialchars($u['long_url']);
        echo "<tr>";
        // echo "<td data-label='ID'>{$u['id']}</td>"; // Removed ID Display
        echo "<td data-label='URL Asli' style='max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;' title='$longUrlDisplay'>$longUrlDisplay</td>";
        echo "<td data-label='URL Pendek'><a href='$shortLink' target='_blank'>{$u['short_code']}</a></td>";
        // echo "<td data-label='Dibuat'>{$u['created_at']}</td>"; // Removed Date
        echo "<td data-label='Aksi'>";
        // Action Buttons
        echo "<div class='buttons are-small'>";
        echo "<a class='button is-warning' href='" . BASE_PATH . "/stats?id={$u['id']}' title='" . __('stats') . "'><i class='fas fa-chart-bar'></i></a>";
        echo "<button class='button is-info' onclick='openEditModal(\"{$u['id']}\", \"" . htmlspecialchars($u['long_url'], ENT_QUOTES) . "\", \"{$u['short_code']}\")' title='" . __('edit') . "'><i class='fas fa-edit'></i></button>";
        echo "<button class='button is-success' onclick='copyToClipboard(\"$shortLink\")' title='" . __('copy') . "'><i class='fas fa-copy'></i></button>";
        echo "<button class='button is-dark' onclick='openQRModal(\"$shortLink\")' title='" . __('qr_code') . "'><i class='fas fa-qrcode'></i></button>";
        // Delete converted to Form
        echo "<form method='post' action='" . BASE_PATH . "/delete' style='display:inline;' onsubmit='return confirm(\"" . __('confirm_delete') . "\")'>";
        echo csrfField();
        echo "<input type='hidden' name='id' value='{$u['id']}'>";
        echo "<button class='button is-danger' type='submit' title='" . __('delete') . "'><i class='fas fa-trash'></i></button>";
        echo "</form>";
        echo "</div>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "</div>"; // table-container

    // Pagination Controls
    if ($totalPages > 1) {
        echo "<nav class='pagination is-centered' role='navigation' aria-label='pagination'>";
        $prevPage = $page - 1;
        $nextPage = $page + 1;
        $qParam = !empty($search) ? "&q=" . urlencode($search) : "";
        
        if ($page > 1) {
            echo "<a class='pagination-previous' href='?page=$prevPage$qParam'>" . __('prev') . "</a>";
        } else {
            echo "<a class='pagination-previous' disabled>" . __('prev') . "</a>";
        }
        
        if ($page < $totalPages) {
            echo "<a class='pagination-next' href='?page=$nextPage$qParam'>" . __('next') . "</a>";
        } else {
            echo "<a class='pagination-next' disabled>" . __('next') . "</a>";
        }
        
        echo "<ul class='pagination-list'>";
        // Simplified pagination: showing current page
        echo "<li><a class='pagination-link is-current'>" . __('page') . " $page of $totalPages</a></li>";
        echo "</ul>";
        echo "</nav>";
    }

    echo "</div>"; // box
    
    echo "</div>"; // column
    echo "</div>"; // columns
    echo "</div>"; // container
    echo "</section>";

    // Modals
    // Edit Modal
    echo "<div id='editModal' class='modal'>";
    echo "<div class='modal-background' onclick='closeEditModal()'></div>";
    echo "<div class='modal-card'>";
    echo "<header class='modal-card-head'><p class='modal-card-title'>" . __('edit') . " URL</p><button class='delete' aria-label='close' onclick='closeEditModal()'></button></header>";
    echo "<section class='modal-card-body'>";
    echo "<form method='post' action='" . BASE_PATH . "/update'>";
    echo csrfField();
    echo "<input type='hidden' name='id' id='edit_id'>";
    echo "<div class='field'><label class='label'>" . __('original_url') . "</label><div class='control'><input class='input' type='text' name='long_url' id='edit_long_url' required></div></div>";
    echo "<div class='field'><label class='label'>" . __('short_url') . "</label><div class='control'><input class='input' type='text' name='short_code' id='edit_short_code' required></div></div>";
    echo "<div class='field mt-4'><button class='button is-success is-fullwidth' type='submit'>" . __('save_pass') . "</button></div>";
    echo "</form>";
    echo "</section>";
    echo "</div>"; // modal-card
    echo "</div>"; // modal

    // QR Modal
    echo "<div id='qrModal' class='modal'>";
    echo "<div class='modal-background' onclick='closeQRModal()'></div>";
    echo "<div class='modal-content has-text-centered p-4' style='background:white; border-radius:8px; width: auto; display: inline-block;'>";
    echo "<h3 class='title is-4 mb-4'>" . __('qr_code') . "</h3>";
    echo "<div id='qrcode' style='display: flex; justify-content: center;'></div>";
    echo "<p class='mt-3 example-link' style='word-break: break-all;' id='qrLinkText'></p>";
    echo "<button class='button is-primary mt-3' onclick='downloadQR()'><span class='icon'><i class='fas fa-download'></i></span><span>" . __('download_qr') . "</span></button>";
    echo "</div>";
    echo "<button class='modal-close is-large' aria-label='close' onclick='closeQRModal()'></button>";
    echo "</div>";

    // Scripts
    echo "<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('" . __('link_copied') . ": ' + text);
        }, (err) => {
            console.error('" . __('copy_failed') . ": ', err);
        });
    }

    function openEditModal(id, longUrl, shortCode) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_long_url').value = longUrl;
        document.getElementById('edit_short_code').value = shortCode;
        document.getElementById('editModal').classList.add('is-active');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('is-active');
    }

    function openQRModal(url) {
        document.getElementById('qrcode').innerHTML = '';
        document.getElementById('qrLinkText').innerText = url;
        new QRCode(document.getElementById('qrcode'), {
            text: url,
            width: 128,
            height: 128
        });
        document.getElementById('qrModal').classList.add('is-active');
    }

    function closeQRModal() {
        document.getElementById('qrModal').classList.remove('is-active');
    }

    function downloadQR() {
        const qrDiv = document.getElementById('qrcode');
        // QRCode.js usually renders an img or canvas. Check both.
        const img = qrDiv.querySelector('img');
        const canvas = qrDiv.querySelector('canvas');
        
        let url = '';
        if (img && img.src) {
            url = img.src;
        } else if (canvas) {
            url = canvas.toDataURL('image/png');
        }

        if (url) {
            const link = document.createElement('a');
            link.href = url;
            link.download = 'qrcode.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            alert('QR Code belum siap/gagal digenerate.');
        }
    }
    
    // Burger Menu (Existing)
    document.addEventListener('DOMContentLoaded', () => {
      const \$navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
      if (\$navbarBurgers.length > 0) {
        \$navbarBurgers.forEach( el => {
          el.addEventListener('click', () => {
            const target = el.dataset.target;
            const \$target = document.getElementById(target);
            el.classList.toggle('is-active');
            \$target.classList.toggle('is-active');
          });
        });
      }
    });
    </script>";
    
    renderFooter();
    exit;
}

// 6. User Management (Admin Only)
if ($uri === BASE_PATH . '/users') {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || empty($_SESSION['is_admin'])) {
        header('Location: ' . BASE_PATH . '/admin');
        exit;
    }

    $message = "";

    // Handle Actions
    if ($method === 'POST') {
        validateCsrf();
        $action = $_POST['action'] ?? '';
        $targetId = $_POST['user_id'] ?? '';

        if (!empty($targetId)) {
            if ($action === 'delete') {
                // Prevent self-delete
                if ($targetId == $_SESSION['user_id']) {
                    $message = "<div class='notification is-danger'>Tidak dapat menghapus akun sendiri.</div>";
                } else {
                     $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmt->execute([$targetId])) {
                         $message = "<div class='notification is-success'>User berhasil dihapus.</div>";
                    }
                }
            } elseif ($action === 'reset_password') {
                $newPass = $_POST['new_password'] ?? '';
                if (strlen($newPass) < 6) {
                    $message = "<div class='notification is-warning'>Password minimal 6 karakter.</div>";
                } else {
                    $hashed = password_hash($newPass, PASSWORD_BCRYPT);
                    // Force change password = 1 so they must change it next time
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, force_change_password = 1 WHERE id = ?");
                    if ($stmt->execute([$hashed, $targetId])) {
                         $message = "<div class='notification is-success'>" . __('msg_pass_reset') . "</div>";
                    }
                }
            } elseif ($action === 'promote') {
                $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
                if ($stmt->execute([$targetId])) {
                     $message = "<div class='notification is-success'>" . __('msg_promoted') . "</div>";
                }
            } elseif ($action === 'demote') {
                if ($targetId == $_SESSION['user_id']) {
                    if ($stmt->execute([$targetId])) {
                         $message = "<div class='notification is-success'>" . __('msg_demoted') . "</div>";
                    }
                }
            }
        }
    }

    // Get Users
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id ASC");
    $users = $stmt->fetchAll();

    renderHeader(__('manage_users'));
    
    echo "<nav class='navbar is-info' style='background-color: #007bff;'><div class='container'><div class='navbar-brand'><a class='navbar-item' href='" . BASE_PATH . "/admin'><b>&larr; " . __('back_dashboard') . "</b></a></div></div></nav>";

    echo "<section class='section'>";
    echo "<div class='container'>";
    echo "<div class='level'>";
    echo "<div class='level-left'><div class='level-item'><h1 class='title'>" . __('manage_users') . "</h1></div></div>";
    echo "<div class='level-right'><div class='level-item'><a class='button is-primary' href='" . BASE_PATH . "/register'><i class='fas fa-user-plus mr-2'></i>" . __('add_user') . "</a></div></div>";
    echo "</div>";
    
    if (!empty($message)) echo $message;
    
    echo "<div class='box'>";
    echo "<table class='table is-striped is-fullwidth'>";
    echo "<thead><tr><th>" . __('id_col') . "</th><th>" . __('username_col') . "</th><th>" . __('status_col') . "</th><th>" . __('action_col') . "</th></tr></thead>";
    echo "<tbody>";
    foreach ($users as $u) {
        echo "<tr>";
        echo "<td>{$u['id']}</td>";
        echo "<td>" . htmlspecialchars($u['username']) . "</td>";
        echo "<td>";
        if ($u['is_admin']) echo "<span class='tag is-primary mr-1'>" . __('role_admin') . "</span>";
        echo ($u['force_change_password'] ? "<span class='tag is-warning'>" . __('force_pass') . "</span>" : "<span class='tag is-success'>" . __('active') . "</span>");
        echo "</td>";
        echo "<td>";
        echo "<div class='buttons are-small'>";
        
        // Promotion/Demotion Logic
        if ($u['id'] != $_SESSION['user_id']) { // Check ID instead of username
            if ($u['is_admin']) {
                echo "<button class='button is-dark' onclick='if(confirm(\"" . __('confirm_demote') . "\")) { submitForm(\"demote\", \"{$u['id']}\"); }' title='" . __('demote_admin') . "'><i class='fas fa-user-minus'></i></button>";
            } else {
                echo "<button class='button is-info' onclick='if(confirm(\"" . __('confirm_promote') . "\")) { submitForm(\"promote\", \"{$u['id']}\"); }' title='" . __('promote_admin') . "'><i class='fas fa-user-plus'></i></button>";
            }
            echo "<button class='button is-danger' onclick='if(confirm(\"" . __('confirm_delete_user') . "\")) { submitForm(\"delete\", \"{$u['id']}\"); }' title='" . __('delete') . "'><i class='fas fa-trash'></i></button>";
        }

        echo "<button class='button is-warning' onclick='openResetModal(\"{$u['id']}\", \"{$u['username']}\")' title='" . __('reset_pass') . "'><i class='fas fa-key'></i></button>";
        echo "</div>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>";
    
    echo "</div></section>";

    // Hidden Form for Actions
    echo "<form id='actionForm' method='post' style='display:none;'>";
    echo csrfField();
    echo "<input type='hidden' name='action' id='formAction'>";
    echo "<input type='hidden' name='user_id' id='formUserId'>";
    echo "</form>";

    // Reset Password Modal
    echo "<div id='resetModal' class='modal'>";
    echo "<div class='modal-background' onclick='closeResetModal()'></div>";
    echo "<div class='modal-card'>";
    echo "<header class='modal-card-head'><p class='modal-card-title'>" . __('reset_pass') . ": <span id='resetUsername'></span></p><button class='delete' onclick='closeResetModal()'></button></header>";
    echo "<section class='modal-card-body'>";
    echo "<form method='post'>";
    echo csrfField();
    echo "<input type='hidden' name='action' value='reset_password'>";
    echo "<input type='hidden' name='user_id' id='resetUserId'>";
    echo "<div class='field'><label class='label'>" . __('new_pass') . "</label><div class='control'><input class='input' type='text' name='new_password' required minlength='6' placeholder='" . __('min_6_chars') . "'></div></div>";
    echo "<div class='field'><button class='button is-warning is-fullwidth' type='submit'>" . __('reset_pass') . "</button></div>";
    echo "</form>";
    echo "</section>";
    echo "</div></div>";

    echo "<script>
    function submitForm(action, userId) {
        document.getElementById('formAction').value = action;
        document.getElementById('formUserId').value = userId;
        document.getElementById('actionForm').submit();
    }
    function openResetModal(id, username) {
        document.getElementById('resetUserId').value = id;
        document.getElementById('resetUsername').innerText = username;
        document.getElementById('resetModal').classList.add('is-active');
    }
    function closeResetModal() {
        document.getElementById('resetModal').classList.remove('is-active');
    }
    </script>";

    renderFooter();
    exit;
}

// 7. Short URL Redirect (Moved down)
// Match {BASE_PATH}/{code}
// Escape BASE_PATH for regex
$escapedBase = preg_quote(BASE_PATH, '/');
if (preg_match('/^' . $escapedBase . '\/([a-zA-Z0-9\-_]+)$/', $uri, $matches)) {
    $code = $matches[1];
    
    // Ignore reserved words just in case regex slipped (though exact matches above handle them)
    if (in_array($code, ['login', 'register', 'logout', 'admin'])) {
        // Should have been caught by exact matches, but safe fallback
        $fallback = sprintf("%s/admin", defined('BASE_PATH') ? BASE_PATH : '');
        header("Location: $fallback");
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, long_url FROM urls WHERE short_code = ?");
    $stmt->execute([$code]);
    // See Fetch logic below
    //$url = $stmt->fetchColumn(); // Changed to fetch assoc

    $stmt->execute([$code]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $url = $result['long_url'];
        $url_id = $result['id'] ?? null; // Fetch ID need modify query above

        // Log Visit
        if ($url_id) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ref = $_SERVER['HTTP_REFERER'] ?? '';
            
            // Get Geo (Warning: Adds latency)
            $geo = getGeoLocation($ip);
            
            $stmtLog = $pdo->prepare("INSERT INTO visits (url_id, ip_address, country, city, user_agent, referrer) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtLog->execute([$url_id, $ip, $geo['country'], $geo['city'], $ua, $ref]);
        }

        header("Location: $url");
        exit;
    } else {
        http_response_code(404);
        renderHeader(__('error_404'));
        echo "<section class='hero is-danger is-fullheight'>";
        echo "<div class='hero-body'>";
        echo "<div class='container has-text-centered'>";
        echo "<h1 class='title is-1'>404</h1>";
        echo "<p class='subtitle is-3'>" . __('url_not_found') . "</p>";
        echo "<p class='mb-6'>" . __('link_invalid') . "</p>";
        echo "<a href='" . BASE_PATH . "/login' class='button is-light is-medium'>" . __('back_home') . "</a>";
        echo "</div>";
        echo "</div>";
        echo "</section>";
        renderFooter();
        exit;
    }
}

// 6. Default / Fallback
// Jika akses root /u/ atau tidak dikenal, redirect ke admin (yang akan cek login)
$fallbackUrl = sprintf("%s/admin", defined('BASE_PATH') ? BASE_PATH : '');
header("Location: $fallbackUrl");
exit;

// --- TRANSLATIONS ---
function getTranslations() {
    return [
        'en' => [
            'setup_wizard' => 'Setup Wizard',
            'setup_config' => 'Initial Configuration Setup',
            'db_type' => 'Database Type',
            'sqlite_opt' => 'SQLite (No Setup)',
            'mysql_opt' => 'MySQL / MariaDB',
            'db_host' => 'Database Host',
            'db_name' => 'Database Name',
            'db_user' => 'Database User',
            'db_pass' => 'Database Password',
            'save_install' => 'Save & Install',
            'login' => 'Login',
            'username' => 'Username',
            'password' => 'Password',
            'login_btn' => 'Login',
            'register' => 'Register',
            'change_pass' => 'Change Password',
            'new_pass' => 'New Password',
            'confirm_pass' => 'Confirm Password',
            'save_pass' => 'Save Password',
            'must_change_pass' => 'You must change the default password.',
            'logout' => 'Logout',
            'hello' => 'Hello',
            'dashboard' => 'Dashboard',
            'manage_users' => 'Manage Users',
            'create_url' => 'Create Short URL',
            'long_url_ph' => 'Enter Long URL (https://...)',
            'short_code_ph' => 'Custom Code (Optional)',
            'shorten_btn' => 'Shorten',
            'history' => 'URL History',
            'search_ph' => 'Search original URL or short code...',
            'original_url' => 'Original URL',
            'short_url' => 'Short URL',
            'actions' => 'Actions',
            'stats' => 'Statistics',
            'edit' => 'Edit',
            'copy' => 'Copy',
            'delete' => 'Delete',
            'qr_code' => 'QR Code',
            'prev' => 'Previous',
            'next' => 'Next',
            'page' => 'Page',
            'total_clicks' => 'Total Clicks',
            'unique_clicks' => 'Unique Clicks',
            'top_country' => 'Top Country',
            'top_device' => 'Top Device',
            'click_trend' => 'Click Trend (Daily)',
            'traffic_source' => 'Traffic Source',
            'location' => 'Location',
            'os' => 'Operating System',
            'browser' => 'Browser',
            'visit_history' => 'Visit History (Last 50)',
            'time' => 'Time',
            'ip' => 'IP',
            'add_user' => 'Add User',
            'back_dashboard' => 'Back to Dashboard',
            'role_admin' => 'Admin',
            'active' => 'Active',
            'force_pass' => 'Must Change Pass',
            'reset_pass' => 'Reset Pass',
            'promote_admin' => 'Promote to Admin',
            'demote_admin' => 'Demote Admin',
            'confirm_delete' => 'Are you sure you want to delete?',
            'confirm_promote' => 'Make this user an admin?',
            'confirm_demote' => 'Revoke admin access?',
            'error_404' => '404 Not Found',
            'url_not_found' => 'URL Not Found',
            'link_invalid' => 'Sorry, the link is invalid or has been deleted.',
            'back_home' => 'Back to Home',
            'success_created' => 'URL successfully created:',
            'error_code_used' => 'Short code already used.',
            'error_invalid_url' => 'Invalid URL.',
            'error_csrf' => 'CSRF validation failed. Please refresh.',
            'error_db' => 'Database Connection Failed',
            'msg_cop_success' => 'Link copied to clipboard:',
            'msg_pass_mismatch' => 'Passwords do not match.',
            'msg_pass_short' => 'Password must be at least 6 characters.',
            'msg_user_deleted' => 'User deleted successfully.',
            'msg_pass_reset' => 'Password reset successfully. User must change it on login.',
            'msg_promoted' => 'User promoted to Admin.',
            'msg_demoted' => 'Admin access revoked.',
            'msg_username_taken' => 'Username already taken.',
            'msg_register_fail' => 'Registration failed.',
            'msg_user_created' => 'User created successfully.',
            'id_col' => 'ID',
            'status_col' => 'Status',
            'confirm_delete_user' => 'Delete this user?',
            'min_6_chars' => 'Minimum 6 characters',
            'cant_demote_self' => 'Cannot demote yourself.',
            'error_title' => 'Error',
            'stats_title' => 'Link Statistics',
            'target' => 'Target',
            'total_clicks' => 'Total Clicks',
            'unique_clicks' => 'Unique Clicks',
            'top_country' => 'Top Country',
            'main_device' => 'Main Device',
            'click_trend' => 'Click Trend (Daily)',
            'traffic_source' => 'Traffic Source',
            'location_country' => 'Location (Country)',
            'operating_system' => 'Operating System',
            'browser' => 'Browser',
            'visit_history' => 'Visit History (Last 50)',
            'time' => 'Time',
            'location' => 'Location',
            'source' => 'Source',
            'visitors' => 'Visitors',
            'click_count' => 'Click Count',
            'download_qr' => 'Download QR',
            'link_copied' => 'Link copied successfully',
            'copy_failed' => 'Failed to copy',
            'reset' => 'Reset',
            'register' => 'Register',
            'register_btn' => 'Register',
            'have_account' => 'Already have an account?',
            'username_col' => 'Username',
            'action_col' => 'Actions',
            'api_title' => 'API Settings',
            'api_enabled' => 'Enable API',
            'api_secret' => 'API Secret Key',
            'api_allowed_ua' => 'Allowed User Agents',
            'api_allowed_ips' => 'Allowed IP Whitelist',
            'api_expiry' => 'Token Expiry (seconds)',
            'api_usage_url' => 'Example API URL',
            'api_test' => 'Test API',
            'api_manage' => 'Manage API',
            'your_api_key' => 'Your Personal API Key',
            'confirm_regen_key' => 'Are you sure you want to regenerate your API key? All your existing static links will stop working!',
            'regen_key_btn' => 'Regenerate API Key',
            'global_api_settings' => 'Global API Restrictions',
            'example_token_url' => 'Ready-to-use URL (Dynamic Token)',
            'example_session_url' => 'Ready-to-use URL (Session Only)',
            'session_url_help' => 'This link only works while you are logged in to this browser.'
        ],
        'id' => [
            'setup_wizard' => 'Setup Wizard',
            'setup_config' => 'Setup Konfigurasi Awal',
            'db_type' => 'Jenis Database',
            'sqlite_opt' => 'SQLite (Tanpa Setup)',
            'mysql_opt' => 'MySQL / MariaDB',
            'db_host' => 'Database Host',
            'db_name' => 'Nama Database',
            'db_user' => 'Database User',
            'db_pass' => 'Database Password',
            'save_install' => 'Simpan & Install',
            'login' => 'Masuk',
            'username' => 'Username',
            'password' => 'Password',
            'login_btn' => 'Masuk',
            'register' => 'Daftar',
            'change_pass' => 'Ganti Password',
            'new_pass' => 'Password Baru',
            'confirm_pass' => 'Konfirmasi Password',
            'save_pass' => 'Simpan Password',
            'must_change_pass' => 'Anda harus mengganti password default.',
            'logout' => 'Keluar',
            'hello' => 'Halo',
            'dashboard' => 'Dashboard',
            'manage_users' => 'Kelola User',
            'create_url' => 'Buat URL Pendek',
            'long_url_ph' => 'Masukkan URL Panjang (https://...)',
            'short_code_ph' => 'Kode Unik (Ops)',
            'shorten_btn' => 'Pendekkan',
            'history' => 'Riwayat URL',
            'search_ph' => 'Cari URL asli atau kode pendek...',
            'original_url' => 'URL Asli',
            'short_url' => 'URL Pendek',
            'actions' => 'Aksi',
            'stats' => 'Statistik',
            'edit' => 'Edit',
            'copy' => 'Salin',
            'delete' => 'Hapus',
            'qr_code' => 'QR Code',
            'prev' => 'Sebelumnya',
            'next' => 'Berikutnya',
            'page' => 'Halaman',
            'total_clicks' => 'Total Klik',
            'unique_clicks' => 'Klik Unik',
            'top_country' => 'Negara Teratas',
            'top_device' => 'Perangkat Utama',
            'click_trend' => 'Tren Klik (Harian)',
            'traffic_source' => 'Sumber Trafik',
            'location' => 'Lokasi',
            'os' => 'Sistem Operasi',
            'browser' => 'Browser',
            'visit_history' => 'Riwayat Kunjungan (Terakhir 50)',
            'time' => 'Waktu',
            'ip' => 'IP',
            'add_user' => 'Tambah User',
            'back_dashboard' => 'Kembali ke Dashboard',
            'role_admin' => 'Admin',
            'active' => 'Aktif',
            'force_pass' => 'Wajib Ganti Pass',
            'reset_pass' => 'Reset Pass',
            'promote_admin' => 'Jadikan Admin',
            'demote_admin' => 'Cabut Admin',
            'confirm_delete' => 'Yakin ingin menghapus?',
            'confirm_promote' => 'Jadikan user ini admin?',
            'confirm_demote' => 'Cabut akses admin user ini?',
            'error_404' => '404 Tidak Ditemukan',
            'url_not_found' => 'URL Tidak Ditemukan',
            'link_invalid' => 'Maaf, link yang Anda tuju tidak valid atau sudah dihapus.',
            'back_home' => 'Kembali ke Beranda',
            'success_created' => 'URL berhasil dibuat:',
            'error_code_used' => 'Kode pendek sudah digunakan.',
            'error_invalid_url' => 'URL tidak valid.',
            'error_csrf' => 'Validasi CSRF gagal. Silakan refresh halaman.',
            'error_db' => 'Gagal Koneksi Database',
            'msg_cop_success' => 'Link berhasil disalin:',
            'msg_pass_mismatch' => 'Password tidak cocok.',
            'msg_pass_short' => 'Password minimal 6 karakter.',
            'msg_user_deleted' => 'User berhasil dihapus.',
            'msg_pass_reset' => 'Password user berhasil direset. User wajib menggantinya saat login.',
            'msg_promoted' => 'User berhasil dijadikan Admin.',
            'msg_demoted' => 'Akses Admin user dicabut.',
            'msg_username_taken' => 'Username sudah digunakan.',
            'msg_register_fail' => 'Gagal mendaftar.',
            'msg_user_created' => 'User berhasil dibuat.',
            'id_col' => 'ID',
            'status_col' => 'Status',
            'confirm_delete_user' => 'Hapus user ini?',
            'min_6_chars' => 'Minimal 6 karakter',
            'cant_demote_self' => 'Tidak dapat mencabut akses admin sendiri.',
            'error_title' => 'Galat',
            'stats_title' => 'Statistik Link',
            'target' => 'Target',
            'total_clicks' => 'Total Klik',
            'unique_clicks' => 'Klik Unik',
            'top_country' => 'Negara Teratas',
            'main_device' => 'Perangkat Utama',
            'click_trend' => 'Tren Klik (Harian)',
            'traffic_source' => 'Sumber Trafik',
            'location_country' => 'Lokasi (Negara)',
            'operating_system' => 'Sistem Operasi',
            'browser' => 'Browser',
            'visit_history' => 'Riwayat Kunjungan (Terakhir 50)',
            'time' => 'Waktu',
            'location' => 'Lokasi',
            'source' => 'Sumber',
            'visitors' => 'Pengunjung',
            'click_count' => 'Jumlah Klik',
            'download_qr' => 'Download QR',
            'link_copied' => 'Link berhasil disalin',
            'copy_failed' => 'Gagal menyalin',
            'reset' => 'Reset',
            'register' => 'Registrasi',
            'register_btn' => 'Daftar',
            'have_account' => 'Sudah punya akun?',
            'username_col' => 'Username',
            'action_col' => 'Aksi',
            'api_title' => 'Pengaturan API',
            'api_enabled' => 'Aktifkan API',
            'api_secret' => 'Kunci Rahasia API',
            'api_allowed_ua' => 'User Agent yang Diizinkan',
            'api_allowed_ips' => 'IP Whitelist yang Diizinkan',
            'api_expiry' => 'Masa Berlaku Token (detik)',
            'api_usage_url' => 'Contoh URL API',
            'api_test' => 'Test API',
            'api_manage' => 'Kelola API',
            'your_api_key' => 'Kunci API Pribadi Anda',
            'confirm_regen_key' => 'Yakin ingin meregenerasi kunci API Anda? Semua link statis lama Anda tidak akan berfungsi lagi!',
            'regen_key_btn' => 'Regenerasi Kunci API',
            'global_api_settings' => 'Pembatasan API Global',
            'example_token_url' => 'URL Siap Pakai (Token Dinamis)',
            'example_session_url' => 'URL Siap Pakai (Sesi Aktif)',
            'session_url_help' => 'Link ini hanya berfungsi selama Anda login di browser ini.'
        ]
    ];
}

