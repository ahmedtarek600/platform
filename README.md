# 🎓 المنصة الذكية – كلية العلوم (Math & CS Platform)

## هيكل المشروع

```
/Math&CS Platform
├── index.php                  ← الصفحة الرئيسية (Hero + Dashboard)
├── .htaccess                  ← إعدادات الأمان
├── database.sql               ← ملف قاعدة البيانات
│
├── login/
│   └── index.php              ← صفحة تسجيل الدخول
│
├── register/
│   └── index.php              ← صفحة إنشاء الحساب
│
├── reset-password/
│   └── index.php              ← صفحة نسيان كلمة المرور
│
├── auth/
│   ├── login.php              ← معالج تسجيل الدخول (POST)
│   ├── register.php           ← معالج التسجيل (POST)
│   └── logout.php             ← تسجيل الخروج
│
├── api/
│   ├── get_subjects.php       ← جلب المواد حسب الفرقة
│   ├── get_uploads.php        ← جلب الملفات المرفوعة
│   ├── upload_file.php        ← رفع الملفات
│   ├── approve_upload.php     ← موافقة/رفض الملفات (أدمن)
│   ├── get_pending.php        ← قائمة الملفات المعلقة (أدمن)
│   ├── reset_verify.php       ← التحقق من الكود عند نسيان الكلمة
│   ├── reset_password.php     ← حفظ كلمة المرور الجديدة
│   └── contact.php            ← نموذج التواصل
│
├── config/
│   └── db.php                 ← إعدادات قاعدة البيانات
│
├── assets/
│   ├── css/
│   │   ├── style.css          ← CSS الرئيسي (Hero + Dashboard)
│   │   ├── login.css          ← CSS صفحة الدخول
│   │   ├── register.css       ← CSS صفحة التسجيل
│   │   └── reset.css          ← CSS صفحة نسيان الكلمة
│   │
│   └── js/
│       ├── main.js            ← JS الرئيسي (Hero + Dashboard)
│       ├── login.js           ← JS صفحة الدخول
│       ├── register.js        ← JS صفحة التسجيل
│       └── reset.js           ← JS صفحة نسيان الكلمة
│
└── uploads/                   ← مجلد الملفات المرفوعة
    └── .htaccess              ← منع تنفيذ PHP فيه
```

---

## 🚀 خطوات التثبيت

### 1. إعداد قاعدة البيانات

```sql
-- في phpMyAdmin أو MySQL CLI:
SOURCE /path/to/database.sql;
```

### 2. إعداد الاتصال بقاعدة البيانات

افتح `config/db.php` وعدّل:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'math_cs_platform');
define('DB_USER', 'root');       // ← اسم مستخدم MySQL
define('DB_PASS', '');           // ← كلمة مرور MySQL
```

### 3. صلاحيات مجلد الملفات

```bash
chmod 755 uploads/
# أو على Linux:
chown www-data:www-data uploads/
```

### 4. الوصول للمنصة

افتح المتصفح على:
```
http://localhost/math-cs-platform/
```

---

## 👤 نظام الأدوار

| الدور  | التسجيل | الصلاحيات |
|--------|---------|-----------|
| `user` | تلقائي عند التسجيل | رفع ملفات (تنتظر مراجعة) |
| `admin` | يُعيَّن يدوياً من DB | رفع مباشر + مراجعة ملفات الطلاب |

### كيف تجعل مستخدماً أدمن؟

```sql
UPDATE users SET role = 'admin' WHERE code = 'كود_الطالب';
```

---

## 🔒 الأمان

- ✅ PHP Sessions (لا localStorage)
- ✅ Prepared Statements (منع SQL Injection)
- ✅ `password_hash()` + `password_verify()` (bcrypt)
- ✅ Session regeneration عند تسجيل الدخول
- ✅ منع تنفيذ PHP في مجلد uploads
- ✅ التحقق من امتداد الملف وحجمه عند الرفع
- ✅ التحقق من صلاحيات المستخدم في كل API endpoint

---

## 📚 المواد الدراسية

المواد موجودة في `database.sql` ومُقسَّمة على 4 فرق:
- **الفرقة الأولى:** 8 مواد
- **الفرقة الثانية:** 7 مواد
- **الفرقة الثالثة:** 7 مواد
- **الفرقة الرابعة:** 7 مواد

---

## ⚙️ متطلبات الخادم

- PHP 8.0+
- MySQL 5.7+ أو MariaDB 10.3+
- Apache مع `mod_rewrite`
- امتداد PDO_MySQL
