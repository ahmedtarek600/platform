<?php
// =============================================
// exams/take.php – صفحة أداء الامتحان
// =============================================
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../login/index.php');
    exit;
}

$examId   = (int)($_GET['exam_id'] ?? 0);
if (!$examId) {
    header('Location: ../index.php');
    exit;
}

$userName = htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES);
$userCode = htmlspecialchars($_SESSION['user_code'] ?? '', ENT_QUOTES);
$isAdmin  = ($_SESSION['user_role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>أداء الامتحان - المنصة الذكية</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/exam.css" />
</head>
<body class="exam-body">

<!-- ===== NAVBAR ===== -->
<header class="exam-navbar">
  <div class="exam-nav-inner">
    <div class="brand">
      <span class="brand-icon">🎓</span>
      <span class="brand-text">المنصة الذكية - كلية العلوم</span>
    </div>
    <div class="exam-nav-info">
      <span class="exam-user-code">كودك: <strong><?= $userCode ?></strong></span>
      <span class="exam-user-name"><?= $userName ?></span>
    </div>
  </div>
</header>

<!-- ===== INSTRUCTION PAGE ===== -->
<div id="pageInstructions" class="exam-page active">
  <div class="instructions-wrap">
    <div class="instructions-box" id="instructionsBox">
      <!-- يتملأ من JS -->
      <div class="loading-subjects" style="text-align:center;padding:60px 20px;">جاري تحميل بيانات الامتحان...</div>
    </div>
    <div class="instructions-actions" id="instructionsActions" style="display:none;">
      <p class="read-notice">📌 برجاء قراءة التعليمات جيداً قبل بدء الامتحان</p>
      <button class="btn btn-primary exam-start-btn" id="startExamBtn">
        <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M5 3L19 12L5 21V3Z" fill="currentColor"/></svg>
        ابدأ الامتحان
      </button>
      <a href="../index.php" class="btn btn-outline">العودة للمنصة</a>
    </div>
  </div>
</div>

<!-- ===== EXAM PAGE ===== -->
<div id="pageExam" class="exam-page">
  <div class="exam-layout">

    <!-- SIDEBAR (أرقام الأسئلة) -->
    <aside class="exam-sidebar">
      <div class="exam-sidebar-header">
        <span class="esi-title">الأسئلة</span>
        <div class="exam-timer" id="examTimer">
          <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8"/><path d="M12 6V12L16 14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          <span id="timerDisplay">--:--</span>
        </div>
      </div>
      <div class="exam-questions-nav" id="questionsNav">
        <!-- أرقام الأسئلة تُملأ من JS -->
      </div>
      <div class="exam-sidebar-footer">
        <div class="exam-legend">
          <span class="legend-dot dot-answered"></span> تمت الإجابة
          <span class="legend-dot dot-unanswered"></span> لم يُجَب
        </div>
        <button class="exam-submit-btn" id="submitExamBtn">
          <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V5C3 3.9 3.9 3 5 3H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          تسليم الامتحان
        </button>
      </div>
    </aside>

    <!-- MAIN EXAM AREA -->
    <main class="exam-main">
      <div class="exam-question-area" id="questionArea">
        <!-- السؤال الحالي -->
      </div>
      <div class="exam-nav-btns">
        <button class="exam-prev-btn" id="prevBtn" disabled>
          <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          السابق
        </button>
        <span class="exam-question-counter" id="questionCounter">السؤال 1 من --</span>
        <button class="exam-next-btn" id="nextBtn">
          التالي
          <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>
    </main>

  </div>
</div>

<!-- ===== CHEAT WARNING MODAL ===== -->
<div class="exam-modal" id="cheatModal">
  <div class="exam-modal-box cheat-box">
    <div class="cheat-icon">⚠️</div>
    <h3 id="cheatModalTitle">تحذير!</h3>
    <p id="cheatModalMsg">لقد خالفت قواعد الامتحان</p>
    <button class="btn btn-primary" id="cheatModalClose">فهمت، أعود للامتحان</button>
  </div>
</div>

<!-- ===== SUBMIT CONFIRM MODAL ===== -->
<div class="exam-modal" id="submitConfirmModal">
  <div class="exam-modal-box">
    <div class="cheat-icon">📋</div>
    <h3>تسليم الامتحان</h3>
    <p id="submitSummary">هل أنت متأكد من تسليم الامتحان؟</p>
    <div class="exam-modal-actions">
      <button class="btn btn-primary" id="confirmSubmitBtn">نعم، سلّم الامتحان</button>
      <button class="btn btn-outline" id="cancelSubmitBtn">العودة للمراجعة</button>
    </div>
  </div>
</div>

<!-- ===== DONE PAGE ===== -->
<div id="pageDone" class="exam-page">
  <div class="exam-done-wrap">
    <div class="exam-done-box">
      <div class="done-icon">✅</div>
      <h2>تم تسليم الامتحان بنجاح!</h2>
      <p class="done-msg">سيتم إرسال النتيجة على ملفك الشخصي بعد مراجعة الامتحان من المشرفين.</p>
      <div class="done-info" id="doneInfo"></div>
      <a href="../index.php" class="btn btn-primary" style="margin-top:24px;">العودة للمنصة</a>
    </div>
  </div>
</div>

<!-- BANNED PAGE -->
<div id="pageBanned" class="exam-page">
  <div class="exam-done-wrap">
    <div class="exam-done-box" style="border-color: rgba(239,68,68,0.4);">
      <div class="done-icon">🚫</div>
      <h2 style="color:#f87171;">تم حظرك من الامتحان</h2>
      <p class="done-msg">لقد تجاوزت عدد محاولات الغش المسموح بها (3 محاولات).<br>تم تسجيل المخالفة وإبلاغ المشرفين.</p>
      <a href="../index.php" class="btn btn-outline" style="margin-top:24px;">العودة للمنصة</a>
    </div>
  </div>
</div>

<script>
window.EXAM_APP = {
  examId:   <?= $examId ?>,
  userId:   <?= (int)$_SESSION['user_id'] ?>,
  userName: <?= json_encode($userName) ?>,
  userCode: <?= json_encode($userCode) ?>,
  isAdmin:  <?= $isAdmin ? 'true' : 'false' ?>
};
</script>
<script src="../assets/js/exam.js"></script>
</body>
</html>
