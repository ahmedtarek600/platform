<?php
// =============================================
// api/approve_upload.php – الأدمن: موافقة / رفض / حذف الملف
// =============================================
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$isAdmin  = ($_SESSION['user_role'] === 'admin');
$isDoctor = ($_SESSION['user_role'] === 'doctor');
$userId   = (int) $_SESSION['user_id'];

// الموافقة/الرفض: للأدمن فقط. الحذف: للأدمن أو الدكتور (بشرط أن يكون رافع الملف)
if (!$isAdmin && !$isDoctor) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'صلاحيات غير كافية']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$uploadId = (int) ($_POST['upload_id'] ?? 0);
$action   = trim($_POST['action']      ?? ''); // 'approve' | 'reject' | 'delete'

if ($uploadId < 1 || !in_array($action, ['approve','reject','delete'], true)) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة']);
    exit;
}

// الموافقة والرفض: للأدمن فقط، الدكتور ممنوع منهما
if (($action === 'approve' || $action === 'reject') && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'صلاحيات الأدمن فقط']);
    exit;
}

$pdo = getDB();

// حذف الملف نهائياً
if ($action === 'delete') {
    $stmt = $pdo->prepare('SELECT file_path, user_id FROM uploads WHERE id = ?');
    $stmt->execute([$uploadId]);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'الملف غير موجود']);
        exit;
    }

    // الدكتور مسموح له بحذف ملفاته الخاصة فقط (وليس ملفات غيره)
    if ($isDoctor && !$isAdmin && (int) $row['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'يمكنك حذف الملفات التي رفعتها أنت فقط']);
        exit;
    }

    $fullPath = __DIR__ . '/../' . $row['file_path'];
    if (file_exists($fullPath)) {
        @unlink($fullPath);
    }

    $stmt = $pdo->prepare('DELETE FROM uploads WHERE id = ?');
    $stmt->execute([$uploadId]);

    echo json_encode(['success' => true, 'message' => 'تم حذف الملف بنجاح', 'new_status' => 'deleted']);
    exit;
}

$newStatus = ($action === 'approve') ? 'approved' : 'rejected';

$stmt = $pdo->prepare('UPDATE uploads SET status = ? WHERE id = ?');
$stmt->execute([$newStatus, $uploadId]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'الملف غير موجود']);
    exit;
}

$msg = ($action === 'approve') ? 'تم نشر الملف بنجاح' : 'تم رفض الملف';
echo json_encode(['success' => true, 'message' => $msg, 'new_status' => $newStatus]);
