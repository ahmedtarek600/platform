<?php
// =============================================
// auth/logout.php  – تسجيل الخروج
// =============================================
require_once __DIR__ . '/../config/session.php';
session_unset();
session_destroy();

header('Location: ../login/index.php');
exit;
