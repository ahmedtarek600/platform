<?php
// =============================================
// api/create_exam.php – إنشاء امتحان جديد (أدمن فقط)
// =============================================
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

// التحقق من الدور
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صحيحة']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$title         = trim($input['title'] ?? '');
$description   = trim($input['description'] ?? '');
$grade         = (int)($input['grade'] ?? 0);
$subject_id    = (int)($input['subject_id'] ?? 0);
$duration_mins = (int)($input['duration_mins'] ?? 60);
$total_marks   = (int)($input['total_marks'] ?? 100);
$pass_marks    = (int)($input['pass_marks'] ?? 50);
$questions     = $input['questions'] ?? [];

if (!$title || !$grade || !$subject_id || !$duration_mins || empty($questions)) {
    echo json_encode(['success' => false, 'message' => 'يرجى ملء جميع الحقول المطلوبة']);
    exit;
}
if ($grade < 1 || $grade > 4) {
    echo json_encode(['success' => false, 'message' => 'الفرقة غير صحيحة']);
    exit;
}

$pdo = getDB();

try {
    $pdo->beginTransaction();

    // إدراج الامتحان متوافق مع PostgreSQL و MySQL
    $stmt = $pdo->prepare("
        INSERT INTO exams (title, description, grade, subject_id, duration_mins, total_marks, pass_marks, num_questions, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        RETURNING id
    ");
    
    try {
        $stmt->execute([
            $title, $description, $grade, $subject_id,
            $duration_mins, $total_marks, $pass_marks,
            count($questions), $_SESSION['user_id']
        ]);
        $examId = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // دعم التراجع لـ MySQL في حالة عدم دعم RETURNING
        $stmt = $pdo->prepare("
            INSERT INTO exams (title, description, grade, subject_id, duration_mins, total_marks, pass_marks, num_questions, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title, $description, $grade, $subject_id,
            $duration_mins, $total_marks, $pass_marks,
            count($questions), $_SESSION['user_id']
        ]);
        $examId = $pdo->lastInsertId();
    }

    $qStmt = $pdo->prepare("
        INSERT INTO exam_questions (exam_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer, marks, order_num)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($questions as $i => $q) {
        $qText = trim($q['question_text'] ?? '');
        $qType = in_array($q['question_type'] ?? '', ['mcq','essay']) ? $q['question_type'] : 'mcq';
        if (!$qText) continue;

        $qStmt->execute([
            $examId,
            $qText,
            $qType,
            trim($q['option_a'] ?? '') ?: null,
            trim($q['option_b'] ?? '') ?: null,
            trim($q['option_c'] ?? '') ?: null,
            trim($q['option_d'] ?? '') ?: null,
            in_array($q['correct_answer'] ?? '', ['a','b','c','d']) ? $q['correct_answer'] : null,
            max(1, (int)($q['marks'] ?? 1)),
            $i + 1
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'تم رفع الامتحان بنجاح', 'exam_id' => $examId]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'خطأ في الحفظ: ' . $e->getMessage()]);
}