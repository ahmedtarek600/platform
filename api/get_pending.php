<?php
// =============================================
// api/get_pending.php – الأدمن: قائمة الملفات المعلقة
// =============================================
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'صلاحيات الأدمن فقط']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->query('
    SELECT u.id, u.grade, u.type, u.file_path, u.original_name,
           u.section_number, u.status, u.created_at,
           usr.name AS uploader_name, usr.code AS uploader_code,
           s.name AS subject_name
    FROM uploads u
    JOIN users    usr ON u.user_id    = usr.id
    JOIN subjects s   ON u.subject_id = s.id
    WHERE u.status = "pending"
    ORDER BY u.created_at DESC
');
$pending = $stmt->fetchAll();

echo json_encode(['success' => true, 'pending' => $pending]);
