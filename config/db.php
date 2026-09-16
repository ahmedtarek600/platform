<?php
// =============================================
// config/db.php - Railway (PostgreSQL) & Localhost (MySQL) Compatible
// =============================================

// قراءة متغيرات البيئة من Railway للـ PostgreSQL أولاً أو DATABASE_URL
$pgHost = getenv('DB_HOST');
$dbUrl  = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');

if ($pgHost) {
    // ✅ إعدادات البيئة على Railway باستخدام متغيرة DB_HOST المباشرة (PostgreSQL)
    $driver = 'pgsql';
    $host   = $pgHost;
    $port   = getenv('DB_PORT') ?: '6543';
    $dbName = getenv('DB_DATABASE') ?: 'postgres';
    $dbUser = getenv('DB_USER') ?: 'postgres';
    $dbPass = getenv('DB_PASSWORD');
} elseif ($dbUrl) {
    // ✅ إعدادات البيئة على Railway في حال استخدام URL الموحد
    $dbOpts = parse_url($dbUrl);
    $driver = ($dbOpts['scheme'] === 'postgres' || $dbOpts['scheme'] === 'postgresql') ? 'pgsql' : 'mysql';
    $host   = $dbOpts['host'];
    $port   = $dbOpts['port'] ?? ($driver === 'pgsql' ? 6543 : 3306);
    $dbName = ltrim($dbOpts['path'], '/');
    $dbUser = $dbOpts['user'];
    $dbPass = $dbOpts['pass'];
} else {
    // 🏠 إعدادات البيئة المحلية (Localhost - MySQL) المحفوظة كما هي
    $driver = 'mysql';
    $hostParts = explode(':', getenv('MYSQL_HOST') ?: 'localhost:3307');
    $host   = $hostParts[0];
    $port   = $hostParts[1] ?? 3307;
    $dbName = getenv('MYSQL_DATABASE') ?: 'math_cs_platform';
    $dbUser = getenv('MYSQL_USER') ?: 'root';
    $dbPass = getenv('MYSQL_PASSWORD') ?: 'Password123.';
}

define('DB_DRIVER', $driver);
define('DB_HOST', $host);
define('DB_PORT', $port);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);

function getDB(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        // بناء الـ DSN حسب نوع قاعدة البيانات (pgsql للإنتاج أو mysql للّوكال)
        if (DB_DRIVER === 'pgsql') {
            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
        } else {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        }
        
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
                'message' => 'خطأ في الاتصال بقاعدة البيانات',
                'error'   => $e->getMessage()
            ]));
        }
    }
    
    return $pdo;
}

// إنشاء متغير اتصال عام $conn أو $pdo لتوافق السكربتات القديمة
try {
    $conn = getDB();
    $pdo  = $conn;
} catch (Exception $e) {
    // يتم التعامل مع الخطأ داخل getDB()
}