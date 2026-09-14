<?php
// test-db.php - مؤقت (احذفه بعد الاستخدام!)
require_once 'config/db.php';

try {
    $pdo = getDB();
    $result = $pdo->query('SELECT 1 as test');
    echo json_encode([
        'success' => true,
        'message' => '✅ الاتصال بقاعدة البيانات نجح!',
        'database' => DB_NAME
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '❌ فشل الاتصال',
        'error' => $e->getMessage()
    ]);
}