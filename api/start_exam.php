<?php
// =============================================
// api/start_exam.php – بدء جلسة الامتحان
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

$input   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$examId  = (int)($input['exam_id'] ?? 0);
$userId  = (int)$_SESSION['user_id'];

if (!$examId) {
    echo json_encode(['success' => false, 'message' => 'معرف الامتحان غير صحيح']);
    exit;
}

$pdo = getDB();

try {
    // جلب بيانات الامتحان
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND is_active = 1");
    $stmt->execute([$examId]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        echo json_encode(['success' => false, 'message' => 'الامتحان غير موجود أو غير مفعّل']);
        exit;
    }

    // التحقق من أن اليوزر لم يأدِ الامتحان قبل كده (allow_once)
    if (!empty($exam['allow_once'])) {
        $stmt = $pdo->prepare("SELECT id, status FROM exam_sessions WHERE exam_id = ? AND user_id = ?");
        $stmt->execute([$examId, $userId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ($existing['status'] === 'banned') {
                echo json_encode(['success' => false, 'message' => 'تم حظرك من هذا الامتحان بسبب محاولة الغش']);
                exit;
            }
            if ($existing['status'] === 'submitted' || $existing['status'] === 'timed_out') {
                echo json_encode(['success' => false, 'message' => 'لقد أديت هذا الامتحان من قبل ولا يمكن تكراره']);
                exit;
            }
            // جلسة في التقدم - استكمال
            $sessionId = $existing['id'];
        } else {
            $sessionId = null;
        }
    } else {
        $sessionId = null;
    }

    // إنشاء جلسة جديدة لو مفيش
    if (!$sessionId) {
        $stmt = $pdo->prepare("
            INSERT INTO exam_sessions (exam_id, user_id, ip_address)
            VALUES (?, ?, ?)
            RETURNING id
        ");
        $stmt->execute([$examId, $userId, $_SERVER['REMOTE_ADDR'] ?? null]);
        $sessionId = $stmt->fetchColumn();
    }

    // جلب أسئلة الامتحان
    $stmt = $pdo->prepare("
        SELECT id, question_text, question_type, option_a, option_b, option_c, option_d, marks, order_num
        FROM exam_questions
        WHERE exam_id = ?
        ORDER BY order_num ASC
    ");
    $stmt->execute([$examId]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب الإجابات المحفوظة مسبقاً (auto-save)
    $stmt = $pdo->prepare("
        SELECT question_id, answer_mcq, answer_essay
        FROM exam_answers
        WHERE session_id = ?
    ");
    $stmt->execute([$sessionId]);
    $savedAnswers = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ans) {
        $savedAnswers[$ans['question_id']] = $ans;
    }

    // حساب الوقت المتبقي
    $stmt = $pdo->prepare("SELECT started_at FROM exam_sessions WHERE id = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $elapsed = time() - strtotime($session['started_at']);
    $totalSecs = (int)$exam['duration_mins'] * 60;
    $remaining = max(0, $totalSecs - $elapsed);

    echo json_encode([
        'success'        => true,
        'session_id'     => $sessionId,
        'exam'           => $exam,
        'questions'      => $questions,
        'saved_answers'  => $savedAnswers,
        'time_remaining' => $remaining
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في بدء الامتحان: ' . $e->getMessage()]);
}