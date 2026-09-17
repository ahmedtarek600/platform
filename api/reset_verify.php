<?php
// =============================================
// api/reset_verify.php – التحقق من وجود الكود
// =============================================
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$code = trim($_POST['code'] ?? '');
if ($code === '') {
    echo json_encode(['success' => false, 'message' => 'الرجاء إدخال الكود الجامعي']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT id FROM users WHERE code = ? LIMIT 1');
$stmt->execute([$code]);

if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode(['success' => false, 'message' => 'الكود الجامعي غير موجود في النظام']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'تم التحقق من الهوية بنجاح']);