<?php
// =============================================
// api/submit_exam.php – تسليم الامتحان وحساب النتيجة
// =============================================
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مسجل الدخول']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صحيحة']);
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$sessionId = (int)($input['session_id'] ?? 0);
$answers   = $input['answers'] ?? []; // [ question_id => 'A' or text ]

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'معرف الجلسة غير صحيح']);
    exit;
}

$pdo = getDB();

try {
    // جلب الجلسة والامتحان
    $stmt = $pdo->prepare("
        SELECT es.*, e.id AS exam_id, e.pass_percentage
        FROM exam_sessions es
        JOIN exams e ON e.id = es.exam_id
        WHERE es.id = ? AND es.user_id = ?
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id']]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'الجلسة غير موجودة']);
        exit;
    }

    if ($session['status'] === 'submitted' || $session['status'] === 'timed_out') {
        echo json_encode(['success' => false, 'message' => 'تم تسليم هذا الامتحان من قبل']);
        exit;
    }

    // جلب الأسئلة لحساب الدرجة
    $stmt = $pdo->prepare("SELECT * FROM exam_questions WHERE exam_id = ?");
    $stmt->execute([$session['exam_id']]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalScore = 0;
    $earnedScore = 0;
    $hasEssay = false;

    // حفظ / تحديث الإجابات
    foreach ($questions as $q) {
        $qId   = $q['id'];
        $marks = (float)$q['marks'];
        $totalScore += $marks;

        $userAns = $answers[$qId] ?? $answers[(string)$qId] ?? null;

        $mcqAns   = null;
        $essayAns = null;
        $isCorrect = null;
        $scoreGained = 0;

        if ($q['question_type'] === 'mcq') {
            $mcqAns = is_string($userAns) ? strtoupper(trim($userAns)) : null;
            if ($mcqAns !== null && $mcqAns === strtoupper(trim($q['correct_option']))) {
                $isCorrect = true;
                $scoreGained = $marks;
            } else {
                $isCorrect = false;
                $scoreGained = 0;
            }
            $earnedScore += $scoreGained;
        } else {
            // سؤال مقالي
            $essayAns = is_string($userAns) ? trim($userAns) : null;
            $hasEssay = true;
        }

        // حفظ الإجابة في جدول exam_answers (مع تحويل is_correct للنوع البوليني)
        $stmtIns = $pdo->prepare("
            INSERT INTO exam_answers (session_id, question_id, answer_mcq, answer_essay, is_correct, score_gained)
            VALUES (?, ?, ?, ?, ?, ?)
            ON CONFLICT (session_id, question_id) DO UPDATE SET
                answer_mcq   = EXCLUDED.answer_mcq,
                answer_essay = EXCLUDED.answer_essay,
                is_correct   = EXCLUDED.is_correct,
                score_gained = EXCLUDED.score_gained
        ");
        
        $isCorrectParam = ($isCorrect === null) ? null : ($isCorrect ? 'true' : 'false');
        $stmtIns->execute([$sessionId, $qId, $mcqAns, $essayAns, $isCorrectParam, $scoreGained]);
    }

    // حساب نسبة النجاح
    $percentage = $totalScore > 0 ? round(($earnedScore / $totalScore) * 100, 2) : 0;
    $passed     = $percentage >= (float)$session['pass_percentage'];

    // تحديث الجلسة وتسليم الامتحان (إدخال القيمة FALSE البولينية صراحة)
    $stmtUpd = $pdo->prepare("
        UPDATE exam_sessions
        SET status       = 'submitted',
            submitted_at = CURRENT_TIMESTAMP,
            score        = ?,
            total_score  = ?,
            percentage   = ?,
            is_passed    = ?,
            is_reviewed  = FALSE
        WHERE id = ?
    ");
    
    $stmtUpd->execute([
        $earnedScore,
        $totalScore,
        $percentage,
        $passed ? 'true' : 'false',
        $sessionId
    ]);

    echo json_encode([
        'success'    => true,
        'message'    => 'تم تسليم الامتحان بنجاح',
        'score'      => $earnedScore,
        'total'      => $totalScore,
        'percentage' => $percentage,
        'passed'     => $passed,
        'has_essay'  => $hasEssay
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في التسليم: ' . $e->getMessage()]);
}