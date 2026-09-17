<?php
// =============================================
// api/get_uploads.php – جلب الملفات المرفوعة
// =============================================
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$isAdmin   = (($_SESSION['user_role'] ?? '') === 'admin');
$isDoctor  = (($_SESSION['user_role'] ?? '') === 'doctor');
$userGrade = (int) ($_SESSION['user_grade'] ?? $_SESSION['grade'] ?? 1);

$subjectId     = isset($_GET['subject_id'])     ? (int) $_GET['subject_id']     : 0;
$type          = trim($_GET['type']             ?? '');
$sectionNumber = isset($_GET['section_number']) ? (int) $_GET['section_number'] : null;

// الأدمن والدكتور يمكنهما تمرير grade عبر GET، الطالب يستخدم فرقته من Session
$canChooseGrade = ($isAdmin || $isDoctor);
$grade = ($canChooseGrade && isset($_GET['grade'])) ? (int) $_GET['grade'] : $userGrade;

$allowedTypes = ['lecture','task','assignment','section','schedule','exam_schedule'];

if ($subjectId < 1 || !in_array($type, $allowedTypes, true)) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة']);
    exit;
}

$pdo = getDB();

$sql    = '
    SELECT u.id, u.user_id, u.grade, u.type, u.file_path, u.original_name,
           u.section_number, u.status, u.created_at,
           usr.name AS uploader_name
    FROM uploads u
    JOIN users usr ON u.user_id = usr.id
    WHERE u.subject_id = ?
      AND u.type       = ?
      AND u.grade      = ?
';
$params = [$subjectId, $type, $grade];

// الطالب يشوف الملفات المعتمدة بس. الأدمن والدكتور يشوفوا كل الحالات
if (!$isAdmin && !$isDoctor) {
    $sql .= " AND u.status = 'approved'";
}

if ($sectionNumber !== null && $sectionNumber > 0) {
    $sql .= ' AND u.section_number = ?';
    $params[] = $sectionNumber;
}

$sql .= ' ORDER BY u.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'uploads' => $uploads]);