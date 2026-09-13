<?php
// =============================================
// config/session.php – بدء جلسة آمن
// يستبدل استدعاء session_start() المباشر في كل الملفات
// =============================================

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 0,       // تنتهي الجلسة بإغلاق المتصفح
        'path'     => '/',
        'domain'   => '',      // اتركه فارغاً على localhost
             'secure'   => true,    // ✅ Railway بيستخدم HTTPS
        'httponly' => true,    // يمنع قراءة الكوكي عن طريق JavaScript
        'samesite' => 'Lax',   // يمنع إرسال الكوكي مع طلبات من مواقع أخرى (حماية إضافية من CSRF)
    ]);

    session_start();

    // تجديد معرّف الجلسة دورياً لتقليل خطر اختطافها (كل 30 دقيقة)
    if (empty($_SESSION['_last_regen'])) {
        $_SESSION['_last_regen'] = time();
    } elseif (time() - $_SESSION['_last_regen'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = time();
    }
}
