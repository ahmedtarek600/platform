<?php
// =============================================
// config/db.php - Railway & Localhost Compatible
// =============================================

// قراءة رابط DATABASE_URL أو MYSQL_URL من متغيرات Railway
$dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');

if ($dbUrl) {
    // إعدادات البيئة على Railway (Production)
    $dbOpts = parse_url($dbUrl);
    $host = $dbOpts['host'];
    $port = $dbOpts['port'] ?? 3306;
    $dbHost = $host . ':' . $port;
    $dbName = ltrim($dbOpts['path'], '/');
    $dbUser = $dbOpts['user'];
    $dbPass = $dbOpts['pass'];
} else {
    // إعدادات البيئة المحلية (Localhost)
    $dbHost = getenv('MYSQLHOST') ?: 'localhost:3307';
    $dbName = getenv('MYSQLDATABASE') ?: 'math_cs_platform';
    $dbUser = getenv('MYSQLUSER') ?: 'root';
    $dbPass = getenv('MYSQLPASSWORD') ?: 'Password123.';
}

define('DB_HOST', $dbHost);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // فك تشفير DB_HOST إذا كان يحتوي على رقم المنفذ (Port)
        $hostParts = explode(':', DB_HOST);
        $host = $hostParts[0];
        $port = $hostParts[1] ?? 3306;

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host, $port, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'خطأ في الاتصال بقاعدة البيانات'
            ]));
        }
    }
    return $pdo;
}