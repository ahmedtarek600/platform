<?php
// =============================================
// api/get_exam_submissions.php – جلب الامتحانات المسلّمة (أدمن + طالب)
// =============================================
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

$pdo     = getDB();
$userId  = (int)$_SESSION['user_id'];
$isAdmin = ($_SESSION['user_role'] === 'admin');

try {
    if ($isAdmin) {
        // الأدمن: كل الامتحانات المسلّمة وغير المراجعة
        $stmt = $pdo->prepare("
            SELECT
                er.id AS result_id,
                er.obtained_marks,
                er.is_reviewed,
                er.reviewed_at,
                er.notes,
                er.total_marks,
                es.submitted_at,
                es.cheat_attempts,
                es.status AS session_status,
                es.id AS session_id,
                e.title  AS exam_title,
                e.grade  AS exam_grade,
                s.name   AS subject_name,
                u.name   AS student_name,
                u.code   AS student_code,
                u.id     AS student_id
            FROM exam_results er
            JOIN exam_sessions es ON es.id = er.session_id
            JOIN exams e          ON e.id  = er.exam_id
            JOIN subjects s       ON s.id  = e.subject_id
            JOIN users u          ON u.id  = er.user_id
            ORDER BY er.is_reviewed ASC, es.submitted_at DESC
        ");
        $stmt->execute();
    } else {
        // الطالب: نتائجه فقط
        $stmt = $pdo->prepare("
            SELECT
                er.id AS result_id,
                er.obtained_marks,
                er.is_reviewed,
                er.reviewed_at,
                er.total_marks,
                er.notes,
                es.submitted_at,
                e.title   AS exam_title,
                e.pass_marks,
                s.name    AS subject_name
            FROM exam_results er
            JOIN exam_sessions es ON es.id = er.session_id
            JOIN exams e          ON e.id  = er.exam_id
            JOIN subjects s       ON s.id  = e.subject_id
            WHERE er.user_id = ?
            ORDER BY es.submitted_at DESC
        ");
        $stmt->execute([$userId]);
    }

    $submissions = $stmt->fetchAll();
    echo json_encode(['success' => true, 'submissions' => $submissions]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في جلب البيانات']);
}
