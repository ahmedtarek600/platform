<?php
// =============================================
// api/get_exams.php – جلب قائمة الامتحانات
// =============================================
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مسجل الدخول']);
    exit;
}

$pdo      = getDB();
$isAdmin  = (($_SESSION['user_role'] ?? '') === 'admin');
$userId   = (int)$_SESSION['user_id'];
$grade    = isset($_GET['grade']) ? (int)$_GET['grade'] : (int)($_SESSION['user_grade'] ?? $_SESSION['grade'] ?? 1);

try {
    $sql = "
        SELECT e.*,
               s.name AS subject_name,
               u.name AS creator_name,
               (SELECT COUNT(*) FROM exam_sessions es WHERE es.exam_id = e.id AND es.user_id = :user_id1) AS user_attempted,
               (SELECT es.status FROM exam_sessions es WHERE es.exam_id = e.id AND es.user_id = :user_id2 LIMIT 1) AS user_status,
               (SELECT COUNT(*) FROM exam_sessions es2 WHERE es2.exam_id = e.id AND es2.status = 'submitted') AS submissions_count
        FROM exams e
        JOIN subjects s ON s.id = e.subject_id
        JOIN users u    ON u.id = e.created_by
        WHERE e.grade = :grade
    ";

    if (!$isAdmin) {
        $sql .= " AND (e.is_active = TRUE OR e.is_active::text = '1')";
    }

    $sql .= " ORDER BY e.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id1', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id2', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':grade', $grade, PDO::PARAM_INT);
    $stmt->execute();

    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($exams as &$exam) {
        $exam['user_attempted']    = (int)($exam['user_attempted'] ?? 0);
        $exam['submissions_count'] = (int)($exam['submissions_count'] ?? 0);
    }

    echo json_encode(['success' => true, 'exams' => $exams]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في جلب الامتحانات',
        'error'   => $e->getMessage()
    ]);
}