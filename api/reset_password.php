<?php
// =============================================
// api/reset_password.php – حفظ كلمة المرور الجديدة
// =============================================
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$code     = trim($_POST['code']     ?? '');
$password = $_POST['password']      ?? '';

if ($code === '' || strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة']);
    exit;
}

$pdo  = getDB();

// التحقق من وجود المستخدم
$stmt = $pdo->prepare('SELECT id FROM users WHERE code = ? LIMIT 1');
$stmt->execute([$code]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'الكود الجامعي غير موجود']);
    exit;
}

$hashed = password_hash($password, PASSWORD_BCRYPT);
$upd    = $pdo->prepare('UPDATE users SET password = ? WHERE code = ?');
$upd->execute([$hashed, $code]);

echo json_encode(['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح']);
