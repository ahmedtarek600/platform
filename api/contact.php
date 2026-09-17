<?php
// =============================================
// api/contact.php – نموذج التواصل
// =============================================
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$name  = trim($_POST['name']  ?? '');
$code  = trim($_POST['code']  ?? '');
$email = trim($_POST['email'] ?? '');
$msg   = trim($_POST['msg']   ?? '');
$topic = trim($_POST['topic'] ?? 'غير محدد');

if ($name === '' || $code === '' || $email === '' || $msg === '') {
    echo json_encode(['success' => false, 'message' => 'من فضلك أكمل جميع الحقول أولاً']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني غير صحيح']);
    exit;
}

$to      = 'support@scienceplatform.edu.eg';
$subject = '=?UTF-8?B?' . base64_encode("رسالة جديدة من المنصة - $topic") . '?=';

$body  = "الموضوع: $topic\n";
$body .= "الاسم: $name\n";
$body .= "الكود الجامعي: $code\n";
$body .= "البريد الإلكتروني: $email\n";
$body .= "-----------------------------\n";
$body .= "$msg\n";

$headers  = "From: noreply@scienceplatform.edu.eg\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = @mail($to, $subject, $body, $headers);

echo json_encode(['success' => true, 'message' => 'تم إرسال رسالتك بنجاح، سنرد عليك قريباً']);