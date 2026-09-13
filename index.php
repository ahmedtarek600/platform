<?php
// =============================================
// index.php – الصفحة الرئيسية (Hero Page)
// =============================================
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/lang.php';

// زرار تبديل اللغة (يُستخدم في النافيجيشن، ديسكتوب وموبايل)
$nextLang       = ($currentLang === 'ar') ? 'en' : 'ar';
$currentPathUrl = $_SERVER['REQUEST_URI'] ?? '/';
$langToggleHref = '/set_lang.php?lang=' . $nextLang . '&return=' . urlencode($currentPathUrl);

$isLoggedIn = !empty($_SESSION['user_id']);
$userName   = $isLoggedIn ? htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES) : '';
$userCode   = $isLoggedIn ? htmlspecialchars($_SESSION['user_code'] ?? '', ENT_QUOTES) : '';
$userGrade  = $isLoggedIn ? (int) ($_SESSION['user_grade'] ?? 0) : 0;
$userRole   = $isLoggedIn ? $_SESSION['user_role'] : '';
$isAdmin    = ($userRole === 'admin');

// Avatar: أول حرفين من الاسم
function getInitials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) >= 2) return mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1);
    return mb_substr($parts[0] ?? 'م', 0, 1);
}
$initials  = $isLoggedIn ? getInitials($_SESSION['user_name'] ?? 'م') : 'م';
$roleLabel = $isAdmin ? t('srv_role_admin') : t('srv_role_student');
$gradeLabels = [1=>t('grade_1'),2=>t('grade_2'),3=>t('grade_3'),4=>t('grade_4')];
$gradeLabel  = $gradeLabels[$userGrade] ?? '—';

// GPA / CGPA: يتم تحديدهما من الأدمن فقط عبر قاعدة البيانات
$userGpa  = $isLoggedIn ? ($_SESSION['user_gpa']  ?? null) : null;
$userCgpa = $isLoggedIn ? ($_SESSION['user_cgpa'] ?? null) : null;
$gpaLabel  = ($userGpa  !== null && $userGpa  !== '') ? number_format((float) $userGpa, 2)  : '—';
$cgpaLabel = ($userCgpa !== null && $userCgpa !== '') ? number_format((float) $userCgpa, 2) : '—';

/**
 * دالة مساعدة لتوليد هيكل الـ Custom Dropdown كاملًا
 * @param string $id           معرّف الـ select المخفي
 * @param array  $options      خيارات من [قيمة => نص]
 * @param string $selectedValue القيمة المختارة حاليًا
 * @return string HTML الكود
 */
function renderCustomDropdown(string $id, array $options, string $selectedValue = ''): string
{
    $html = '<div class="custom-dropdown" data-select-id="' . $id . '">';
    $html .= '<button type="button" class="dropdown-btn">';
    // اختيار النص المناسب للقيمة الحالية
    $selectedText = '';
    foreach ($options as $val => $text) {
        if ((string)$val === (string)$selectedValue) {
            $selectedText = $text;
            break;
        }
    }
    $html .= '<span class="dropdown-btn-text">' . ($selectedText ?: t('select_placeholder')) . '</span>';
    $html .= '<svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    $html .= '</button>';
    $html .= '<div class="dropdown-menu">';
    foreach ($options as $val => $text) {
        $isActive = ((string)$val === (string)$selectedValue) ? ' active' : '';
        $html .= '<div class="dropdown-item' . $isActive . '" data-value="' . $val . '">' . $text . '</div>';
    }
    $html .= '</div>';
    // الـ select المخفي (يحافظ على القيمة الحقيقية)
    $html .= '<select id="' . $id . '" hidden>';
    foreach ($options as $val => $text) {
        $sel = ((string)$val === (string)$selectedValue) ? ' selected' : '';
        $html .= '<option value="' . $val . '"' . $sel . '>' . $text . '</option>';
    }
    $html .= '</select>';
    $html .= '</div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $currentDir ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars(t('brand'), ENT_QUOTES) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/exam.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
</head>

<body>

  <!-- ===== LOGIN REQUIRED BANNER ===== -->
  <div class="login-toast" id="loginToast">
    <?= htmlspecialchars(t('login_required_banner'), ENT_QUOTES) ?>
  </div>

  <!-- NAVBAR -->
  <header class="navbar" id="mainNav">
    <div class="nav-container">
      <div class="brand">
        <span class="brand-icon">🎓</span>
        <span class="brand-text"><?= htmlspecialchars(t('brand'), ENT_QUOTES) ?></span>
      </div>
      <nav class="nav-links">
        <a class="nav-link active" href="#" data-view="home"><?= htmlspecialchars(t('nav_home'), ENT_QUOTES) ?></a>
        <a class="nav-link" href="#" data-view="features"><?= htmlspecialchars(t('nav_features'), ENT_QUOTES) ?></a>
        <a class="nav-link" href="#" data-view="dashboard" id="navDashboard"><?= htmlspecialchars(t('nav_sections'), ENT_QUOTES) ?></a>
        <a class="nav-link" href="#" data-view="contact"><?= htmlspecialchars(t('nav_feedback'), ENT_QUOTES) ?></a>
      </nav>

      <?php if (!$isLoggedIn): ?>
      <!-- Guest Actions -->
      <div class="nav-actions" id="guestActions">
        <a class="lang-nav-btn" href="<?= htmlspecialchars($langToggleHref, ENT_QUOTES) ?>">🌐 <?= htmlspecialchars(t('lang_toggle'), ENT_QUOTES) ?></a>
        <a class="btn btn-outline" href="login/index.php"><?= htmlspecialchars(t('nav_login'), ENT_QUOTES) ?></a>
        <a class="btn btn-solid"   href="register/index.php"><?= htmlspecialchars(t('nav_register'), ENT_QUOTES) ?></a>
        <button class="menu-btn" id="menuBtn">☰</button>
      </div>
      <?php else: ?>
      <!-- User Actions -->
      <div class="nav-actions" id="userActions">
        <a class="lang-nav-btn" href="<?= htmlspecialchars($langToggleHref, ENT_QUOTES) ?>">🌐 <?= htmlspecialchars(t('lang_toggle'), ENT_QUOTES) ?></a>
        <div class="nav-user-wrap">
          <div class="nav-user-avatar" id="navUserAvatar" title="<?= htmlspecialchars(t('dash_profile'), ENT_QUOTES) ?>">
            <?= htmlspecialchars($initials, ENT_QUOTES) ?>
          </div>
          <div class="nav-user-tooltip">
            <span class="nav-user-name"><?= $userName ?></span>
            <span class="nav-user-role"><?= $roleLabel ?></span>
          </div>
        </div>
        <a class="nav-logout-btn" href="auth/logout.php">
          <svg viewBox="0 0 24 24" fill="none" width="16" height="16">
            <path d="M9 21H5C4.5 21 4 20.8 3.6 20.4C3.2 20 3 19.5 3 19V5C3 4.5 3.2 4 3.6 3.6C4 3.2 4.5 3 5 3H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M16 17L21 12L16 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M21 12H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          <?= htmlspecialchars(t('nav_logout'), ENT_QUOTES) ?>
        </a>
        <button class="menu-btn" id="menuBtn2">☰</button>
      </div>
      <?php endif; ?>
    </div>

    <div class="mobile-menu" id="mobileMenu">
      <a href="#" class="mobile-link" data-view="home"><?= htmlspecialchars(t('nav_home'), ENT_QUOTES) ?></a>
      <a href="#" class="mobile-link" data-view="features"><?= htmlspecialchars(t('nav_features'), ENT_QUOTES) ?></a>
      <a href="#" class="mobile-link" data-view="dashboard"><?= htmlspecialchars(t('nav_sections'), ENT_QUOTES) ?></a>
      <a href="#" class="mobile-link" data-view="contact"><?= htmlspecialchars(t('nav_feedback'), ENT_QUOTES) ?></a>
      <a href="<?= htmlspecialchars($langToggleHref, ENT_QUOTES) ?>" class="mobile-link">🌐 <?= htmlspecialchars(t('lang_toggle'), ENT_QUOTES) ?></a>
      <?php if (!$isLoggedIn): ?>
      <div class="mobile-actions">
        <a class="btn btn-outline w-100" href="login/index.php"><?= htmlspecialchars(t('nav_login'), ENT_QUOTES) ?></a>
        <a class="btn btn-solid w-100"   href="register/index.php"><?= htmlspecialchars(t('nav_register'), ENT_QUOTES) ?></a>
      </div>
      <?php else: ?>
      <div class="mobile-actions">
        <a class="btn btn-outline w-100" href="auth/logout.php"><?= htmlspecialchars(t('nav_logout'), ENT_QUOTES) ?></a>
      </div>
      <?php endif; ?>
    </div>
  </header>

  <!-- ===== VIEW: HOME ===== -->
  <div class="view active" id="viewHome">
    <main class="hero">
      <div class="hero-bg"></div>
      <section class="hero-content">
        <div class="hero-text">
          <h1 class="hero-title">
            <span class="highlight"><?= htmlspecialchars(t('home_title_highlight'), ENT_QUOTES) ?></span><br />
            <?= htmlspecialchars(t('home_title_rest'), ENT_QUOTES) ?>
          </h1>
          <p class="hero-subtitle">
            <?= htmlspecialchars(t('home_subtitle'), ENT_QUOTES) ?>
          </p>
          <div class="hero-cta">
            <a class="btn btn-primary" href="#" id="startLearningBtn">
              <?= htmlspecialchars(t('home_cta'), ENT_QUOTES) ?>
              <span class="btn-arrow"><?= htmlspecialchars(t('home_cta_arrow'), ENT_QUOTES) ?></span>
            </a>
          </div>
          <div class="hero-badges">
            <span class="badge"><?= htmlspecialchars(t('home_badge_lectures'), ENT_QUOTES) ?></span>
            <span class="badge"><?= htmlspecialchars(t('home_badge_sections'), ENT_QUOTES) ?></span>
            <span class="badge"><?= htmlspecialchars(t('home_badge_tasks'), ENT_QUOTES) ?></span>
            <span class="badge"><?= htmlspecialchars(t('home_badge_exams'), ENT_QUOTES) ?></span>
          </div>
        </div>
      </section>
    </main>
  </div>

  <!-- ===== VIEW: FEATURES ===== -->
  <div class="view" id="viewFeatures">
    <div class="page-section">
      <div class="section-header">
        <div class="section-emoji">🎯</div>
        <h2><?= htmlspecialchars(t('feat_title'), ENT_QUOTES) ?></h2>
        <p><?= htmlspecialchars(t('feat_subtitle'), ENT_QUOTES) ?></p>
      </div>
      <div class="features-grid">
        <div class="feat-card"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div><h3><?= htmlspecialchars(t('feat1_title'), ENT_QUOTES) ?></h3><p><?= htmlspecialchars(t('feat1_desc'), ENT_QUOTES) ?></p></div>
        <div class="feat-card"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4.5 7.5H14.5C15.6 7.5 16.5 8.4 16.5 9.5V14.5C16.5 15.6 15.6 16.5 14.5 16.5H4.5C3.4 16.5 2.5 15.6 2.5 14.5V9.5C2.5 8.4 3.4 7.5 4.5 7.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M16.5 10L21.5 7.5V16.5L16.5 14" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></div><h3><?= htmlspecialchars(t('feat2_title'), ENT_QUOTES) ?></h3><p><?= htmlspecialchars(t('feat2_desc'), ENT_QUOTES) ?></p></div>
        <div class="feat-card"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M20 21V19C20 17.9 19.1 17 18 17H6C4.9 17 4 17.9 4 19V21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 13C14.2 13 16 11.2 16 9C16 6.8 14.2 5 12 5C9.8 5 8 6.8 8 9C8 11.2 9.8 13 12 13Z" stroke="currentColor" stroke-width="1.8"/></svg></div><h3><?= htmlspecialchars(t('feat3_title'), ENT_QUOTES) ?></h3><p><?= htmlspecialchars(t('feat3_desc'), ENT_QUOTES) ?></p></div>
        <div class="feat-card"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V5C3 3.9 3.9 3 5 3H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></div><h3><?= htmlspecialchars(t('feat4_title'), ENT_QUOTES) ?></h3><p><?= htmlspecialchars(t('feat4_desc'), ENT_QUOTES) ?></p></div>
        <div class="feat-card"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3.5H15L19.5 8V20.5H7C5.9 20.5 5 19.6 5 18.5V5.5C5 4.4 5.9 3.5 7 3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M15 3.5V8H19.5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8 12H16M8 15.5H13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div><h3><?= htmlspecialchars(t('feat5_title'), ENT_QUOTES) ?></h3><p><?= htmlspecialchars(t('feat5_desc'), ENT_QUOTES) ?></p></div>
        <div class="feat-card"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M21 15C21 15.5 20.8 16 20.4 16.4C20 16.8 19.5 17 19 17H7L3 21V5C3 4.5 3.2 4 3.6 3.6C4 3.2 4.5 3 5 3H19C19.5 3 20 3.2 20.4 3.6C20.8 4 21 4.5 21 5V15Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div><h3><?= htmlspecialchars(t('feat6_title'), ENT_QUOTES) ?></h3><p><?= htmlspecialchars(t('feat6_desc'), ENT_QUOTES) ?></p></div>
      </div>
    </div>
  </div>

  <!-- ===== VIEW: CONTACT (شاركنا برأيك) ===== -->
  <div class="view" id="viewContact">
    <div class="page-section">
    
      <!-- Header -->
      <div class="section-header">
        <div class="cnt-page-icon"><i class="fas fa-comments" style="color:#fff"></i></div>
        <h2><?= htmlspecialchars(t('contact_page_title'), ENT_QUOTES) ?></h2>
        <p><?= htmlspecialchars(t('contact_page_sub'), ENT_QUOTES) ?></p>
      </div>

      <!-- Layout: form + info -->
      <div class="cnt-layout">

        <!-- Info cards -->
        <div class="cnt-info-col">
          <div class="cnt-info-card">
            <div class="cnt-info-icon"><i class="fas fa-envelope"></i></div>
            <div>
              <div class="cnt-info-label"><?= htmlspecialchars(t('contact_info_email'), ENT_QUOTES) ?></div>
              <div class="cnt-info-value">support@scienceplatform.edu.eg</div>
            </div>
          </div>
          <div class="cnt-info-card">
            <div class="cnt-info-icon"><i class="fas fa-location-dot"></i></div>
            <div>
              <div class="cnt-info-label"><?= htmlspecialchars(t('contact_info_loc'), ENT_QUOTES) ?></div>
              <div class="cnt-info-value"><?= htmlspecialchars(t('contact_info_loc_val'), ENT_QUOTES) ?></div>
            </div>
          </div>
          <div class="cnt-info-card">
            <div class="cnt-info-icon"><i class="fas fa-clock"></i></div>
            <div>
              <div class="cnt-info-label"><?= htmlspecialchars(t('contact_info_reply'), ENT_QUOTES) ?></div>
              <div class="cnt-info-value"><?= htmlspecialchars(t('contact_info_reply_val'), ENT_QUOTES) ?></div>
            </div>
          </div>
          <div class="cnt-info-card">
            <div class="cnt-info-icon"><i class="fas fa-headset"></i></div>
            <div>
              <div class="cnt-info-label"><?= htmlspecialchars(t('contact_info_support'), ENT_QUOTES) ?></div>
              <div class="cnt-info-value"><?= htmlspecialchars(t('contact_info_support_val'), ENT_QUOTES) ?></div>
            </div>
          </div>
        </div>

        <!-- Form -->
        <div class="cnt-form-col">
          <div class="cnt-form-card">

            <div class="cnt-form-row">
              <div class="c-field">
                <label for="cName"><?= htmlspecialchars(t('contact_name_label'), ENT_QUOTES) ?></label>
                <input type="text" class="cnt-input" id="cName" placeholder="<?= htmlspecialchars(t('contact_name_ph'), ENT_QUOTES) ?>"/>
                <div class="cnt-err" id="cntNameErr"><?= htmlspecialchars(t('contact_name_err'), ENT_QUOTES) ?></div>
              </div>
              <div class="c-field">
                <label for="cCode"><?= htmlspecialchars(t('contact_code_label'), ENT_QUOTES) ?></label>
                <input type="text" class="cnt-input" id="cCode" placeholder="<?= htmlspecialchars(t('contact_code_ph'), ENT_QUOTES) ?>"/>
                <div class="cnt-err" id="cntCodeErr"><?= htmlspecialchars(t('contact_code_err'), ENT_QUOTES) ?></div>
              </div>
            </div>

            <div class="c-field">
              <label for="cEmail"><?= htmlspecialchars(t('contact_email_label'), ENT_QUOTES) ?></label>
              <input type="email" class="cnt-input" id="cEmail" placeholder="<?= htmlspecialchars(t('contact_email_ph'), ENT_QUOTES) ?>"/>
              <div class="cnt-err" id="cntEmailErr"><?= htmlspecialchars(t('contact_email_err'), ENT_QUOTES) ?></div>
            </div>

            <div class="c-field">
              <label><?= htmlspecialchars(t('contact_topic_label'), ENT_QUOTES) ?></label>
              <div class="cnt-chip-group">
                <span class="cnt-chip active" data-val="<?= htmlspecialchars(t('contact_topic_ui'), ENT_QUOTES) ?>"><?= htmlspecialchars(t('contact_topic_ui'), ENT_QUOTES) ?></span>
                <span class="cnt-chip" data-val="<?= htmlspecialchars(t('contact_topic_content'), ENT_QUOTES) ?>"><?= htmlspecialchars(t('contact_topic_content'), ENT_QUOTES) ?></span>
                <span class="cnt-chip" data-val="<?= htmlspecialchars(t('contact_topic_perf'), ENT_QUOTES) ?>"><?= htmlspecialchars(t('contact_topic_perf'), ENT_QUOTES) ?></span>
                <span class="cnt-chip" data-val="<?= htmlspecialchars(t('contact_topic_support'), ENT_QUOTES) ?>"><?= htmlspecialchars(t('contact_topic_support'), ENT_QUOTES) ?></span>
                <span class="cnt-chip" data-val="<?= htmlspecialchars(t('contact_topic_suggest'), ENT_QUOTES) ?>"><?= htmlspecialchars(t('contact_topic_suggest'), ENT_QUOTES) ?></span>
                <span class="cnt-chip" data-val="<?= htmlspecialchars(t('contact_topic_other'), ENT_QUOTES) ?>"><?= htmlspecialchars(t('contact_topic_other'), ENT_QUOTES) ?></span>
              </div>
            </div>

            <div class="c-field">
              <label for="cMsg"><?= htmlspecialchars(t('contact_msg_label'), ENT_QUOTES) ?></label>
              <textarea class="cnt-input cnt-textarea" id="cMsg" rows="5" placeholder="<?= htmlspecialchars(t('contact_msg_ph'), ENT_QUOTES) ?>"></textarea>
              <div class="cnt-err" id="cntMsgErr"><?= htmlspecialchars(t('contact_msg_err'), ENT_QUOTES) ?></div>
            </div>

            <button class="btn btn-primary contact-submit cnt-submit-btn" id="cntSubmitBtn" type="button">
              <i class="fas fa-paper-plane"></i> <?= htmlspecialchars(t('contact_submit_btn'), ENT_QUOTES) ?> <?= htmlspecialchars(t('home_cta_arrow'), ENT_QUOTES) ?>
            </button>

            <div class="c-toast" id="cToast"></div>

          </div>
        </div>

      </div>
    </div>

    <!-- Success overlay -->
    <div id="cntSuccessOverlay" class="cnt-overlay">
      <div class="cnt-success-box">
        <div class="cnt-success-icon"><i class="fas fa-check" style="color:#fff"></i></div>
        <h4 style="font-weight:800;margin-bottom:.5rem;color:#fff"><?= htmlspecialchars(t('contact_thanks_title'), ENT_QUOTES) ?></h4>
        <p style="color:rgba(255,255,255,0.65);font-size:.9rem;margin-bottom:1.5rem"><?= htmlspecialchars(t('contact_thanks_msg'), ENT_QUOTES) ?></p>
        <button class="btn btn-primary" onclick="document.getElementById('cntSuccessOverlay').classList.remove('show')" style="padding:10px 28px"><?= htmlspecialchars(t('contact_close_btn'), ENT_QUOTES) ?></button>
      </div>
    </div>

  </div>

  <!-- ===== VIEW: DASHBOARD ===== -->
  <?php if ($isLoggedIn): ?>
  <div class="view" id="viewDashboard">
    <div class="dashboard-layout">

      <!-- SIDEBAR -->
      <aside class="sidebar">
        <div class="sidebar-logo">
          <span class="brand-icon">🎓</span>
          <span><?= htmlspecialchars(t('sidebar_brand_short'), ENT_QUOTES) ?></span>
        </div>
        <nav class="sidebar-nav">
          <a class="sidebar-link active" href="#" data-dash="content">
            <svg viewBox="0 0 24 24" fill="none"><path d="M4 6H20M4 12H20M4 18H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <span><?= htmlspecialchars(t('dash_content'), ENT_QUOTES) ?></span>
          </a>
          <a class="sidebar-link" href="#" data-dash="schedule">
            <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 2V6M8 2V6M3 10H21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span><?= htmlspecialchars(t('dash_schedule'), ENT_QUOTES) ?></span>
          </a>
          <a class="sidebar-link" href="#" data-dash="exams">
            <svg viewBox="0 0 24 24" fill="none"><path d="M7 3.5H15L19.5 8V20.5H7C5.9 20.5 5 19.6 5 18.5V5.5C5 4.4 5.9 3.5 7 3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M15 3.5V8H19.5M8 12H16M8 15.5H13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span><?= htmlspecialchars(t('dash_exams_sched'), ENT_QUOTES) ?></span>
          </a>
          <a class="sidebar-link" href="#" data-dash="exam_system">
            <svg viewBox="0 0 24 24" fill="none"><path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V5C3 3.9 3.9 3 5 3H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <span><?= htmlspecialchars(t('dash_online_exams'), ENT_QUOTES) ?></span>
          </a>
          <?php if ($isAdmin): ?>
          <a class="sidebar-link" href="#" data-dash="pending">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8"/><path d="M12 6V12L16 14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span><?= htmlspecialchars(t('dash_pending'), ENT_QUOTES) ?></span>
          </a>
          <?php endif; ?>
          <a class="sidebar-link" href="#" data-dash="profile">
            <svg viewBox="0 0 24 24" fill="none"><path d="M20 21V19C20 17.9 19.1 17 18 17H6C4.9 17 4 17.9 4 19V21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 13C14.2 13 16 11.2 16 9C16 6.8 14.2 5 12 5C9.8 5 8 6.8 8 9C8 11.2 9.8 13 12 13Z" stroke="currentColor" stroke-width="1.8"/></svg>
            <span><?= htmlspecialchars(t('dash_profile'), ENT_QUOTES) ?></span>
          </a>
        </nav>
        <div class="sidebar-footer">
          <div class="sidebar-user">
            <div class="user-avatar"><?= htmlspecialchars($initials, ENT_QUOTES) ?></div>
            <div class="user-info">
              <span class="user-name"><?= htmlspecialchars(explode(' ', $userName)[0] ?? $userName, ENT_QUOTES) ?></span>
              <span class="user-role"><?= $roleLabel ?></span>
            </div>
          </div>
          
        </div>
      </aside>

      <!-- MAIN CONTENT -->
      <main class="dash-main">

        <!-- المحتوى التعليمي -->
        <div class="dash-panel active" id="panelContent">
          <div class="dash-header">
            <div>
              <h2><?= htmlspecialchars(t('dash_content'), ENT_QUOTES) ?></h2>
              <p>
                <?= $gradeLabel ?> &nbsp;|&nbsp;
                <?php if ($isAdmin): ?>
                  <span class="grade-switcher-label"><?= htmlspecialchars(t('dash_view_grade'), ENT_QUOTES) ?>
                    <?= renderCustomDropdown('adminGradeSelect', [
                        '1' => t('grade_1'),
                        '2' => t('grade_2'),
                        '3' => t('grade_3'),
                        '4' => t('grade_4')
                    ], (string)$userGrade) ?>
                  </span>
                <?php endif; ?>
              </p>
            </div>
            <button class="refresh-btn" id="refreshBtn">
              <svg viewBox="0 0 24 24" fill="none"><path d="M1 4V10H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.51 15C4.26 17.1 5.77 18.9 7.73 20.1C9.7 21.2 12 21.6 14.2 21.1C16.4 20.6 18.4 19.3 19.8 17.4C21.1 15.5 21.7 13.2 21.4 10.9C21.1 8.6 19.9 6.5 18.2 5C16.4 3.5 14.1 2.7 11.8 2.8C9.5 2.8 7.2 3.8 5.6 5.5L1 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <?= htmlspecialchars(t('dash_refresh_btn'), ENT_QUOTES) ?>
            </button>
          </div>

          <!-- اختيار الترم -->
          <div class="section-filter" id="semesterFilter" style="margin-bottom:18px;">
            <label style="font-size:13px;color:rgba(255,255,255,0.7);font-weight:700;margin-left:8px;"><?= htmlspecialchars(t('semester_label'), ENT_QUOTES) ?></label>
            <?= renderCustomDropdown('semesterSelect', [
                '1' => t('semester_1'),
                '2' => t('semester_2')
            ], '1') ?>
          </div>

          <!-- المواد (تُحمَّل عبر AJAX) -->
          <div class="subjects-bar" id="subjectsBar">
            <div class="loading-subjects"><?= htmlspecialchars(t('file_loading_subjects'), ENT_QUOTES) ?></div>
          </div>

          <!-- أزرار النوع -->
          <div class="content-btns" id="contentBtns" style="display:none;">
            <button class="content-btn active" data-content="lecture">
              <svg viewBox="0 0 24 24" fill="none"><path d="M4.5 7.5H14.5C15.6 7.5 16.5 8.4 16.5 9.5V14.5C16.5 15.6 15.6 16.5 14.5 16.5H4.5C3.4 16.5 2.5 15.6 2.5 14.5V9.5C2.5 8.4 3.4 7.5 4.5 7.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M16.5 10L21.5 7.5V16.5L16.5 14" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
              <?= htmlspecialchars(t('content_lectures'), ENT_QUOTES) ?>
            </button>
            <button class="content-btn" data-content="task">
              <svg viewBox="0 0 24 24" fill="none"><path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V5C3 3.9 3.9 3 5 3H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              <?= htmlspecialchars(t('content_tasks'), ENT_QUOTES) ?>
            </button>
            <button class="content-btn" data-content="assignment">
              <svg viewBox="0 0 24 24" fill="none"><path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V5C3 3.9 3.9 3 5 3H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              <?= htmlspecialchars(t('content_assign'), ENT_QUOTES) ?>
            </button>
            <button class="content-btn" data-content="section">
              <svg viewBox="0 0 24 24" fill="none"><path d="M16 11.5C17.7 11.5 19 10.1 19 8.5C19 6.9 17.7 5.5 16 5.5C14.3 5.5 13 6.9 13 8.5C13 10.1 14.3 11.5 16 11.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M8.5 11.5C10.2 11.5 11.5 10.1 11.5 8.5C11.5 6.9 10.2 5.5 8.5 5.5C6.8 5.5 5.5 6.9 5.5 8.5C5.5 10.1 6.8 11.5 8.5 11.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 18.5C4.2 15.9 6.1 14.5 8.5 14.5C10.9 14.5 12.8 15.9 13.5 18.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12.8 18.5C13.3 16.5 14.8 15.4 16.8 15.4C18.8 15.4 20.3 16.5 20.8 18.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
              <?= htmlspecialchars(t('content_sections'), ENT_QUOTES) ?>
            </button>
          </div>

          <!-- فلتر السكشن (يظهر فقط عند اختيار "السكاشن") -->
          <div class="section-filter" id="sectionFilter" style="display:none; margin-bottom:18px;">
            <label style="font-size:13px;color:rgba(255,255,255,0.7);font-weight:700;margin-left:8px;"><?= htmlspecialchars(t('schedule_pick_sec'), ENT_QUOTES) ?></label>
            <?= renderCustomDropdown('sectionSelect', [
                '' => t('section_all'),
                '1' => t('type_section') . ' 1',
                '2' => t('type_section') . ' 2',
                '3' => t('type_section') . ' 3',
                '4' => t('type_section') . ' 4'
            ], '') ?>
          </div>

          <!-- زر الرفع -->
          <div id="uploadArea" style="margin-bottom:18px; display:none;">
            <button class="upload-trigger-btn" id="uploadTriggerBtn">
              <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M21 15V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              <?= htmlspecialchars(t('upload_btn_generic'), ENT_QUOTES) ?>
            </button>
          </div>

          <!-- قائمة الملفات -->
          <div id="filesList">
            <div class="empty-state">
              <div class="empty-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M22 19C22 19.5 21.8 20 21.4 20.4C21 20.8 20.5 21 20 21H4C3.5 21 3 20.8 2.6 20.4C2.2 20 2 19.5 2 19V5C2 4.5 2.2 4 2.6 3.6C3 3.2 3.5 3 4 3H9L11 6H20C20.5 6 21 6.2 21.4 6.6C21.8 7 22 7.5 22 8V19Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
              <h3><?= htmlspecialchars(t('file_select_subject_title'), ENT_QUOTES) ?></h3>
              <p><?= htmlspecialchars(t('file_select_subject_sub'), ENT_QUOTES) ?></p>
            </div>
          </div>
        </div>

        <!-- الجدول الدراسي -->
        <div class="dash-panel" id="panelSchedule">
          <div class="dash-header">
            <div>
              <h2><?= htmlspecialchars(t('dash_schedule'), ENT_QUOTES) ?></h2>
              <p><?= htmlspecialchars(t('schedule_subtitle'), ENT_QUOTES) ?> &ndash; <?= $gradeLabel ?></p>
            </div>
            <?php if ($isAdmin): ?>
            <button class="upload-trigger-btn" id="scheduleUploadBtn" style="white-space:nowrap;"><?= htmlspecialchars(t('schedule_upload_btn'), ENT_QUOTES) ?></button>
            <?php endif; ?>
          </div>
          <div class="schedule-filter">
            <?php if ($isAdmin): ?>
            <div class="filter-group">
              <label class="filter-label"><?= htmlspecialchars(t('filter_grade_label'), ENT_QUOTES) ?></label>
              <?= renderCustomDropdown('schedGradeSelect', [
                  '1' => t('grade_1'),
                  '2' => t('grade_2'),
                  '3' => t('grade_3'),
                  '4' => t('grade_4')
              ], (string)$userGrade) ?>
            </div>
            <?php endif; ?>
            <div class="filter-group">
              <label class="filter-label"><?= htmlspecialchars(t('schedule_pick_sec'), ENT_QUOTES) ?></label>
              <?= renderCustomDropdown('schedSectionSelect', [
                  '' => t('schedule_choose_sec'),
                  '1' => t('type_section') . ' 1',
                  '2' => t('type_section') . ' 2',
                  '3' => t('type_section') . ' 3',
                  '4' => t('type_section') . ' 4'
              ], '') ?>
            </div>
          </div>
          <div id="scheduleContent">
            <div class="empty-state"><div class="empty-icon"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 2V6M8 2V6M3 10H21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div><h3><?= htmlspecialchars(t('schedule_empty_title'), ENT_QUOTES) ?></h3><p><?= htmlspecialchars(t('schedule_empty_sub'), ENT_QUOTES) ?></p></div>
          </div>
        </div>

        <!-- جدول الامتحانات -->
        <div class="dash-panel" id="panelExams">
          <div class="dash-header">
            <div>
              <h2><?= htmlspecialchars(t('dash_exams_sched'), ENT_QUOTES) ?></h2>
              <p><?= htmlspecialchars(t('exam_sched_subtitle'), ENT_QUOTES) ?> &ndash; <?= $gradeLabel ?></p>
            </div>
            <?php if ($isAdmin): ?>
            <button class="upload-trigger-btn" id="examUploadBtn" style="white-space:nowrap;"><?= htmlspecialchars(t('exam_sched_upload'), ENT_QUOTES) ?></button>
            <?php endif; ?>
          </div>
          <?php if ($isAdmin): ?>
          <div class="schedule-filter">
            <div class="filter-group">
              <label class="filter-label"><?= htmlspecialchars(t('view_grade_label'), ENT_QUOTES) ?></label>
              <?= renderCustomDropdown('examGradeSelect', [
                  '1' => t('grade_1'),
                  '2' => t('grade_2'),
                  '3' => t('grade_3'),
                  '4' => t('grade_4')
              ], (string)$userGrade) ?>
            </div>
          </div>
          <?php endif; ?>
          <div id="examsContent">
            <div class="empty-state"><div class="empty-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3.5H15L19.5 8V20.5H7C5.9 20.5 5 19.6 5 18.5V5.5C5 4.4 5.9 3.5 7 3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M15 3.5V8H19.5M8 12H16M8 15.5H13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div><h3><?= htmlspecialchars(t('exam_sched_loading'), ENT_QUOTES) ?></h3><p><?= htmlspecialchars(t('exam_sched_auto'), ENT_QUOTES) ?></p></div>
          </div>
        </div>

        <!-- ===== بانل الامتحانات الإلكترونية ===== -->
        <div class="dash-panel" id="panelExamSystem">
          <div class="dash-header">
            <div>
              <h2><?= $isAdmin ? htmlspecialchars(t('online_exams_admin_title'), ENT_QUOTES) : htmlspecialchars(t('online_exams_title'), ENT_QUOTES) ?></h2>
              <p><?= $isAdmin ? htmlspecialchars(t('online_exams_admin_sub'), ENT_QUOTES) : htmlspecialchars(t('online_exams_sub'), ENT_QUOTES) ?></p>
            </div>
            <?php if ($isAdmin): ?>
            <button class="upload-trigger-btn" id="showCreateExamBtn" style="white-space:nowrap;"><?= htmlspecialchars(t('create_exam_btn'), ENT_QUOTES) ?></button>
            <?php endif; ?>
          </div>

          <?php if ($isAdmin): ?>
          <div class="schedule-filter">
            <div class="filter-group">
              <label class="filter-label"><?= htmlspecialchars(t('filter_grade_label'), ENT_QUOTES) ?></label>
              <?= renderCustomDropdown('examSysGradeSelect', [
                  '1' => t('grade_1'),
                  '2' => t('grade_2'),
                  '3' => t('grade_3'),
                  '4' => t('grade_4')
              ], (string)$userGrade) ?>
            </div>
          </div>

          <!-- نموذج إنشاء الامتحان -->
          <div id="createExamFormWrap" style="display:none; margin-bottom:24px;">
            <div class="exam-create-form">
              <div class="ecf-section-title"><?= htmlspecialchars(t('exam_basic_data'), ENT_QUOTES) ?></div>
              <div class="ecf-grid">
                <div class="ecf-field">
                  <label class="ecf-label"><?= htmlspecialchars(t('exam_grade_label'), ENT_QUOTES) ?></label>
                  <select class="ecf-select" id="ecfGrade">
                    <option value="1"><?= htmlspecialchars(t('grade_1'), ENT_QUOTES) ?></option>
                    <option value="2"><?= htmlspecialchars(t('grade_2'), ENT_QUOTES) ?></option>
                    <option value="3"><?= htmlspecialchars(t('grade_3'), ENT_QUOTES) ?></option>
                    <option value="4"><?= htmlspecialchars(t('grade_4'), ENT_QUOTES) ?></option>
                  </select>
                </div>
                <div class="ecf-field">
                  <label class="ecf-label"><?= htmlspecialchars(t('exam_subject_label'), ENT_QUOTES) ?></label>
                  <select class="ecf-select" id="ecfSubject"><option value=""><?= htmlspecialchars(t('exam_choose_subject'), ENT_QUOTES) ?></option></select>
                </div>
                <div class="ecf-field full">
                  <label class="ecf-label"><?= htmlspecialchars(t('exam_title_label'), ENT_QUOTES) ?></label>
                  <input type="text" class="ecf-input" id="ecfTitle" placeholder="<?= htmlspecialchars(t('exam_title_ph'), ENT_QUOTES) ?>" />
                </div>
                <div class="ecf-field full">
                  <label class="ecf-label"><?= htmlspecialchars(t('exam_desc_label'), ENT_QUOTES) ?></label>
                  <textarea class="ecf-textarea" id="ecfDesc" placeholder="<?= htmlspecialchars(t('exam_desc_ph'), ENT_QUOTES) ?>"></textarea>
                </div>
                <div class="ecf-field">
                  <label class="ecf-label"><?= htmlspecialchars(t('exam_duration_label'), ENT_QUOTES) ?></label>
                  <input type="number" class="ecf-input" id="ecfDuration" min="5" max="300" value="60" />
                </div>
                <div class="ecf-field">
                  <label class="ecf-label"><?= htmlspecialchars(t('exam_total_grade_label'), ENT_QUOTES) ?></label>
                  <input type="number" class="ecf-input" id="ecfTotalMarks" min="1" value="100" />
                </div>
                <div class="ecf-field">
                  <label class="ecf-label"><?= htmlspecialchars(t('exam_pass_grade_label'), ENT_QUOTES) ?></label>
                  <input type="number" class="ecf-input" id="ecfPassMarks" min="1" value="50" />
                </div>
              </div>
              <div class="ecf-section-title" style="margin-top:8px;"><?= htmlspecialchars(t('exam_questions_title'), ENT_QUOTES) ?></div>
              <div class="ecf-questions-list" id="ecfQuestionsList"></div>
              <button type="button" class="ecf-add-q-btn" id="ecfAddQuestionBtn">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <?= htmlspecialchars(t('exam_add_question_btn'), ENT_QUOTES) ?>
              </button>
              <div class="ecf-msg" id="ecfMsg"></div>
              <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="ecf-submit-btn" id="ecfSubmitBtn" style="flex:1;">
                  <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M17 8L12 3L7 8M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  <?= htmlspecialchars(t('exam_upload_btn'), ENT_QUOTES) ?>
                </button>
                <button type="button" class="btn btn-outline" id="ecfCancelBtn" style="padding:14px 20px;"><?= htmlspecialchars(t('exam_cancel_btn'), ENT_QUOTES) ?></button>
              </div>
            </div>
          </div>
          <div class="content-btns" style="margin-bottom:20px;">
            <button class="content-btn active" data-exam-tab="exams_list"><?= htmlspecialchars(t('exam_tab_list'), ENT_QUOTES) ?></button>
            <button class="content-btn" data-exam-tab="submissions"><?= htmlspecialchars(t('exam_tab_submissions'), ENT_QUOTES) ?></button>
          </div>
          <?php else: ?>
          <div class="content-btns" style="margin-bottom:20px;">
            <button class="content-btn active" data-exam-tab="exams_list"><?= htmlspecialchars(t('exam_tab_available'), ENT_QUOTES) ?></button>
            <button class="content-btn" data-exam-tab="my_results"><?= htmlspecialchars(t('exam_tab_results'), ENT_QUOTES) ?></button>
          </div>
          <?php endif; ?>

          <div id="examSystemContent">
            <div class="loading-subjects"><?= htmlspecialchars(t('exam_loading'), ENT_QUOTES) ?></div>
          </div>
        </div>

        <?php if ($isAdmin): ?>
        <!-- ملفات قيد المراجعة (أدمن فقط) -->
        <div class="dash-panel" id="panelPending">
          <div class="dash-header">
            <div>
              <h2><?= htmlspecialchars(t('dash_pending'), ENT_QUOTES) ?></h2>
              <p><?= htmlspecialchars(t('pending_sub'), ENT_QUOTES) ?></p>
            </div>
            <button class="refresh-btn" id="refreshPendingBtn"><?= htmlspecialchars(t('pending_refresh'), ENT_QUOTES) ?></button>
          </div>
          <div id="pendingList">
            <div class="loading-subjects"><?= htmlspecialchars(t('pending_loading'), ENT_QUOTES) ?></div>
          </div>
        </div>
        <?php endif; ?>

        <!-- الملف الشخصي -->
        <div class="dash-panel" id="panelProfile">
          <div class="dash-header"><div><h2><?= htmlspecialchars(t('dash_profile'), ENT_QUOTES) ?></h2><p><?= htmlspecialchars(t('profile_static_sub'), ENT_QUOTES) ?></p></div></div>
          <div class="profile-wrap">
            <div class="profile-card">
              <div class="profile-avatar-wrap">
                <div class="profile-avatar"><?= htmlspecialchars($initials, ENT_QUOTES) ?></div>
                <div class="profile-role-badge"><?= $roleLabel ?></div>
              </div>
              <h3 class="profile-name"><?= $userName ?></h3>
              <p class="profile-dept"><?= htmlspecialchars(t('profile_static_dept'), ENT_QUOTES) ?></p>
            </div>
            <div class="profile-info-card">
              <h4 class="info-title"><?= htmlspecialchars(t('profile_academic_data'), ENT_QUOTES) ?></h4>
              <div class="info-list">
                <div class="info-row"><span class="info-lbl"><?= htmlspecialchars(t('profile_code'), ENT_QUOTES) ?></span><span class="info-val"><?= $userCode ?></span></div>
                <div class="info-row"><span class="info-lbl"><?= htmlspecialchars(t('profile_full_name'), ENT_QUOTES) ?></span><span class="info-val"><?= $userName ?></span></div>
                <div class="info-row"><span class="info-lbl"><?= htmlspecialchars(t('profile_grade'), ENT_QUOTES) ?></span><span class="info-val"><?= $gradeLabel ?></span></div>
                <div class="info-row"><span class="info-lbl">GPA</span><span class="info-val"><?= $gpaLabel ?></span></div>
                <div class="info-row"><span class="info-lbl">CGPA</span><span class="info-val"><?= $cgpaLabel ?></span></div>
                <div class="info-row"><span class="info-lbl"><?= htmlspecialchars(t('profile_role'), ENT_QUOTES) ?></span><span class="info-val"><?= $roleLabel ?></span></div>
                <div class="info-row"><span class="info-lbl"><?= htmlspecialchars(t('profile_section_label'), ENT_QUOTES) ?></span><span class="info-val"><?= htmlspecialchars(t('profile_dept_default'), ENT_QUOTES) ?></span></div>
                <div class="info-row"><span class="info-lbl"><?= htmlspecialchars(t('profile_uni_label'), ENT_QUOTES) ?></span><span class="info-val"><?= htmlspecialchars(t('profile_uni_value'), ENT_QUOTES) ?></span></div>
              </div>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>
  <?php else: ?>
  <!-- Dashboard placeholder for guests -->
  <div class="view" id="viewDashboard"></div>
  <?php endif; ?>

  <!-- Session data for JS -->
  <script>
    window.APP = {
      isLoggedIn: <?= $isLoggedIn ? 'true' : 'false' ?>,
      isAdmin:    <?= $isAdmin    ? 'true' : 'false' ?>,
      userGrade:  <?= $userGrade ?>,
      userName:   <?= json_encode($userName) ?>,
      userCode:   <?= json_encode($userCode) ?>,
      roleLabel:  <?= json_encode($roleLabel) ?>
    };

    // نصوص المحتوى التعليمي والملف الشخصي، تُستخدم من main.js
    window.LANG = {
      profile: {
        faculty: <?= json_encode(t('profile_faculty')) ?>,
        academicData: <?= json_encode(t('profile_academic_data')) ?>,
        code: <?= json_encode(t('profile_code')) ?>,
        fullName: <?= json_encode(t('profile_full_name')) ?>,
        grade: <?= json_encode(t('profile_grade')) ?>,
        dept: <?= json_encode(t('profile_dept')) ?>,
        role: <?= json_encode(t('profile_role')) ?>,
        email: <?= json_encode(t('profile_email')) ?>,
        personalEmail: <?= json_encode(t('profile_personal_email')) ?>,
        degree: <?= json_encode(t('profile_degree')) ?>,
        joinYear: <?= json_encode(t('profile_join_year')) ?>,
        experience: <?= json_encode(t('profile_experience')) ?>,
        deptDefault: <?= json_encode(t('profile_dept_default')) ?>,
        studentRoleFallback: <?= json_encode(t('srv_role_student')) ?>,
        grades: {
          1: <?= json_encode(t('grade_1')) ?>, 2: <?= json_encode(t('grade_2')) ?>,
          3: <?= json_encode(t('grade_3')) ?>, 4: <?= json_encode(t('grade_4')) ?>
        }
      },
      contact: {
        topicNone: <?= json_encode(t('contact_topic_none')) ?>,
        sending: <?= json_encode(t('contact_sending')) ?>,
        error: <?= json_encode(t('contact_error')) ?>,
        connError: <?= json_encode(t('contact_conn_error')) ?>,
        submitBtn: <?= json_encode(t('contact_submit_btn')) ?>
      },
      upload: {
        chooseFileErr: <?= json_encode(t('upload_choose_file_err')) ?>,
        noSubjectErr: <?= json_encode(t('upload_no_subject_err')) ?>,
        uploading: <?= json_encode(t('upload_uploading')) ?>,
        connError: <?= json_encode(t('upload_conn_error')) ?>,
        submitBtn: <?= json_encode(t('upload_submit_btn')) ?>,
        previewTitle: <?= json_encode(t('upload_preview_title')) ?>,
        noPreview: <?= json_encode(t('upload_no_preview')) ?>,
        downloadFile: <?= json_encode(t('upload_download_file')) ?>,
        videoUnsupported: <?= json_encode(t('upload_video_unsupported')) ?>
      },
      files: {
        loadingSubjects: <?= json_encode(t('file_loading_subjects')) ?>,
        noSubjectsTerm: <?= json_encode(t('file_no_subjects_term')) ?>,
        selectSubjectTitle: <?= json_encode(t('file_select_subject_title')) ?>,
        selectSubjectSub: <?= json_encode(t('file_select_subject_sub')) ?>,
        loading: <?= json_encode(t('file_loading')) ?>,
        noFilesTitle: <?= json_encode(t('file_no_files_title')) ?>,
        noFilesSub: <?= json_encode(t('file_no_files_sub')) ?>,
        defaultName: <?= json_encode(t('file_default_name')) ?>,
        viewBtn: <?= json_encode(t('file_view_btn')) ?>,
        downloadBtn: <?= json_encode(t('file_download_btn')) ?>,
        deleteBtn: <?= json_encode(t('file_delete_btn')) ?>,
        approveBtn: <?= json_encode(t('file_approve_btn')) ?>,
        rejectBtn: <?= json_encode(t('file_reject_btn')) ?>,
        uploadedBy: <?= json_encode(t('file_uploaded_by')) ?>,
        sectionLabel: <?= json_encode(t('file_section_label')) ?>,
        selectSubjectFirst: <?= json_encode(t('file_select_subject_first')) ?>,
        subjectLabel: <?= json_encode(t('file_subject_label')) ?>,
        typeLabelText: <?= json_encode(t('file_type_label')) ?>,
        status: {
          approved: <?= json_encode(t('status_approved')) ?>,
          pending: <?= json_encode(t('status_pending')) ?>,
          rejected: <?= json_encode(t('status_rejected')) ?>
        },
        type: {
          lecture: <?= json_encode(t('type_lecture')) ?>,
          task: <?= json_encode(t('type_task')) ?>,
          assignment: <?= json_encode(t('type_assignment')) ?>,
          section: <?= json_encode(t('type_section')) ?>,
          schedule: <?= json_encode(t('type_schedule')) ?>,
          exam_schedule: <?= json_encode(t('type_exam_schedule')) ?>
        }
      },
      locale: <?= json_encode($currentLang === 'ar' ? 'ar-EG' : 'en-US') ?>,
      ctaArrow: <?= json_encode(t('home_cta_arrow')) ?>,
      exams: {
        schedEmptyTitle: <?= json_encode(t('exam_sched_empty_title')) ?>,
        schedEmptySub: <?= json_encode(t('exam_sched_empty_sub')) ?>,
        schedCardType: <?= json_encode(t('exam_sched_card_type')) ?>,
        schedLoadErr: <?= json_encode(t('exam_sched_load_err')) ?>,
        pendingEmptyTitle: <?= json_encode(t('pending_empty_title')) ?>,
        pendingEmptySub: <?= json_encode(t('pending_empty_sub')) ?>,
        pendingLoadErr: <?= json_encode(t('pending_load_err')) ?>,
        loadErr: <?= json_encode(t('exam_load_err')) ?>,
        noneForGrade: <?= json_encode(t('exam_none_for_grade')) ?>,
        noneAvailable: <?= json_encode(t('exam_none_available')) ?>,
        clickWord: <?= json_encode(t('exam_click')) ?>,
        clickToAdd: <?= json_encode(t('exam_click_to_add')) ?>,
        willAppear: <?= json_encode(t('exam_will_appear')) ?>,
        connError: <?= json_encode(t('exam_conn_error')) ?>,
        statusActive: <?= json_encode(t('exam_status_active')) ?>,
        statusInactive: <?= json_encode(t('exam_status_inactive')) ?>,
        disableBtn: <?= json_encode(t('exam_disable_btn')) ?>,
        enableBtn: <?= json_encode(t('exam_enable_btn')) ?>,
        statusBanned: <?= json_encode(t('exam_status_banned')) ?>,
        bannedBtn: <?= json_encode(t('exam_banned_btn')) ?>,
        statusDone: <?= json_encode(t('exam_status_done')) ?>,
        submittedBtn: <?= json_encode(t('exam_submitted_btn')) ?>,
        statusAvailable: <?= json_encode(t('exam_status_available')) ?>,
        deleteConfirm: <?= json_encode(t('exam_delete_confirm')) ?>,
        deleteErr: <?= json_encode(t('exam_delete_err')) ?>,
        connErrShort: <?= json_encode(t('exam_conn_err_short')) ?>,
        genericErr: <?= json_encode(t('exam_generic_err')) ?>,
        submissionsLoading: <?= json_encode(t('submissions_loading')) ?>,
        submissionsEmptyTitle: <?= json_encode(t('submissions_empty_title')) ?>,
        submissionsEmptySub: <?= json_encode(t('submissions_empty_sub')) ?>,
        submissionsLoadErr: <?= json_encode(t('submissions_load_err')) ?>,
        submissionsCheat: <?= json_encode(t('submissions_cheat')) ?>,
        submissionsNotes: <?= json_encode(t('submissions_notes')) ?>,
        submissionsReviewed: <?= json_encode(t('submissions_reviewed')) ?>,
        submissionsEnterGrade: <?= json_encode(t('submissions_enter_grade')) ?>,
        submissionsSaveErr: <?= json_encode(t('submissions_save_err')) ?>,
        submissionsAnswersErr: <?= json_encode(t('submissions_answers_err')) ?>,
        submissionsNoAnswer: <?= json_encode(t('submissions_no_answer')) ?>,
        submissionsNoAnswerWarn: <?= json_encode(t('submissions_no_answer_warn')) ?>,
        resultsLoading: <?= json_encode(t('results_loading')) ?>,
        resultsEmptyTitle: <?= json_encode(t('results_empty_title')) ?>,
        resultsEmptySub: <?= json_encode(t('results_empty_sub')) ?>,
        resultsPass: <?= json_encode(t('results_pass')) ?>,
        resultsFail: <?= json_encode(t('results_fail')) ?>,
        resultsPending: <?= json_encode(t('results_pending')) ?>,
        formLoadingOpt: <?= json_encode(t('exam_form_loading_opt')) ?>,
        formChooseSubj: <?= json_encode(t('exam_form_choose_subj')) ?>,
        formLoadErrOpt: <?= json_encode(t('exam_form_load_err_opt')) ?>,
        formGradeCol: <?= json_encode(t('exam_form_grade_col')) ?>,
        formQuestionPh: <?= json_encode(t('exam_form_question_ph')) ?>,
        formChoiceA: <?= json_encode(t('exam_form_choice_a')) ?>,
        formChoiceB: <?= json_encode(t('exam_form_choice_b')) ?>,
        formChoiceC: <?= json_encode(t('exam_form_choice_c')) ?>,
        formChoiceD: <?= json_encode(t('exam_form_choice_d')) ?>,
        formQuestionNum: <?= json_encode(t('exam_form_question_num')) ?>,
        formErrRequired: <?= json_encode(t('exam_form_err_required')) ?>,
        formErrQtext: <?= json_encode(t('exam_form_err_qtext')) ?>,
        formErrMcq: <?= json_encode(t('exam_form_err_mcq')) ?>,
        formErrMin1: <?= json_encode(t('exam_form_err_min1')) ?>,
        formUploading: <?= json_encode(t('exam_form_uploading')) ?>,
        formSuccess: <?= json_encode(t('exam_form_success')) ?>,
        formUploadErr: <?= json_encode(t('exam_form_upload_err')) ?>,
        formConnErr: <?= json_encode(t('exam_form_conn_err')) ?>,
        createExamBtnText: <?= json_encode(t('create_exam_btn')) ?>,
        studentsSubmitted: <?= json_encode(t('exam_students_submitted')) ?>,
        studentUnit: <?= json_encode(t('exam_student_unit')) ?>,
        durationIcon: <?= json_encode(t('exam_duration_icon')) ?>,
        minuteUnit: <?= json_encode(t('exam_minute_unit')) ?>,
        questionsIcon: <?= json_encode(t('exam_questions_icon')) ?>,
        questionUnit: <?= json_encode(t('exam_question_unit')) ?>,
        totalGradeIcon: <?= json_encode(t('exam_total_grade_icon')) ?>,
        gradeUnit: <?= json_encode(t('exam_grade_unit')) ?>,
        passIcon: <?= json_encode(t('exam_pass_icon')) ?>,
        startBtn: <?= json_encode(t('exam_start_btn')) ?>,
        deleteIconBtn: <?= json_encode(t('exam_delete_icon_btn')) ?>,
        cheatPrefix: <?= json_encode(t('exam_cheat_prefix')) ?>,
        givenGrade: <?= json_encode(t('exam_given_grade')) ?>,
        gradeFrom: <?= json_encode(t('exam_grade_from')) ?>,
        saveBtn: <?= json_encode(t('exam_save_btn')) ?>,
        viewAnswersBtn: <?= json_encode(t('exam_view_answers_btn')) ?>,
        codePrefix: <?= json_encode(t('exam_code_prefix')) ?>,
        enterGradeFirst: <?= json_encode(t('exam_enter_grade_first')) ?>,
        cheatLogTitle: <?= json_encode(t('exam_cheat_log_title')) ?>,
        qPrefix: <?= json_encode(t('exam_q_prefix')) ?>,
        backToList: <?= json_encode(t('exam_back_to_list')) ?>,
        answersOf: <?= json_encode(t('exam_answers_of')) ?>,
        startedAt: <?= json_encode(t('exam_started_at')) ?>,
        submittedAtLabel: <?= json_encode(t('exam_submitted_at_label')) ?>,
        resultOf: <?= json_encode(t('exam_result_of')) ?>,
        typeMcq: <?= json_encode(t('exam_type_mcq')) ?>,
        typeEssay: <?= json_encode(t('exam_type_essay')) ?>,
        marksPh: <?= json_encode(t('exam_marks_ph')) ?>,
        removeQBtn: <?= json_encode(t('exam_remove_q_btn')) ?>,
        questionTextLabel: <?= json_encode(t('exam_question_text_label')) ?>,
        correctAnswerLabel: <?= json_encode(t('exam_correct_answer_label')) ?>,
        letterA: <?= json_encode(t('exam_letter_a')) ?>,
        letterB: <?= json_encode(t('exam_letter_b')) ?>,
        letterC: <?= json_encode(t('exam_letter_c')) ?>,
        letterD: <?= json_encode(t('exam_letter_d')) ?>,
        examUploadBtnText: <?= json_encode(t('exam_upload_btn')) ?>,
        schedNoSectionTitle: <?= json_encode(t('sched_no_section_title')) ?>,
        schedNoSectionSub: <?= json_encode(t('sched_no_section_sub')) ?>,
        schedEmptyTitle2: <?= json_encode(t('sched_empty_title')) ?>,
        schedEmptySub2: <?= json_encode(t('sched_empty_sub')) ?>,
        schedCardType2: <?= json_encode(t('sched_card_type')) ?>,
        schedLoadErr2: <?= json_encode(t('sched_load_err')) ?>
      }
    };
  </script>
  <!-- ===== UPLOAD MODAL ===== -->
  <div class="platform-modal" id="uploadModal">
    <div class="platform-modal-box">
      <div class="platform-modal-header">
        <h3 class="platform-modal-title">
          <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M21 15V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          <?= htmlspecialchars(t('upload_modal_title'), ENT_QUOTES) ?>
        </h3>
        <button class="platform-modal-close" id="modalCloseBtn">✕</button>
      </div>
      <form id="modalUploadForm" class="platform-modal-form">
        <input type="hidden" id="modalType" value="" />
        <input type="hidden" id="modalSubject" value="" />

        <?php if ($isAdmin): ?>
        <div id="modalAdminFields" class="modal-field-group">
          <div class="modal-field">
            <label class="modal-label"><?= htmlspecialchars(t('modal_grade_label'), ENT_QUOTES) ?></label>
            <?= renderCustomDropdown('modalGrade', [
                '1' => t('grade_1'),
                '2' => t('grade_2'),
                '3' => t('grade_3'),
                '4' => t('grade_4')
            ], (string)$userGrade) ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="modal-field" id="modalSectionField" style="display:none;">
          <label class="modal-label"><?= htmlspecialchars(t('modal_section_label'), ENT_QUOTES) ?></label>
          <?= renderCustomDropdown('modalSectionNumber', [
              '1' => t('type_section') . ' 1',
              '2' => t('type_section') . ' 2',
              '3' => t('type_section') . ' 3',
              '4' => t('type_section') . ' 4'
          ], '1') ?>
        </div>

        <div class="modal-field">
          <label class="modal-label"><?= htmlspecialchars(t('modal_choose_file_label'), ENT_QUOTES) ?></label>
          <label class="modal-file-label" for="modalFileInput">
            <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M21 15V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <span id="modalFileName"><?= htmlspecialchars(t('modal_choose_file_text'), ENT_QUOTES) ?></span>
          </label>
          <input type="file" id="modalFileInput" name="file" hidden required
                 onchange="document.getElementById('modalFileName').textContent = this.files[0]?.name || <?= json_encode(t('modal_choose_file_short')) ?>" />
        </div>

        <div class="modal-msg" id="modalUploadMsg"></div>

        <div class="modal-actions">
          <button type="submit" class="modal-submit-btn">
            <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <?= htmlspecialchars(t('modal_upload_btn'), ENT_QUOTES) ?>
          </button>
          <button type="button" class="modal-cancel-btn" id="modalCancelBtn"><?= htmlspecialchars(t('modal_cancel_btn'), ENT_QUOTES) ?></button>
        </div>
      </form>
    </div>
  </div>

  <!-- ===== PREVIEW MODAL ===== -->
  <div class="platform-modal" id="previewModal">
    <div class="platform-modal-box platform-modal-wide">
      <div class="platform-modal-header">
        <h3 class="platform-modal-title" id="previewTitle"><?= htmlspecialchars(t('upload_preview_title'), ENT_QUOTES) ?></h3>
        <button class="platform-modal-close" id="previewCloseBtn">✕</button>
      </div>
      <div class="preview-container" id="previewContainer"></div>
    </div>
  </div>

  <!-- ===== DELETE CONFIRM MODAL ===== -->
  <div class="platform-modal" id="deleteConfirmModal">
    <div class="platform-modal-box delete-confirm-box">
      <div class="delete-confirm-icon">
        <svg viewBox="0 0 24 24" fill="none" width="36" height="36">
          <path d="M3 6H21M19 6L18 20C18 21.1 17.1 22 16 22H8C6.9 22 6 21.1 6 20L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M8 6V4C8 2.9 8.9 2 10 2H14C15.1 2 16 2.9 16 4V6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <h3 class="delete-confirm-title"><?= htmlspecialchars(t('delete_confirm_title'), ENT_QUOTES) ?></h3>
      <p class="delete-confirm-msg"><?= t('delete_confirm_msg') ?></p>
      <div class="delete-confirm-actions">
        <button class="delete-confirm-btn" id="deleteConfirmYes">
          <svg viewBox="0 0 24 24" fill="none" width="16" height="16">
            <path d="M3 6H21M19 6L18 20C18 21.1 17.1 22 16 22H8C6.9 22 6 21.1 6 20L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 6V4C8 2.9 8.9 2 10 2H14C15.1 2 16 2.9 16 4V6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <?= htmlspecialchars(t('delete_confirm_yes'), ENT_QUOTES) ?>
        </button>
        <button class="delete-cancel-btn" id="deleteConfirmNo"><?= htmlspecialchars(t('delete_confirm_no'), ENT_QUOTES) ?></button>
      </div>
    </div>
  </div>

  <script src="assets/js/main.js"></script>
  <?php include $_SERVER['DOCUMENT_ROOT'] . '/partials/footer.php'; ?>
</body>
</html>