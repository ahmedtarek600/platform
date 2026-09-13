<?php
// =============================================
// config/db.php - Railway Compatible
// =============================================
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost:3307');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'math_cs_platform');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: 'Password123.');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
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