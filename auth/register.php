<?php
// =============================================
// auth/register.php  – معالج التسجيل
// =============================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/lang.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$name     = trim($_POST['name']     ?? '');
$code     = trim($_POST['code']     ?? '');
$grade    = (int) ($_POST['grade']  ?? 0);
$password = trim($_POST['password'] ?? '');
$confirm  = trim($_POST['confirm']  ?? '');

// Validation
if ($name === '' || $code === '' || $grade < 1 || $grade > 4 || $password === '') {
    echo json_encode(['success' => false, 'message' => t('srv_reg_fill_fields')]);
    exit;
}
if ($password !== $confirm) {
    echo json_encode(['success' => false, 'message' => t('srv_reg_pass_mismatch')]);
    exit;
}
if (mb_strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => t('srv_reg_pass_short')]);
    exit;
}

$pdo = getDB();

// التحقق من تكرار الكود
$check = $pdo->prepare('SELECT id FROM users WHERE code = ? LIMIT 1');
$check->execute([$code]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => t('srv_reg_code_taken')]);
    exit;
}

$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
    'INSERT INTO users (name, code, password, grade, role) VALUES (?, ?, ?, ?, ?)'
);
// role يتم تغييره من داخل قاعدة البيانات بواسطة الأدمن فقط
$stmt->execute([$name, $code, $hashed, $grade, 'user']);

echo json_encode([
    'success'  => true,
    'message'  => t('srv_reg_success'),
    'redirect' => '../login/index.php'
]);
