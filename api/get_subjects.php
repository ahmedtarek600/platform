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

// قراءة الفرقة الممررة في الطلب مباشرة، أو العودة لفرقة المستخدم المسجلة
if (isset($_GET['grade']) && is_numeric($_GET['grade'])) {
    $grade = (int) $_GET['grade'];
} else {
    $grade = (int) ($_SESSION['user_grade'] ?? $_SESSION['grade'] ?? 1);
}

if ($grade < 1 || $grade > 4) {
    echo json_encode(['success' => false, 'message' => 'فرقة غير صالحة']);
    exit;
}

// الترم اختياري
$semester = null;
if (isset($_GET['semester']) && in_array((int)$_GET['semester'], [1, 2], true)) {
    $semester = (int) $_GET['semester'];
}

// تحديد اللغة الحالية من كافة المصادر الممكنة (Session / Cookie)
$lang = $_SESSION['lang'] ?? $_COOKIE['site_lang'] ?? $_COOKIE['lang'] ?? 'ar';

try {
    $pdo = getDB();

    // اختيار عمود الاسم بحسب اللغة والتوافق مع PostgreSQL
    $nameColumn = ($lang === 'en') ? "COALESCE(NULLIF(name_en, ''), name)" : "name";

    if ($semester !== null) {
        $stmt = $pdo->prepare("SELECT id, {$nameColumn} AS name FROM subjects WHERE grade = ? AND semester = ? ORDER BY id");
        $stmt->execute([$grade, $semester]);
    } else {
        $stmt = $pdo->prepare("SELECT id, {$nameColumn} AS name FROM subjects WHERE grade = ? ORDER BY id");
        $stmt->execute([$grade]);
    }
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'subjects' => $subjects, 'grade' => $grade]);
} catch (PDOException $e) {
    // التراجع التلقائي لعمود name فقط في حال عدم وجود عمود name_en بالداتابيز
    if ($semester !== null) {
        $stmt = $pdo->prepare("SELECT id, name FROM subjects WHERE grade = ? AND semester = ? ORDER BY id");
        $stmt->execute([$grade, $semester]);
    } else {
        $stmt = $pdo->prepare("SELECT id, name FROM subjects WHERE grade = ? ORDER BY id");
        $stmt->execute([$grade]);
    }
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'subjects' => $subjects, 'grade' => $grade]);
}