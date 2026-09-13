/* =====================================================================
   dashboard2.js — لوحة تحكم الدكتور، متصلة بالـ API الحقيقي
   ===================================================================== */

document.addEventListener('DOMContentLoaded', () => {

  const app = window.APP;

  let currentSubjectId = null;
  let currentType       = 'lecture';
  let currentGrade      = app.userGrade >= 1 && app.userGrade <= 4 ? app.userGrade : 1;
  let currentSemester   = '1';
  let subjectsRequestId = 0; // يستخدم لتجاهل أي رد قديم يصل متأخراً
  let filesRequestId    = 0;

  const subjectsBar    = document.getElementById('subjectsBar');
  const contentBtns    = document.getElementById('contentBtns');
  const uploadArea     = document.getElementById('uploadArea');
  const filesList      = document.getElementById('filesList');
  const gradeSelect    = document.getElementById('gradeSelect');
  const semesterSelect = document.getElementById('semesterSelect');

  if (gradeSelect) {
    gradeSelect.value = String(currentGrade);
    gradeSelect.addEventListener('change', () => {
      currentGrade = parseInt(gradeSelect.value, 10);
      currentSubjectId = null;
      contentBtns.style.display = 'none';
      uploadArea.style.display  = 'none';
      filesList.innerHTML = '';
      loadSubjects();
    });
  }

  if (semesterSelect) {
    semesterSelect.value = currentSemester;
    semesterSelect.addEventListener('change', () => {
      currentSemester = semesterSelect.value;
      currentSubjectId = null;
      contentBtns.style.display = 'none';
      uploadArea.style.display  = 'none';
      filesList.innerHTML = '';
      loadSubjects();
    });
  }

  /* ---------- تحميل المواد الخاصة بالفرقة والترم المختارين ---------- */
  async function loadSubjects() {
    const gradeAtRequestTime    = currentGrade;
    const semesterAtRequestTime = currentSemester;
    const myRequestId = ++subjectsRequestId;
    const L = window.LANG || {};

    subjectsBar.innerHTML = `<div class="loading-subjects">${L.loadingSubjects || 'جاري تحميل المواد...'}</div>`;
    try {
      const res  = await fetch(`../api/get_subjects.php?grade=${gradeAtRequestTime}&semester=${semesterAtRequestTime}`);
      const data = await res.json();

      // لو المستخدم غيّر الفرقة/الترم تاني قبل ما الرد يوصل، تجاهل الرد القديم ده
      if (myRequestId !== subjectsRequestId) return;

      if (!data.success || !data.subjects.length) {
        subjectsBar.innerHTML = `<div class="loading-subjects">${L.noSubjectsTerm || 'لا توجد مواد لهذا الترم'}</div>`;
        return;
      }

      subjectsBar.innerHTML = '';
      data.subjects.forEach((sub, i) => {
        const btn = document.createElement('button');
        btn.className = 'subject-btn' + (i === 0 ? ' active' : '');
        btn.textContent = sub.name;
        btn.dataset.id = sub.id;
        btn.addEventListener('click', () => selectSubject(sub.id, btn));
        subjectsBar.appendChild(btn);
      });

      // اختيار أول مادة تلقائيًا
      const firstBtn = subjectsBar.querySelector('.subject-btn');
      if (firstBtn) selectSubject(firstBtn.dataset.id, firstBtn);

    } catch {
      if (myRequestId !== subjectsRequestId) return;
      subjectsBar.innerHTML = `<div class="loading-subjects">${L.loadSubjectsErr || 'خطأ في تحميل المواد'}</div>`;
    }
  }

  function selectSubject(subjectId, btn) {
    subjectsBar.querySelectorAll('.subject-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentSubjectId = subjectId;
    contentBtns.style.display = 'flex';
    uploadArea.style.display  = 'block';
    loadFiles();
  }

  /* ---------- تحميل الملفات المرفوعة للمادة/النوع الحاليين ---------- */
  async function loadFiles() {
    if (!currentSubjectId) return;
    const myRequestId = ++filesRequestId;
    const L = window.LANG || {};
    filesList.innerHTML = `<div class="loading-subjects">${L.loading || 'جاري التحميل...'}</div>`;

    let url = `../api/get_uploads.php?subject_id=${currentSubjectId}&type=${currentType}&grade=${currentGrade}`;

    try {
      const res  = await fetch(url);
      const data = await res.json();

      if (myRequestId !== filesRequestId) return; // رد قديم متأخر، تجاهله

      if (!data.success || !data.uploads.length) {
        filesList.innerHTML = `
          <div class="empty-state" style="grid-column:1/-1;">
            <i class="fa-solid fa-folder-open"></i>
            <h3>${L.noFilesTitle || 'لا يوجد ملفات هنا حتى الآن'}</h3>
            <p>${L.noFilesSub || 'لم يتم رفع أي ملف في هذا القسم بعد'}</p>
          </div>`;
        return;
      }

      filesList.innerHTML = data.uploads.map(fileCard).join('');
    } catch {
      if (myRequestId !== filesRequestId) return;
      filesList.innerHTML = `<div class="loading-subjects">${L.loadFilesErr || 'خطأ في تحميل الملفات'}</div>`;
    }
  }

  function fileCard(f) {
    const L = window.LANG || {};
    const filePath = '../' + f.file_path;
    const isOwner = Number(f.user_id) === Number(app.userId);

    const deleteBtn = isOwner
      ? `<button class="file-btn del" onclick="deleteMyFile(${f.id})">
           <i class="fa-solid fa-trash"></i> ${L.deleteBtn || 'حذف'}
         </button>`
      : '';

    return `
      <div class="file-card" id="doc-fcard-${f.id}">
        <i class="fa-solid ${fileIcon(f.original_name)} file-card-icon"></i>
        <div class="file-card-name">${escapeHtml(f.original_name || L.defaultName || 'ملف')}</div>
        <div class="file-card-meta">
          ${L.uploadedBy || 'رُفع بواسطة'}: ${escapeHtml(f.uploader_name)} | ${formatDate(f.created_at)}<br/>
          ${f.section_number ? (L.sectionLabel || 'سكشن') + ' ' + f.section_number : ''}
        </div>
        <div class="file-card-actions">
          <a class="file-btn dl" href="${escapeHtml(filePath)}" download>
            <i class="fa-solid fa-download"></i> ${L.downloadBtn || 'تحميل'}
          </a>
          <button class="file-btn view" onclick="window.open('${escapeHtml(filePath)}','_blank')">
            <i class="fa-solid fa-eye"></i> ${L.viewBtn || 'عرض'}
          </button>
          ${deleteBtn}
        </div>
      </div>`;
  }

  /* ---------- حذف ملف (الدكتور: ملفاته فقط، السيرفر بيتأكد برضو) ---------- */
  window.deleteMyFile = function (uploadId) {
    const L = window.LANG || {};
    if (!confirm(L.deleteConfirm || 'هل أنت متأكد من حذف هذا الملف؟ لا يمكن التراجع عن هذه الخطوة.')) return;

    const card = document.getElementById('doc-fcard-' + uploadId);
    if (card) card.style.opacity = '0.5';

    const fd = new FormData();
    fd.append('upload_id', uploadId);
    fd.append('action', 'delete');

    fetch('../api/approve_upload.php', { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success && card) {
          card.style.transition = 'all 0.3s';
          card.style.opacity    = '0';
          setTimeout(() => card.remove(), 300);
        } else {
          if (card) card.style.opacity = '1';
          alert(data.message || L.deleteFailed || 'تعذر حذف الملف');
        }
      })
      .catch(() => {
        if (card) card.style.opacity = '1';
        alert(L.connError || 'خطأ في الاتصال بالخادم');
      });
  };

  function fileIcon(name) {
    const ext = (name || '').split('.').pop().toLowerCase();
    if (ext === 'pdf') return 'fa-file-pdf';
    if (['doc','docx'].includes(ext)) return 'fa-file-word';
    if (['xls','xlsx'].includes(ext)) return 'fa-file-excel';
    if (['ppt','pptx'].includes(ext)) return 'fa-file-powerpoint';
    if (['jpg','jpeg','png','gif'].includes(ext)) return 'fa-file-image';
    if (['mp4','avi','mkv'].includes(ext)) return 'fa-file-video';
    if (['zip','rar'].includes(ext)) return 'fa-file-zipper';
    return 'fa-file';
  }

  function formatDate(dt) {
    if (!dt) return '';
    const d = new Date(dt.replace(' ', 'T'));
    const locale = (window.LANG && window.LANG.locale) || 'ar-EG';
    return d.toLocaleDateString(locale, { year: 'numeric', month: 'long', day: 'numeric' });
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ---------- تبديل نوع المحتوى ---------- */
  contentBtns.querySelectorAll('.content-btn[data-content]').forEach(btn => {
    btn.addEventListener('click', () => {
      contentBtns.querySelectorAll('.content-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentType = btn.dataset.content;

      const sectionField = document.getElementById('modalSectionField');
      if (sectionField) sectionField.dataset.forType = currentType;

      loadFiles();
    });
  });

  /* ---------- Upload modal ---------- */
  const uploadModal      = document.getElementById('uploadModal');
  const uploadTriggerBtn = document.getElementById('uploadTriggerBtn');
  const modalCloseBtn    = document.getElementById('modalCloseBtn');
  const modalCancelBtn   = document.getElementById('modalCancelBtn');
  const modalFileInput   = document.getElementById('modalFileInput');
  const modalFileName    = document.getElementById('modalFileName');
  const modalUploadForm  = document.getElementById('modalUploadForm');
  const modalSectionField= document.getElementById('modalSectionField');

  function openModal() {
    const L = window.LANG || {};
    if (!currentSubjectId) { alert(L.selectSubjectFirst || 'اختر مادة أولاً'); return; }
    modalSectionField.style.display = (currentType === 'section') ? 'block' : 'none';
    uploadModal.classList.add('active');
  }
  function closeModal() {
    const L = window.LANG || {};
    uploadModal.classList.remove('active');
    modalUploadForm.reset();
    modalFileName.textContent = L.chooseFileText || 'اضغط لاختيار ملف من جهازك';
  }

  uploadTriggerBtn.addEventListener('click', openModal);
  modalCloseBtn.addEventListener('click', closeModal);
  modalCancelBtn.addEventListener('click', closeModal);
  uploadModal.addEventListener('click', e => { if (e.target === uploadModal) closeModal(); });

  modalFileInput.addEventListener('change', () => {
    const L = window.LANG || {};
    modalFileName.textContent = modalFileInput.files[0]?.name || L.chooseFileText || 'اضغط لاختيار ملف من جهازك';
  });

  modalUploadForm.addEventListener('submit', async e => {
    e.preventDefault();
    const L = window.LANG || {};
    if (!modalFileInput.files.length) {
      alert(L.chooseFileFirst || 'اختر ملفاً أولاً');
      return;
    }

    const fd = new FormData();
    fd.append('file', modalFileInput.files[0]);
    fd.append('type', currentType);
    fd.append('subject_id', currentSubjectId);
    fd.append('grade', currentGrade);
    if (currentType === 'section') {
      const sec = document.getElementById('modalSectionNumber')?.value;
      if (sec) fd.append('section_number', sec);
    }

    const btn = modalUploadForm.querySelector('.modal-submit-btn');
    btn.disabled = true;
    btn.textContent = L.uploading || 'جاري الرفع...';

    try {
      const res  = await fetch('../api/upload_file.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        closeModal();
        loadFiles();
      } else {
        alert(data.message || L.uploadGenericErr || 'حدث خطأ أثناء الرفع');
      }
    } catch {
      alert(L.connError || 'خطأ في الاتصال بالخادم');
    }

    btn.disabled = false;
    btn.textContent = L.uploadBtn || 'رفع الملف';
  });

  /* ---------- بدء التشغيل ---------- */
  loadSubjects();

});
