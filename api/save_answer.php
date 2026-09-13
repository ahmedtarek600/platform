<?php
// =============================================
// api/save_answer.php – حفظ إجابة (Auto-Save)
// =============================================
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$input       = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$sessionId   = (int)($input['session_id'] ?? 0);
$questionId  = (int)($input['question_id'] ?? 0);
$answerMcq   = $input['answer_mcq']   ?? null;
$answerEssay = $input['answer_essay'] ?? null;
$userId      = (int)$_SESSION['user_id'];

if (!$sessionId || !$questionId) {
    echo json_encode(['success' => false]);
    exit;
}

$pdo = getDB();

try {
    // التحقق أن الجلسة تخص هذا المستخدم وما زالت in_progress
    $stmt = $pdo->prepare("SELECT id, status FROM exam_sessions WHERE id = ? AND user_id = ?");
    $stmt->execute([$sessionId, $userId]);
    $session = $stmt->fetch();

    if (!$session || $session['status'] !== 'in_progress') {
        echo json_encode(['success' => false, 'message' => 'الجلسة منتهية']);
        exit;
    }

    // حفظ أو تحديث الإجابة
    $stmt = $pdo->prepare("
        INSERT INTO exam_answers (session_id, question_id, answer_mcq, answer_essay)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            answer_mcq   = VALUES(answer_mcq),
            answer_essay = VALUES(answer_essay),
            saved_at     = CURRENT_TIMESTAMP
    ");

    $mcq   = in_array($answerMcq, ['a','b','c','d']) ? $answerMcq : null;
    $essay = $answerEssay ? trim($answerEssay) : null;
    $stmt->execute([$sessionId, $questionId, $mcq, $essay]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
