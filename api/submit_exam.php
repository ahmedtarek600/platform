<?php
// =============================================
// api/submit_exam.php – تسليم الامتحان
// =============================================
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مسجل الدخول']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$sessionId = (int)($input['session_id'] ?? 0);
$userId    = (int)$_SESSION['user_id'];

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'معرف الجلسة غير صحيح']);
    exit;
}

$pdo = getDB();

try {
    // التحقق من الجلسة
    $stmt = $pdo->prepare("SELECT es.*, e.total_marks, e.title AS exam_title FROM exam_sessions es JOIN exams e ON e.id = es.exam_id WHERE es.id = ? AND es.user_id = ?");
    $stmt->execute([$sessionId, $userId]);
    $session = $stmt->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'الجلسة غير موجودة']);
        exit;
    }

    if (in_array($session['status'], ['submitted', 'timed_out', 'banned'])) {
        echo json_encode(['success' => false, 'message' => 'تم تسليم الامتحان مسبقاً']);
        exit;
    }

    $pdo->beginTransaction();

    // تحديث حالة الجلسة
    $stmt = $pdo->prepare("UPDATE exam_sessions SET status = 'submitted', submitted_at = NOW() WHERE id = ?");
    $stmt->execute([$sessionId]);

    // إنشاء سجل النتيجة (مؤقتاً بدون درجة – الأدمن هيحطها)
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO exam_results (session_id, exam_id, user_id, total_marks, is_reviewed)
        VALUES (?, ?, ?, ?, 0)
    ");
    $stmt->execute([$sessionId, $session['exam_id'], $userId, $session['total_marks']]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'تم تسليم الامتحان بنجاح. سيتم إرسال النتيجة بعد مراجعة المشرف.',
        'exam_title' => $session['exam_title']
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'خطأ في التسليم: ' . $e->getMessage()]);
}
