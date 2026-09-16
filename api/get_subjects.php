<?php
// =============================================
// api/get_subjects.php – جلب المواد حسب الفرقة
// =============================================
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

// الأدمن والدكتور يمكنهما تمرير grade=X من الـ URL، الطالب يستخدم فرقته من الـ Session
$canChooseGrade = in_array($_SESSION['user_role'], ['admin', 'doctor'], true);
if ($canChooseGrade && isset($_GET['grade'])) {
    $grade = (int) $_GET['grade'];
} else {
    $grade = (int) ($_SESSION['user_grade'] ?? 0);
}

if ($grade < 1 || $grade > 4) {
    echo json_encode(['success' => false, 'message' => 'فرقة غير صالحة']);
    exit;
}

// الترم اختياري: لو اتبعت، فلتر بيه. لو مبعتش (زي صفحة الدكتور القديمة)، رجّع كل الترمين
$semester = null;
if (isset($_GET['semester'])) {
    $s = (int) $_GET['semester'];
    if (in_array($s, [1, 2], true)) {
        $semester = $s;
    }
}

// تحديد اسم المادة حسب اللغة المختارة بطريقة آمنة ومتوافقة مع PostgreSQL
$lang = $_COOKIE['site_lang'] ?? 'ar';
$nameColumn = ($lang === 'en') ? "COALESCE(NULLIF(name_en, ''), name)" : "name";

try {
    $pdo = getDB();

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
    // في حالة عدم وجود عمود name_en، يتم التراجع لاستخدام name فقط
    if ($lang === 'en') {
        if ($semester !== null) {
            $stmt = $pdo->prepare("SELECT id, name FROM subjects WHERE grade = ? AND semester = ? ORDER BY id");
            $stmt->execute([$grade, $semester]);
        } else {
            $stmt = $pdo->prepare("SELECT id, name FROM subjects WHERE grade = ? ORDER BY id");
            $stmt->execute([$grade]);
        }
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'subjects' => $subjects, 'grade' => $grade]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}