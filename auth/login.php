<?php
// =============================================
// auth/login.php  – معالج تسجيل الدخول
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

$code     = trim($_POST['code']     ?? '');
$password = trim($_POST['password'] ?? '');

if ($code === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => t('srv_login_fill_fields')]);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM users WHERE code = ? LIMIT 1');
$stmt->execute([$code]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => t('srv_login_wrong_creds')]);
    exit;
}

// إنشاء Session
session_regenerate_id(true);
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_code'] = $user['code'];
$_SESSION['user_grade']= (int) $user['grade'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_gpa']  = $user['gpa']  ?? null;
$_SESSION['user_cgpa'] = $user['cgpa'] ?? null;

// تحديد اسم الدور المعروض (مترجم حسب اللغة الحالية)
switch ($user['role']) {
    case 'admin':
        $roleLabel = t('srv_role_admin');
        break;
    case 'doctor':
        $roleLabel = t('srv_role_doctor');
        break;
    default:
        $roleLabel = t('srv_role_student');
}

// تحديد وجهة التحويل بعد الدخول حسب الدور
// الدكتور له لوحة تحكم خاصة لرفع المحاضرات
$redirect = '../index.php';
if ($user['role'] === 'doctor') {
    $redirect = '../dashboard/dashboard2.php';
}

echo json_encode([
    'success'   => true,
    'message'   => t('srv_login_success'),
    'redirect'  => $redirect,
    'user' => [
        'name'  => $user['name'],
        'code'  => $user['code'],
        'grade' => $user['grade'],
        'role'  => $roleLabel,
    ]
]);
