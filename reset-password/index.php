<?php
// =============================================
// reset-password/index.php – إعادة تعيين كلمة المرور
// =============================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/lang.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$nextLang    = ($currentLang === 'ar') ? 'en' : 'ar';
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!doctype html>
<html lang="<?= $currentLang ?>" dir="<?= $currentDir ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars(t('reset_page_title'), ENT_QUOTES) ?> | <?= htmlspecialchars(t('auth_brand_title'), ENT_QUOTES) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/reset.css" />
</head>
<body>
  <div class="bg"></div>

  <main class="auth-wrap">
    <section class="auth-card">

      <!-- ===== LEFT: STEPS ===== -->
      <div class="auth-left">

        <!-- STEP 1: Identity -->
        <div class="step" id="step1">
          <div class="header" style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
            <div>
              <div class="step-badge"><?= sprintf(t('reset_step_of'), 1) ?></div>
              <h1><?= htmlspecialchars(t('reset_s1_title'), ENT_QUOTES) ?></h1>
              <p><?= htmlspecialchars(t('reset_s1_sub'), ENT_QUOTES) ?></p>
            </div>
            <a class="lang-nav-btn" style="color:#333; flex-shrink:0;"
               href="/set_lang.php?lang=<?= $nextLang ?>&return=<?= urlencode($currentPath) ?>">
              🌐 <?= htmlspecialchars(t('lang_toggle'), ENT_QUOTES) ?>
            </a>
          </div>
          <form class="form" id="form1" novalidate>
            <div class="field">
              <label for="uniCode"><?= htmlspecialchars(t('reset_s1_code_label'), ENT_QUOTES) ?></label>
              <div class="input-wrap">
                <input id="uniCode" name="code" type="text"
                  placeholder="<?= htmlspecialchars(t('reset_s1_code_ph'), ENT_QUOTES) ?>"
                  autocomplete="off" required />
              </div>
              <small class="hint"><?= htmlspecialchars(t('reset_s1_code_hint'), ENT_QUOTES) ?></small>
              <small class="error" id="codeError"><?= htmlspecialchars(t('reset_s1_code_err'), ENT_QUOTES) ?></small>
            </div>
            <button class="btn" type="submit" id="verifyBtn"><?= htmlspecialchars(t('reset_s1_verify_btn'), ENT_QUOTES) ?></button>
            <p class="bottom" style="margin-top:20px;">
              <?= htmlspecialchars(t('reset_s1_remember'), ENT_QUOTES) ?> <a class="link" href="../login/index.php"><?= htmlspecialchars(t('reset_s1_login_link'), ENT_QUOTES) ?></a>
            </p>
            <div class="toast" id="toast1" role="status" aria-live="polite"></div>
          </form>
        </div>

        <!-- STEP 2: OTP (في البيئة الحقيقية يُرسل OTP بالبريد) -->
        <div class="step hidden" id="step2">
          <div class="header">
            <div class="step-badge"><?= sprintf(t('reset_step_of'), 2) ?></div>
            <h1><?= htmlspecialchars(t('reset_s2_title'), ENT_QUOTES) ?></h1>
            <p><?= htmlspecialchars(t('reset_s2_sub'), ENT_QUOTES) ?></p>
          </div>
          <form class="form" id="form2" novalidate>
            <div class="field">
              <label><?= htmlspecialchars(t('reset_s2_label'), ENT_QUOTES) ?></label>
              <div class="otp-wrap">
                <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" />
                <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" />
                <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" />
                <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" />
                <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" />
                <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" />
              </div>
              <small class="error" id="otpError" style="text-align:center; display:none;"><?= htmlspecialchars(t('reset_s2_err'), ENT_QUOTES) ?></small>
            </div>
            <div class="resend-row">
              <span class="muted-sm"><?= htmlspecialchars(t('reset_s2_no_code'), ENT_QUOTES) ?></span>
              <a class="link" href="#" id="resendBtn" onclick="return false;"><?= htmlspecialchars(t('reset_s2_resend'), ENT_QUOTES) ?></a>
            </div>
            <button class="btn" type="submit"><?= htmlspecialchars(t('reset_s2_verify_btn'), ENT_QUOTES) ?></button>
            <button class="btn-back" type="button" id="backToStep1"><?= htmlspecialchars(t('home_cta_arrow'), ENT_QUOTES) ?> <?= htmlspecialchars(t('reset_s2_back_btn'), ENT_QUOTES) ?></button>
            <div class="toast" id="toast2" role="status" aria-live="polite"></div>
          </form>
        </div>

        <!-- STEP 3: New Password -->
        <div class="step hidden" id="step3">
          <div class="header">
            <div class="step-badge"><?= sprintf(t('reset_step_of'), 3) ?></div>
            <h1><?= htmlspecialchars(t('reset_s3_title'), ENT_QUOTES) ?></h1>
            <p><?= htmlspecialchars(t('reset_s3_sub'), ENT_QUOTES) ?></p>
          </div>
          <form class="form" id="form3" novalidate>
            <div class="field">
              <label for="newPass"><?= htmlspecialchars(t('reset_s3_pass_label'), ENT_QUOTES) ?></label>
              <div class="input-wrap">
                <input id="newPass" name="newPass" type="password"
                  placeholder="<?= htmlspecialchars(t('reset_s3_pass_ph'), ENT_QUOTES) ?>"
                  autocomplete="new-password" required />
                <button class="eye-btn" type="button" id="toggleNew"><?= htmlspecialchars(t('reset_show'), ENT_QUOTES) ?></button>
              </div>
              <small class="error" id="newPassError"><?= htmlspecialchars(t('reset_s3_pass_err'), ENT_QUOTES) ?></small>
            </div>
            <div class="field">
              <label for="confirmNewPass"><?= htmlspecialchars(t('reset_s3_confirm_label'), ENT_QUOTES) ?></label>
              <div class="input-wrap">
                <input id="confirmNewPass" name="confirmNewPass" type="password"
                  placeholder="<?= htmlspecialchars(t('reset_s3_confirm_ph'), ENT_QUOTES) ?>"
                  autocomplete="new-password" required />
                <button class="eye-btn" type="button" id="toggleConfirmNew"><?= htmlspecialchars(t('reset_show'), ENT_QUOTES) ?></button>
              </div>
              <small class="error" id="confirmNewError"><?= htmlspecialchars(t('reset_s3_confirm_err_mismatch'), ENT_QUOTES) ?></small>
            </div>
            <button class="btn" type="submit" id="resetBtn"><?= htmlspecialchars(t('reset_s3_submit_btn'), ENT_QUOTES) ?></button>
            <div class="toast" id="toast3" role="status" aria-live="polite"></div>
          </form>
        </div>

        <!-- SUCCESS -->
        <div class="step hidden" id="stepSuccess">
          <div class="success-box">
            <div class="success-icon">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M20 7L10 17L5 12" stroke="currentColor" stroke-width="2.5"
                  stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h1><?= htmlspecialchars(t('reset_success_title'), ENT_QUOTES) ?></h1>
            <p><?= t('reset_success_msg') ?></p>
            <a class="btn" href="../login/index.php" style="display:block;text-align:center;text-decoration:none;margin-top:10px;">
              <?= htmlspecialchars(t('reset_success_btn'), ENT_QUOTES) ?>
            </a>
          </div>
        </div>

      </div>

      <!-- ===== RIGHT: INFO PANEL ===== -->
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
        <h3><?= htmlspecialchars(t('reset_panel_title'), ENT_QUOTES) ?></h3>
        <p class="desc"><?= htmlspecialchars(t('reset_panel_desc'), ENT_QUOTES) ?></p>
        <div class="progress-steps">
          <div class="progress-step active" id="prog1"><div class="prog-circle">1</div><span><?= htmlspecialchars(t('reset_prog1'), ENT_QUOTES) ?></span></div>
          <div class="prog-line" id="line1"></div>
          <div class="progress-step" id="prog2"><div class="prog-circle">2</div><span><?= htmlspecialchars(t('reset_prog2'), ENT_QUOTES) ?></span></div>
          <div class="prog-line" id="line2"></div>
          <div class="progress-step" id="prog3"><div class="prog-circle">3</div><span><?= htmlspecialchars(t('reset_prog3'), ENT_QUOTES) ?></span></div>
        </div>
        <ul class="features">
          <li><span class="ico"><svg viewBox="0 0 24 24" fill="none"><path d="M12 15V17M6 21H18C19.1 21 20 20.1 20 19V13C20 11.9 19.1 11 18 11H6C4.9 11 4 11.9 4 13V19C4 20.1 4.9 21 6 21ZM16 11V7C16 4.8 14.2 3 12 3C9.8 3 8 4.8 8 7V11H16Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><?= htmlspecialchars(t('reset_feat1'), ENT_QUOTES) ?></li>
          <li><span class="ico"><svg viewBox="0 0 24 24" fill="none"><path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M22 6L12 13L2 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><?= htmlspecialchars(t('reset_feat2'), ENT_QUOTES) ?></li>
          <li><span class="ico"><svg viewBox="0 0 24 24" fill="none"><path d="M20 7L10 17L5 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span><?= htmlspecialchars(t('reset_feat3'), ENT_QUOTES) ?></li>
        </ul>
      </div>

    </section>
  </main>

  <script>
    window.LANG = {
      verifying: <?= json_encode(t('reset_toast_verifying')) ?>,
      verifyBtnText: <?= json_encode(t('reset_s1_verify_btn')) ?>,
      verified: <?= json_encode(t('reset_toast_verified')) ?>,
      wrongCode: <?= json_encode(t('reset_toast_wrong_code')) ?>,
      codeNotFound: <?= json_encode(t('reset_toast_code_notfound')) ?>,
      connErr: <?= json_encode(t('reset_toast_conn_err')) ?>,
      codeRequired: <?= json_encode(t('reset_s1_code_err')) ?>,
      otpRequired: <?= json_encode(t('reset_s2_err')) ?>,
      otpVerified: <?= json_encode(t('reset_toast_otp_verified')) ?>,
      otpResent: <?= json_encode(t('reset_toast_otp_resent')) ?>,
      show: <?= json_encode(t('reset_show')) ?>,
      hide: <?= json_encode(t('reset_hide')) ?>,
      passShort: <?= json_encode(t('reset_s3_pass_err')) ?>,
      confirmEmpty: <?= json_encode(t('reset_s3_confirm_err_empty')) ?>,
      confirmMismatch: <?= json_encode(t('reset_s3_confirm_err_mismatch')) ?>,
      saving: <?= json_encode(t('reset_toast_saving')) ?>,
      resetSuccess: <?= json_encode(t('reset_toast_success')) ?>,
      genericErr: <?= json_encode(t('reset_toast_generic_err')) ?>,
      submitBtnText: <?= json_encode(t('reset_s3_submit_btn')) ?>
    };
  </script>
  <script src="../assets/js/reset.js"></script>
</body>
</html>
