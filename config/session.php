<?php
// =============================================
// config/session.php – بدء جلسة آمن ومحسّن
// =============================================

if (session_status() === PHP_SESSION_NONE) {

    // التحقق تلقائياً هل البيئة تعمل عبر HTTPS أم HTTP محلي
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            || ($_SERVER['SERVER_PORT'] ?? 0) == 443 
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,          // تنتهي الجلسة بإغلاق المتصفح
        'path'     => '/',
        'domain'   => '',         // يترك فارغاً للتكيف الذاتي مع النطاق
        'secure'   => $isHttps,   // ✅ يعمل بـ true على HTTPS و false على localhost
        'httponly' => true,       // حماية الـ Cookie من الوصول عبر JavaScript
        'samesite' => 'Lax',      // حماية إضافية من هجمات CSRF
    ]);

    session_start();

    // تجديد معرّف الجلسة دورياً لتقليل خطر اختطاف الجلسات (كل 30 دقيقة)
    if (empty($_SESSION['_last_regen'])) {
        $_SESSION['_last_regen'] = time();
    } elseif (time() - $_SESSION['_last_regen'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = time();
    }
}