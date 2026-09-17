<?php
// =============================================
// api/log_cheat.php – تسجيل محاولة غش (Backend Security)
// =============================================
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$input      = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$sessionId  = (int)($input['session_id'] ?? 0);
$cheatType  = trim($input['cheat_type'] ?? '');
$userId     = (int)$_SESSION['user_id'];

$validTypes = ['tab_switch','copy_attempt','paste_attempt','screenshot','window_blur','right_click'];
if (!$sessionId || !in_array($cheatType, $validTypes, true)) {
    echo json_encode(['success' => false]);
    exit;
}

$pdo = getDB();

try {
    // التحقق من الجلسة
    $stmt = $pdo->prepare("SELECT id, exam_id, status, cheat_attempts FROM exam_sessions WHERE id = ? AND user_id = ?");
    $stmt->execute([$sessionId, $userId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session || $session['status'] !== 'in_progress') {
        echo json_encode(['success' => false]);
        exit;
    }

    // زيادة عداد محاولات الغش
    $newAttempts = (int)$session['cheat_attempts'] + 1;
    $newStatus   = ($newAttempts >= 3) ? 'banned' : 'in_progress';

    $stmt = $pdo->prepare("UPDATE exam_sessions SET cheat_attempts = ?, status = ? WHERE id = ?");
    $stmt->execute([$newAttempts, $newStatus, $sessionId]);

    // تسجيل في سجل الغش
    $stmt = $pdo->prepare("
        INSERT INTO exam_cheat_log (session_id, user_id, exam_id, cheat_type, attempt_num, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$sessionId, $userId, $session['exam_id'], $cheatType, $newAttempts, $_SERVER['REMOTE_ADDR'] ?? null]);

    echo json_encode([
        'success'       => true,
        'cheat_attempts'=> $newAttempts,
        'is_banned'     => ($newStatus === 'banned'),
        'warning_num'   => $newAttempts
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false]);
}