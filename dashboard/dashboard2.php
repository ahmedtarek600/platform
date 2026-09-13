<?php
// =============================================
// dashboard/dashboard2.php – لوحة تحكم الدكتور
// =============================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/db.php';

$isLoggedIn = !empty($_SESSION['user_id']);
$userRole   = $isLoggedIn ? ($_SESSION['user_role'] ?? '') : '';

// السماح فقط للدكتور بالدخول لهذه الصفحة
if (!$isLoggedIn || $userRole !== 'doctor') {
    header('Location: ../index.php');
    exit;
}

$userName  = htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES);
$userGrade = (int) ($_SESSION['user_grade'] ?? 0);

function getInitials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) >= 2) return mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1);
    return mb_substr($parts[0] ?? 'د', 0, 1);
}
$initials = getInitials($_SESSION['user_name'] ?? 'دكتور');
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $currentDir ?>">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars(t('doctor_page_title'), ENT_QUOTES) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
<link rel="stylesheet" href="dashboard2.css" />
<link rel="stylesheet" href="../assets/css/style.css" />
<link rel="stylesheet" href="../assets/css/exam.css" />
</head>
<body>

  <!-- ===================== NAVBAR ===================== -->
  <header class="navbar" id="mainNav">
    <div class="nav-container">
      <div class="brand">
        <span class="brand-icon">🎓</span>
        <span class="brand-text"><?= htmlspecialchars(t('brand'), ENT_QUOTES) ?></span>
      </div>
      <div class="nav-actions" id="userActions">
        <?php
          $nextLang    = ($currentLang === 'ar') ? 'en' : 'ar';
          $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
        ?>
        <a class="lang-nav-btn" href="/set_lang.php?lang=<?= $nextLang ?>&return=<?= urlencode($currentPath) ?>">🌐 <?= htmlspecialchars(t('lang_toggle'), ENT_QUOTES) ?></a>
        <div class="nav-user-pill"><?= htmlspecialchars(t('doctor_welcome'), ENT_QUOTES) ?> <?= $userName ?></div>
        <a class="nav-logout-btn" href="../auth/logout.php">
          <svg viewBox="0 0 24 24" fill="none" width="16" height="16">
            <path d="M9 21H5C4.5 21 4 20.8 3.6 20.4C3.2 20 3 19.5 3 19V5C3 4.5 3.2 4 3.6 3.6C4 3.2 4.5 3 5 3H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M16 17L21 12L16 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M21 12H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          <?= htmlspecialchars(t('nav_logout'), ENT_QUOTES) ?>
        </a>
      </div>
    </div>
  </header>

  <!-- ===================== DASHBOARD LAYOUT ===================== -->
  <div class="dashboard-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-logo">
        <span><?= htmlspecialchars(t('sidebar_brand_short'), ENT_QUOTES) ?></span>
        <i class="fa-solid fa-graduation-cap"></i>
      </div>

      <nav class="sidebar-nav">
        <a class="sidebar-link active" href="#" data-panel="courses">
          <span><?= htmlspecialchars(t('doctor_sidebar_item'), ENT_QUOTES) ?></span>
          <i class="fa-solid fa-folder-open"></i>
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="user-info">
          <span class="user-name"><?= $userName ?></span>
          <span class="user-role"><?= htmlspecialchars(t('srv_role_doctor'), ENT_QUOTES) ?></span>
        </div>
        <div class="user-avatar"><?= htmlspecialchars($initials, ENT_QUOTES) ?></div>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="dash-main">

      <!-- ===== PANEL: رفع المحتوى ===== -->
      <section class="dash-panel active" id="panelCourses">

        <div class="panel-header">
          <h1><?= htmlspecialchars(t('doctor_upload_title'), ENT_QUOTES) ?></h1>
        </div>
        <hr class="panel-divider" />

        <!-- اختيار الفرقة والترم -->
        <div class="subjects-bar" style="margin-bottom:14px; gap:20px;">
          <div style="display:flex; align-items:center; gap:8px;">
            <label style="font-size:14px;font-weight:700;color:rgba(255,255,255,.85);"><?= htmlspecialchars(t('filter_grade_label'), ENT_QUOTES) ?></label>
            <select id="gradeSelect" class="form-control" style="max-width:220px;">
              <option value="1"><?= htmlspecialchars(t('grade_1'), ENT_QUOTES) ?></option>
              <option value="2"><?= htmlspecialchars(t('grade_2'), ENT_QUOTES) ?></option>
              <option value="3"><?= htmlspecialchars(t('grade_3'), ENT_QUOTES) ?></option>
              <option value="4"><?= htmlspecialchars(t('grade_4'), ENT_QUOTES) ?></option>
            </select>
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            <label style="font-size:14px;font-weight:700;color:rgba(255,255,255,.85);"><?= htmlspecialchars(t('semester_label'), ENT_QUOTES) ?></label>
            <select id="semesterSelect" class="form-control" style="max-width:180px;">
              <option value="1"><?= htmlspecialchars(t('semester_1'), ENT_QUOTES) ?></option>
              <option value="2"><?= htmlspecialchars(t('semester_2'), ENT_QUOTES) ?></option>
            </select>
          </div>
        </div>

        <!-- Subjects bar (يُملأ ديناميكيًا) -->
        <div class="subjects-bar" id="subjectsBar">
          <div class="loading-subjects"><?= htmlspecialchars(t('file_loading_subjects'), ENT_QUOTES) ?></div>
        </div>

        <!-- Content type tabs -->
        <div class="content-btns" id="contentBtns" style="display:none;">
          <button class="content-btn active" data-content="lecture"><?= htmlspecialchars(t('doc_content_lecture'), ENT_QUOTES) ?></button>
          <button class="content-btn" data-content="assignment"><?= htmlspecialchars(t('doc_content_assignment'), ENT_QUOTES) ?></button>
          <button class="content-btn" data-content="task"><?= htmlspecialchars(t('doc_content_task'), ENT_QUOTES) ?></button>
          <button class="content-btn" data-content="section"><?= htmlspecialchars(t('doc_content_section'), ENT_QUOTES) ?></button>
        </div>

        <!-- Upload trigger -->
        <div class="upload-area" id="uploadArea" style="display:none;">
          <button class="upload-trigger-btn" id="uploadTriggerBtn">
            <i class="fa-solid fa-arrow-up-from-bracket"></i> <?= htmlspecialchars(t('upload_btn_generic'), ENT_QUOTES) ?>
          </button>
        </div>

        <!-- Files list -->
        <div id="filesList" class="files-cards-grid"></div>

      </section>

    </main>
  </div>

  <!-- ===================== UPLOAD MODAL ===================== -->
  <div class="platform-modal" id="uploadModal">
    <div class="platform-modal-box">
      <div class="platform-modal-header">
        <h3><i class="fa-solid fa-arrow-up-from-bracket"></i> <?= htmlspecialchars(t('upload_modal_title'), ENT_QUOTES) ?></h3>
        <button class="modal-close-btn" id="modalCloseBtn">✕</button>
      </div>
      <form id="modalUploadForm" class="platform-modal-form">

        <!-- حقل رقم السكشن (يظهر فقط عند اختيار نوع "سكاشن") -->
        <div class="modal-field" id="modalSectionField" style="display:none;">
          <label class="modal-label" style="font-size:13.5px;font-weight:700;color:rgba(255,255,255,.85);"><?= htmlspecialchars(t('modal_section_label'), ENT_QUOTES) ?></label>
          <select id="modalSectionNumber" class="form-control">
            <option value="1"><?= htmlspecialchars(t('type_section'), ENT_QUOTES) ?> 1</option>
            <option value="2"><?= htmlspecialchars(t('type_section'), ENT_QUOTES) ?> 2</option>
            <option value="3"><?= htmlspecialchars(t('type_section'), ENT_QUOTES) ?> 3</option>
            <option value="4"><?= htmlspecialchars(t('type_section'), ENT_QUOTES) ?> 4</option>
          </select>
        </div>

        <label class="modal-file-label" for="modalFileInput">
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <span id="modalFileName"><?= htmlspecialchars(t('modal_choose_file_text'), ENT_QUOTES) ?></span>
        </label>
        <input type="file" id="modalFileInput" hidden />
        <div class="modal-actions">
          <button type="submit" class="modal-submit-btn"><?= htmlspecialchars(t('modal_upload_btn'), ENT_QUOTES) ?></button>
          <button type="button" class="modal-cancel-btn" id="modalCancelBtn"><?= htmlspecialchars(t('modal_cancel_btn'), ENT_QUOTES) ?></button>
        </div>
      </form>
    </div>
  </div>

  <script>
    window.APP = {
      isLoggedIn: true,
      userId:     <?= (int) $_SESSION['user_id'] ?>,
      userGrade:  <?= $userGrade ?>,
      userName:   <?= json_encode($userName) ?>
    };
    window.LANG = {
      loadingSubjects: <?= json_encode(t('file_loading_subjects')) ?>,
      noSubjectsTerm: <?= json_encode(t('file_no_subjects_term')) ?>,
      loadSubjectsErr: <?= json_encode(t('doc_load_subjects_err')) ?>,
      loading: <?= json_encode(t('file_loading')) ?>,
      noFilesTitle: <?= json_encode(t('file_no_files_title')) ?>,
      noFilesSub: <?= json_encode(t('file_no_files_sub')) ?>,
      loadFilesErr: <?= json_encode(t('doc_load_files_err')) ?>,
      defaultName: <?= json_encode(t('file_default_name')) ?>,
      deleteBtn: <?= json_encode(t('file_delete_btn')) ?>,
      uploadedBy: <?= json_encode(t('file_uploaded_by')) ?>,
      sectionLabel: <?= json_encode(t('type_section')) ?>,
      downloadBtn: <?= json_encode(t('file_download_btn')) ?>,
      viewBtn: <?= json_encode(t('file_view_btn')) ?>,
      deleteConfirm: <?= json_encode(t('doc_delete_confirm')) ?>,
      deleteFailed: <?= json_encode(t('doc_delete_failed')) ?>,
      connError: <?= json_encode(t('doc_conn_error')) ?>,
      selectSubjectFirst: <?= json_encode(t('file_select_subject_first')) ?>,
      chooseFileText: <?= json_encode(t('modal_choose_file_text')) ?>,
      chooseFileFirst: <?= json_encode(t('doc_choose_file_first')) ?>,
      uploading: <?= json_encode(t('upload_uploading')) ?>,
      uploadGenericErr: <?= json_encode(t('doc_upload_generic_err')) ?>,
      uploadBtn: <?= json_encode(t('modal_upload_btn')) ?>,
      locale: <?= json_encode($currentLang === 'ar' ? 'ar-EG' : 'en-US') ?>
    };
  </script>
  <script src="dashboard2.js"></script>

  <?php include $_SERVER['DOCUMENT_ROOT'] . '/partials/footer.php'; ?>
</body>
</html>
