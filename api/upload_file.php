<?php
// =============================================
// api/upload_file.php – رفع الملفات
// =============================================
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$isAdmin   = (($_SESSION['user_role'] ?? '') === 'admin');
$isDoctor  = (($_SESSION['user_role'] ?? '') === 'doctor');
$userId    = (int) $_SESSION['user_id'];
$userGrade = (int) ($_SESSION['user_grade'] ?? $_SESSION['grade'] ?? 1);

// الأدمن والدكتور يختاران الفرقة، أما الطالب فتُحدَّد الفرقة تلقائياً من الـ Session
$canChooseGrade = ($isAdmin || $isDoctor);
if ($canChooseGrade && isset($_POST['grade'])) {
    $grade = (int) $_POST['grade'];
} else {
    $grade = $userGrade;
}

// للـ exam_schedule و schedule: تأكد أن الفرقة صحيحة ومُرسَلة دائماً
if ($grade < 1 || $grade > 4) {
    $grade = $userGrade;
}

$subjectId     = (int) ($_POST['subject_id']     ?? 0);
$type          = trim($_POST['type']             ?? '');
$sectionNumber = isset($_POST['section_number']) ? (int) $_POST['section_number'] : null;

$allowedTypes = ['lecture','task','assignment','section','schedule','exam_schedule'];

if ($subjectId < 1 || !in_array($type, $allowedTypes, true) || $grade < 1 || $grade > 4) {
    echo json_encode(['success' => false, 'message' => 'بيانات الرفع غير صالحة']);
    exit;
}

// التحقق من الملف
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'الرجاء اختيار ملف صحيح']);
    exit;
}

$maxSize = 50 * 1024 * 1024; // 50MB
if ($_FILES['file']['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'حجم الملف يتجاوز 50MB']);
    exit;
}

$allowedExts = ['pdf','doc','docx','ppt','pptx','xls','xlsx','jpg','jpeg','png','gif','mp4','avi','mkv','zip','rar'];
$originalName = $_FILES['file']['name'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExts, true)) {
    echo json_encode(['success' => false, 'message' => 'نوع الملف غير مسموح به']);
    exit;
}

// إنشاء مسار الحفظ
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$uniqueName = uniqid('file_', true) . '.' . $ext;
$filePath   = 'uploads/' . $uniqueName;
$fullPath   = __DIR__ . '/../' . $filePath;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $fullPath)) {
    echo json_encode(['success' => false, 'message' => 'فشل في رفع الملف، حاول مرة أخرى']);
    exit;
}

// الأدمن والدكتور: الملف يُنشر مباشرة / الطالب: pending بانتظار مراجعة الأدمن
$status = ($isAdmin || $isDoctor) ? 'approved' : 'pending';

$pdo  = getDB();
$stmt = $pdo->prepare('
    INSERT INTO uploads (user_id, subject_id, grade, type, file_path, original_name, section_number, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
');
$stmt->execute([
    $userId, $subjectId, $grade, $type,
    $filePath, $originalName, $sectionNumber, $status
]);

$message = ($isAdmin || $isDoctor)
    ? '✅ تم رفع الملف ونشره بنجاح'
    : '✅ تم رفع الملف وسيظهر بعد مراجعة الأدمن';

echo json_encode(['success' => true, 'message' => $message, 'status' => $status]);