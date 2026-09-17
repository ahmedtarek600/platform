<?php
// =============================================
// api/get_exam_answers.php – جلب إجابات طالب معين (أدمن)
// =============================================
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك']);
    exit;
}

$sessionId = (int)($_GET['session_id'] ?? 0);
if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'معرف الجلسة مطلوب']);
    exit;
}

$pdo = getDB();

try {
    // بيانات الجلسة والامتحان
    $stmt = $pdo->prepare("
        SELECT es.*, e.title AS exam_title, e.duration_mins, e.total_marks,
               u.name AS student_name, u.code AS student_code
        FROM exam_sessions es
        JOIN exams e ON e.id = es.exam_id
        JOIN users u ON u.id = es.user_id
        WHERE es.id = ?
    ");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'الجلسة غير موجودة']);
        exit;
    }

    // جلب الأسئلة مع الإجابات
    $stmt = $pdo->prepare("
        SELECT
            eq.id AS question_id,
            eq.question_text,
            eq.question_type,
            eq.option_a, eq.option_b, eq.option_c, eq.option_d,
            eq.correct_answer,
            eq.marks,
            eq.order_num,
            ea.answer_mcq,
            ea.answer_essay,
            ea.saved_at
        FROM exam_questions eq
        LEFT JOIN exam_answers ea ON ea.question_id = eq.id AND ea.session_id = ?
        WHERE eq.exam_id = ?
        ORDER BY eq.order_num ASC
    ");
    $stmt->execute([$sessionId, $session['exam_id']]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب سجل الغش
    $stmt = $pdo->prepare("SELECT * FROM exam_cheat_log WHERE session_id = ? ORDER BY logged_at ASC");
    $stmt->execute([$sessionId]);
    $cheatLog = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'   => true,
        'session'   => $session,
        'questions' => $questions,
        'cheat_log' => $cheatLog
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في جلب البيانات']);
}