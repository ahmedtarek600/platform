<?php
// =============================================
// api/review_exam.php – مراجعة وتصحيح الامتحان (أدمن)
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

$input          = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$resultId       = (int)($input['result_id'] ?? 0);
$obtainedMarks  = isset($input['obtained_marks']) ? (int)$input['obtained_marks'] : null;
$notes          = trim($input['notes'] ?? '');
$adminId        = (int)$_SESSION['user_id'];

if (!$resultId || $obtainedMarks === null) {
    echo json_encode(['success' => false, 'message' => 'البيانات ناقصة']);
    exit;
}

$pdo = getDB();

try {
    $stmt = $pdo->prepare("
        UPDATE exam_results
        SET obtained_marks = ?, is_reviewed = TRUE, reviewed_by = ?, reviewed_at = NOW(), notes = ?
        WHERE id = ?
    ");
    $stmt->execute([$obtainedMarks, $adminId, $notes ?: null, $resultId]);

    echo json_encode(['success' => true, 'message' => 'تم حفظ الدرجة بنجاح']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الحفظ: ' . $e->getMessage()]);
}