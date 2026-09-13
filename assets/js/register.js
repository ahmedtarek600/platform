// =============================================
// assets/js/register.js  – بدون localStorage
// =============================================

const form            = document.getElementById('registerForm');
const fullName        = document.getElementById('fullName');
const uniCode         = document.getElementById('uniCode');
const grade           = document.getElementById('grade');
const password        = document.getElementById('password');
const confirmPassword = document.getElementById('confirmPassword');
const togglePass      = document.getElementById('togglePass');
const toggleConfirm   = document.getElementById('toggleConfirm');
const nameError       = document.getElementById('nameError');
const codeError       = document.getElementById('codeError');
const gradeError      = document.getElementById('gradeError');
const passError       = document.getElementById('passError');
const confirmError    = document.getElementById('confirmError');
const toast           = document.getElementById('toast');
const submitBtn       = document.getElementById('submitBtn');

const L = window.LANG || {
  showText: 'إظهار', hideText: 'إخفاء',
  submitText: 'إنشاء الحساب', submittingText: 'جاري الإنشاء...',
  toastFill: 'راجع البيانات أولاً',
  toastSuccess: '✅ تم إنشاء الحساب بنجاح، جاري التحويل...',
  toastError: 'حدث خطأ، حاول مرة أخرى',
  toastConnError: 'خطأ في الاتصال بالخادم',
  nameErrorMsg: 'من فضلك اكتب اسمك الكامل',
  codeErrorMsg: 'من فضلك اكتب الكود الجامعي',
  gradeErrorMsg: 'من فضلك اختر الفرقة',
  passRequiredMsg: 'من فضلك اكتب كلمة المرور',
  passErrorMsg: 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
  confirmErrorMsg: 'كلمتا المرور غير متطابقتين'
};

function showToast(message, isError = false) {
  toast.textContent    = message;
  toast.style.background  = isError ? 'rgba(239,68,68,0.12)'  : 'rgba(34,197,94,0.12)';
  toast.style.borderColor = isError ? 'rgba(239,68,68,0.3)'   : 'rgba(34,197,94,0.3)';
  toast.style.color       = isError ? '#f87171'                : '#4ade80';
  toast.style.border      = '1px solid';
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

function setError(input, errorEl, show, msg = null) {
  if (show) {
    input.style.borderColor = '#dc2626';
    errorEl.style.display   = 'block';
    if (msg) errorEl.textContent = msg;
  } else {
    input.style.borderColor = '';
    errorEl.style.display   = 'none';
  }
}

togglePass.addEventListener('click', () => {
  const h = password.type === 'password';
  password.type = h ? 'text' : 'password';
  togglePass.textContent = h ? L.hideText : L.showText;
});
toggleConfirm.addEventListener('click', () => {
  const h = confirmPassword.type === 'password';
  confirmPassword.type = h ? 'text' : 'password';
  toggleConfirm.textContent = h ? L.hideText : L.showText;
});

form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const nameVal    = fullName.value.trim();
  const codeVal    = uniCode.value.trim();
  const gradeVal   = grade.value;
  const passVal    = password.value;
  const confirmVal = confirmPassword.value;

  const nameInvalid    = nameVal.length === 0;
  const codeInvalid    = codeVal.length === 0;
  const gradeInvalid   = !gradeVal;
  const passInvalid    = passVal.length < 6;
  const confirmInvalid = confirmVal !== passVal;

  setError(fullName,        nameError,    nameInvalid,  L.nameErrorMsg);
  setError(uniCode,         codeError,    codeInvalid,  L.codeErrorMsg);
  setError(grade,           gradeError,   gradeInvalid, L.gradeErrorMsg);
  setError(password,        passError,    passInvalid,
           passVal.length === 0 ? L.passRequiredMsg : L.passErrorMsg);
  setError(confirmPassword, confirmError, confirmInvalid, L.confirmErrorMsg);

  if (nameInvalid || codeInvalid || gradeInvalid || passInvalid || confirmInvalid) {
    showToast(L.toastFill, true);
    return;
  }

  submitBtn.disabled     = true;
  submitBtn.textContent  = L.submittingText;

  try {
    const fd = new FormData();
    fd.append('name',     nameVal);
    fd.append('code',     codeVal);
    fd.append('grade',    gradeVal);
    fd.append('password', passVal);
    fd.append('confirm',  confirmVal);

    const res  = await fetch('../auth/register.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      showToast(L.toastSuccess);
      setTimeout(() => {
        window.location.href = data.redirect || '../login/index.php';
      }, 1200);
    } else {
      showToast(data.message || L.toastError, true);
      submitBtn.disabled    = false;
      submitBtn.textContent = L.submitText;
    }
  } catch {
    showToast(L.toastConnError, true);
    submitBtn.disabled    = false;
    submitBtn.textContent = L.submitText;
  }
});
