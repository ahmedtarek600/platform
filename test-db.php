<?php
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '5432';
$db   = getenv('DB_DATABASE') ?: 'postgres';
$user = getenv('DB_USER') ?: 'postgres';
$pass = getenv('DB_PASSWORD');

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // استعلام بسيط للتحقق من قراءة البيانات
    $stmt = $pdo->query("SELECT COUNT(*) FROM subjects");
    $count = $stmt->fetchColumn();
    
    echo "✅ تم الاتصال بنجاح! عدد المواد المسجلة: " . $count;
} catch (PDOException $e) {
    echo "❌ فشل الاتصال: " . $e->getMessage();
}