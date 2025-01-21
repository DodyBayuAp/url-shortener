<?php
// Pilihan Koneksi Database: MySQL atau SQLite
$dbType = 'sqlite'; // Pilihan: 'mysql' atau 'sqlite'

if ($dbType === 'mysql') {
    $host = 'localhost';
    $db = 'url_shortener';
    $user = 'root';
    $pass = '';
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
} elseif ($dbType === 'sqlite') {
    $dbFile = __DIR__ . '/url_shortener.sqlite';
    $dsn = "sqlite:$dbFile";
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user ?? null, $pass ?? null, $options);

    // Buat tabel jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS urls (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        long_url TEXT NOT NULL,
        short_code VARCHAR(6) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password TEXT NOT NULL
    );");

    // Tambah pengguna admin jika belum ada
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)")->execute(['admin', $hashedPassword]);
    }
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
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

// Routing Sederhana
session_start();
$uri = $_SERVER['REQUEST_URI'];
if (preg_match('/^\/u\/admin$/', $uri)) {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: /u/login');
        exit;
    }

    // Dashboard Admin
    echo "<h1>Dashboard Admin</h1>";
    $stmt = $pdo->query("SELECT * FROM urls ORDER BY created_at DESC");
    $urls = $stmt->fetchAll();

    echo "<table border='1'>
            <tr>
                <th>ID</th>
                <th>URL Panjang</th>
                <th>Kode Pendek</th>
                <th>Dibuat Pada</th>
            </tr>";
    foreach ($urls as $url) {
        echo "<tr>
                <td>{$url['id']}</td>
                <td>{$url['long_url']}</td>
                <td><a href='/u/{$url['short_code']}'>/u/{$url['short_code']}</a></td>
                <td>{$url['created_at']}</td>
              </tr>";
    }
    echo "</table>";
    echo "<a href='/u/logout'>Logout</a>";
} elseif (preg_match('/^\/u\/login$/', $uri)) {
    // Halaman Login
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            header('Location: /u/admin');
            exit;
        } else {
            echo "Login gagal. Periksa username dan password Anda.";
        }
    }

    echo "<form method='post'>
            <label for='username'>Username:</label>
            <input type='text' id='username' name='username' required>
            <label for='password'>Password:</label>
            <input type='password' id='password' name='password' required>
            <button type='submit'>Login</button>
          </form>";
} elseif (preg_match('/^\/u\/logout$/', $uri)) {
    // Logout
    session_destroy();
    header('Location: /u/login');
    exit;
} elseif (preg_match('/^\/u\/([a-zA-Z0-9]{6})$/', $uri, $matches)) {
    // Redirect URL Pendek
    $code = $matches[1];

    $stmt = $pdo->prepare("SELECT long_url FROM urls WHERE short_code = ?");
    $stmt->execute([$code]);
    $result = $stmt->fetch();

    if ($result) {
        header("Location: " . $result['long_url']);
        exit;
    } else {
        echo "<h1>404 - URL Tidak Ditemukan</h1>";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Proses Perpendek URL
    $longUrl = $_POST['long_url'] ?? '';

    if (filter_var($longUrl, FILTER_VALIDATE_URL)) {
        $code = generateCode();
        $stmt = $pdo->prepare("INSERT INTO urls (long_url, short_code) VALUES (?, ?)");
        $stmt->execute([$longUrl, $code]);

        $baseUrl = getBaseUrl();
        echo "URL pendek Anda: <a href='$baseUrl/u/$code'>$baseUrl/u/$code</a>";
    } else {
        echo "URL tidak valid.";
    }
} else {
    // Alihkan ke Admin jika URL kosong
    header('Location: /u/admin');
    exit;
}
