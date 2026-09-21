<?php
// =============================================
// api/get_subjects.php – جلب المواد حسب الفرقة واللغة
// =============================================
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id']) && empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

if (isset($_GET['grade']) && is_numeric($_GET['grade'])) {
    $grade = (int) $_GET['grade'];
} else {
    $grade = (int) ($_SESSION['user_grade'] ?? $_SESSION['grade'] ?? 1);
}

if ($grade < 1 || $grade > 4) {
    echo json_encode(['success' => false, 'message' => 'فرقة غير صالحة']);
    exit;
}

$semester = null;
if (isset($_GET['semester']) && is_numeric($_GET['semester'])) {
    $sem = (int)$_GET['semester'];
    if (in_array($sem, [1, 2], true)) {
        $semester = $sem;
    }
}

try {
    $pdo = getDB();

    // استخدام Type Casting صريح لضمان التوافق مع أنواع العمود في PostgreSQL ( سواء كانت integer أو varchar )
    if ($semester !== null) {
        $stmt = $pdo->prepare("
            SELECT id, name 
            FROM subjects 
            WHERE (grade::text = ?::text) 
              AND (semester::text = ?::text) 
            ORDER BY id
        ");
        $stmt->execute([(string)$grade, (string)$semester]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id, name 
            FROM subjects 
            WHERE (grade::text = ?::text) 
            ORDER BY id
        ");
        $stmt->execute([(string)$grade]);
    }

    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'  => true,
        'subjects' => $subjects,
        'grade'    => $grade
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في جلب المواد',
        'error'   => $e->getMessage()
    ]);
}