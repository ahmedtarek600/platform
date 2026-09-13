<?php
// =============================================
// login/index.php – صفحة تسجيل الدخول
// =============================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/lang.php';

// إذا كان مسجل دخول بالفعل، وجهه للرئيسية
if (!empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!doctype html>
<html lang="<?= $currentLang ?>" dir="<?= $currentDir ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars(t('login_title'), ENT_QUOTES) ?> | <?= htmlspecialchars(t('auth_brand_title'), ENT_QUOTES) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/login.css" />
  <style>
    .role-select{
      width:100%;
      padding:12px 14px;
      border-radius:10px;
      border:1px solid rgba(0,0,0,.15);
      font-family:'Cairo', sans-serif;
      font-size:14px;
      background:#fff;
      cursor:pointer;
      outline:none;
    }
    .role-select:focus{ border-color:#2f63ff; }
  </style>
</head>
<body>
  <div class="bg"></div>

  <main class="auth-wrap">
    <section class="auth-card">

      <!-- LEFT: FORM -->
      <div class="auth-left">
        <div class="header" style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
          <div>
            <h1><?= htmlspecialchars(t('login_title'), ENT_QUOTES) ?></h1>
            <p><?= htmlspecialchars(t('login_subtitle'), ENT_QUOTES) ?></p>
          </div>
          <?php
            $nextLang    = ($currentLang === 'ar') ? 'en' : 'ar';
            $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
          ?>
          <a class="lang-nav-btn" style="color:#333; flex-shrink:0;"
             href="/set_lang.php?lang=<?= $nextLang ?>&return=<?= urlencode($currentPath) ?>">
            🌐 <?= htmlspecialchars(t('lang_toggle'), ENT_QUOTES) ?>
          </a>
        </div>

        <form class="form" id="loginForm" novalidate>

          <div class="field">
            <label for="loginRole"><?= htmlspecialchars(t('login_role_label'), ENT_QUOTES) ?></label>
            <div class="input-wrap">
              <select id="loginRole" class="role-select">
                <option value="student" selected><?= htmlspecialchars(t('login_role_student'), ENT_QUOTES) ?></option>
                <option value="doctor"><?= htmlspecialchars(t('login_role_doctor'), ENT_QUOTES) ?></option>
              </select>
            </div>
          </div>

          <div class="field">
            <label for="uniCode" id="codeLabel"><?= htmlspecialchars(t('login_code_label'), ENT_QUOTES) ?></label>
            <div class="input-wrap">
              <input id="uniCode" name="code" type="text"
                placeholder="<?= htmlspecialchars(t('login_code_placeholder'), ENT_QUOTES) ?>"
                autocomplete="off" required />
            </div>
            <small class="hint" id="codeHint"><?= htmlspecialchars(t('login_code_hint'), ENT_QUOTES) ?></small>
            <small class="error" id="codeError"><?= htmlspecialchars(t('login_code_error'), ENT_QUOTES) ?></small>
          </div>

          <div class="field">
            <label for="password"><?= htmlspecialchars(t('login_password_label'), ENT_QUOTES) ?></label>
            <div class="input-wrap">
              <input id="password" name="password" type="password"
                placeholder="<?= htmlspecialchars(t('login_password_placeholder'), ENT_QUOTES) ?>"
                autocomplete="current-password" required />
              <button class="eye-btn" type="button" id="togglePass"><?= htmlspecialchars(t('login_show'), ENT_QUOTES) ?></button>
            </div>
            <small class="error" id="passError"><?= htmlspecialchars(t('login_password_error'), ENT_QUOTES) ?></small>
          </div>

          <div class="row">
            <label class="checkbox">
              <input type="checkbox" id="rememberMe" />
              <span><?= htmlspecialchars(t('login_remember'), ENT_QUOTES) ?></span>
            </label>
            <a class="link" href="../reset-password/index.php"><?= htmlspecialchars(t('login_forgot'), ENT_QUOTES) ?></a>
          </div>

          <button class="btn" type="submit" id="submitBtn"><?= htmlspecialchars(t('login_submit'), ENT_QUOTES) ?></button>

          <div class="divider"><span><?= htmlspecialchars(t('login_or'), ENT_QUOTES) ?></span></div>

          <p class="bottom">
            <?= htmlspecialchars(t('login_no_account'), ENT_QUOTES) ?> <a class="link" href="../register/index.php"><?= htmlspecialchars(t('login_create_link'), ENT_QUOTES) ?></a>
          </p>
          <p class="role-note"><?= htmlspecialchars(t('login_role_note'), ENT_QUOTES) ?></p>

          <div class="toast" id="toast" role="status" aria-live="polite"></div>
        </form>
      </div>

      <!-- RIGHT: INFO PANEL -->
      <div class="auth-right">
        <div class="brand">
          <div class="cap" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M12 3L1.5 8.2 12 13.4 22.5 8.2 12 3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
              <path d="M4.7 10.1V15.2C4.7 15.2 7.5 18 12 18C16.5 18 19.3 15.2 19.3 15.2V10.1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
              <path d="M22.5 8.2V14.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
          </div>
          <h2><?= htmlspecialchars(t('auth_brand_title'), ENT_QUOTES) ?></h2>
        </div>
        <h3><?= htmlspecialchars(t('login_welcome_back'), ENT_QUOTES) ?></h3>
        <p class="desc"><?= htmlspecialchars(t('login_welcome_desc'), ENT_QUOTES) ?></p>
        <ul class="features">
          <li><span class="ico"><svg viewBox="0 0 24 24" fill="none"><path d="M4.5 7.5H14.5C15.6 7.5 16.5 8.4 16.5 9.5V14.5C16.5 15.6 15.6 16.5 14.5 16.5H4.5C3.4 16.5 2.5 15.6 2.5 14.5V9.5C2.5 8.4 3.4 7.5 4.5 7.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M16.5 10L21.5 7.5V16.5L16.5 14" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span><?= htmlspecialchars(t('auth_features_lecture'), ENT_QUOTES) ?></li>
          <li><span class="ico"><svg viewBox="0 0 24 24" fill="none"><path d="M7 7H20M7 12H20M7 17H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M4 7H4.01M4 12H4.01M4 17H4.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg></span><?= htmlspecialchars(t('auth_features_tasks'), ENT_QUOTES) ?></li>
          <li><span class="ico"><svg viewBox="0 0 24 24" fill="none"><path d="M16 11.5C17.7 11.5 19 10.1 19 8.5C19 6.9 17.7 5.5 16 5.5C14.3 5.5 13 6.9 13 8.5C13 10.1 14.3 11.5 16 11.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M8.5 11.5C10.2 11.5 11.5 10.1 11.5 8.5C11.5 6.9 10.2 5.5 8.5 5.5C6.8 5.5 5.5 6.9 5.5 8.5C5.5 10.1 6.8 11.5 8.5 11.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 18.5C4.2 15.9 6.1 14.5 8.5 14.5C10.9 14.5 12.8 15.9 13.5 18.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12.8 18.5C13.3 16.5 14.8 15.4 16.8 15.4C18.8 15.4 20.3 16.5 20.8 18.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><?= htmlspecialchars(t('auth_features_section'), ENT_QUOTES) ?></li>
          <li><span class="ico"><svg viewBox="0 0 24 24" fill="none"><path d="M20 7L10 17L5 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span><?= htmlspecialchars(t('auth_features_task2'), ENT_QUOTES) ?></li>
          <li><span class="ico"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3.5H15L19.5 8V20.5H7C5.9 20.5 5 19.6 5 18.5V5.5C5 4.4 5.9 3.5 7 3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M15 3.5V8H19.5M8 12H16M8 15.5H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><?= htmlspecialchars(t('auth_features_exams'), ENT_QUOTES) ?></li>
        </ul>
      </div>

    </section>
  </main>

  <script>
    // كل النصوص اللي محتاجها login.js وقت التشغيل، حسب اللغة الحالية
    window.LANG = {
      showText:       <?= json_encode(t('login_show')) ?>,
      hideText:       <?= json_encode(t('login_hide')) ?>,
      submitText:     <?= json_encode(t('login_submit')) ?>,
      submittingText: <?= json_encode(t('login_submitting')) ?>,
      toastFill:      <?= json_encode(t('login_toast_fill')) ?>,
      toastSuccess:   <?= json_encode(t('login_toast_success')) ?>,
      toastError:     <?= json_encode(t('login_toast_error')) ?>,
      toastConnError: <?= json_encode(t('login_toast_conn_error')) ?>
    };

    // تبديل نص الحقل حسب نوع الحساب المختار (طالب / دكتور)
    // ملحوظة: اسم الحقل الفعلي المُرسل للسيرفر (code) لا يتغيّر أبداً،
    // فقط الشكل الظاهر للمستخدم — لا حاجة لتعديل login.php
    (function () {
      const roleSelect = document.getElementById('loginRole');
      const codeInput  = document.getElementById('uniCode');
      const codeLabel  = document.getElementById('codeLabel');
      const codeHint   = document.getElementById('codeHint');
      const codeError  = document.getElementById('codeError');

      const config = {
        student: {
          label: <?= json_encode(t('login_code_label')) ?>,
          placeholder: <?= json_encode(t('login_code_placeholder')) ?>,
          hint: <?= json_encode(t('login_code_hint')) ?>,
          error: <?= json_encode(t('login_code_error')) ?>
        },
        doctor: {
          label: <?= json_encode(t('login_email_label')) ?>,
          placeholder: <?= json_encode(t('login_email_placeholder')) ?>,
          hint: <?= json_encode(t('login_email_hint')) ?>,
          error: <?= json_encode(t('login_email_error')) ?>
        }
      };

      roleSelect.addEventListener('change', () => {
        const cfg = config[roleSelect.value] || config.student;
        codeLabel.textContent = cfg.label;
        codeInput.placeholder = cfg.placeholder;
        codeHint.textContent  = cfg.hint;
        codeError.textContent = cfg.error;
        codeInput.value       = '';
      });
    })();
  </script>

  <script src="../assets/js/login.js"></script>

  <?php include $_SERVER['DOCUMENT_ROOT'] . '/partials/footer.php'; ?>
</body>
</html>
