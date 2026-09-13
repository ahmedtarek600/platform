<?php
// =============================================
// api/delete_exam.php – حذف امتحان (أدمن)
// =============================================
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$examId = (int)($input['exam_id'] ?? 0);

if (!$examId) {
    echo json_encode(['success' => false, 'message' => 'معرف الامتحان مطلوب']);
    exit;
}

$pdo = getDB();

try {
    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
    $stmt->execute([$examId]);
    echo json_encode(['success' => true, 'message' => 'تم حذف الامتحان']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الحذف']);
}
