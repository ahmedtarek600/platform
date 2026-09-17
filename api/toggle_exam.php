<?php
// =============================================
// api/toggle_exam.php – تفعيل / تعطيل امتحان (أدمن)
// =============================================
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$examId   = (int)($input['exam_id'] ?? 0);
$isActive = isset($input['is_active']) ? (int)$input['is_active'] : null;

if (!$examId || $isActive === null) {
    echo json_encode(['success' => false, 'message' => 'بيانات ناقصة']);
    exit;
}

$pdo = getDB();

try {
    $stmt = $pdo->prepare("UPDATE exams SET is_active = ? WHERE id = ?");
    $stmt->execute([$isActive ? 1 : 0, $examId]);
    echo json_encode([
        'success' => true,
        'message' => $isActive ? 'تم تفعيل الامتحان' : 'تم تعطيل الامتحان'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في التحديث']);
}