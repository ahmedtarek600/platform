-- =============================================
-- نظام الامتحانات - جداول إضافية
-- أضفها على database.sql الموجود
-- =============================================


-- =============================================
-- جدول الامتحانات (Exams)
-- =============================================
CREATE TABLE IF NOT EXISTS exams (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255)         NOT NULL COMMENT 'اسم الامتحان',
    description     TEXT                 DEFAULT NULL COMMENT 'وصف الامتحان / نوعه (MCQ / مقالي)',
    grade           TINYINT UNSIGNED     NOT NULL COMMENT 'الفرقة 1-4',
    subject_id      INT                  NOT NULL,
    duration_mins   SMALLINT UNSIGNED    NOT NULL DEFAULT 60 COMMENT 'مدة الامتحان بالدقائق',
    total_marks     SMALLINT UNSIGNED    NOT NULL DEFAULT 100 COMMENT 'الدرجة الكلية',
    pass_marks      SMALLINT UNSIGNED    NOT NULL DEFAULT 50  COMMENT 'درجة النجاح',
    num_questions   SMALLINT UNSIGNED    NOT NULL DEFAULT 0   COMMENT 'عدد الأسئلة (يُحسب تلقائياً)',
    allow_once      TINYINT(1)           NOT NULL DEFAULT 1   COMMENT '1 = مرة واحدة فقط',
    show_answers    TINYINT(1)           NOT NULL DEFAULT 0   COMMENT '0 = لا تعرض الإجابات',
    is_active       TINYINT(1)           NOT NULL DEFAULT 1   COMMENT '1 = مفعّل ومتاح للطلاب',
    created_by      INT                  NOT NULL COMMENT 'معرف الأدمن',
    created_at      TIMESTAMP            DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP            DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id)  REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- جدول أسئلة الامتحان (Exam Questions)
-- =============================================
CREATE TABLE IF NOT EXISTS exam_questions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    exam_id         INT              NOT NULL,
    question_text   TEXT             NOT NULL COMMENT 'نص السؤال',
    question_type   ENUM('mcq','essay') NOT NULL DEFAULT 'mcq',
    option_a        VARCHAR(500)     DEFAULT NULL COMMENT 'اختيار أ (للـ MCQ)',
    option_b        VARCHAR(500)     DEFAULT NULL COMMENT 'اختيار ب',
    option_c        VARCHAR(500)     DEFAULT NULL COMMENT 'اختيار ج',
    option_d        VARCHAR(500)     DEFAULT NULL COMMENT 'اختيار د',
    correct_answer  ENUM('a','b','c','d') DEFAULT NULL COMMENT 'الإجابة الصحيحة (MCQ فقط)',
    marks           TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'درجة السؤال',
    order_num       SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'ترتيب السؤال',
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- جدول جلسات الامتحان (Exam Sessions) - لمنع الغش وتتبع الحالة
-- =============================================
CREATE TABLE IF NOT EXISTS exam_sessions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    exam_id         INT              NOT NULL,
    user_id         INT              NOT NULL,
    started_at      TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    submitted_at    TIMESTAMP        NULL DEFAULT NULL COMMENT 'وقت التسليم',
    status          ENUM('in_progress','submitted','timed_out','banned') NOT NULL DEFAULT 'in_progress',
    cheat_attempts  TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'عدد محاولات الغش',
    time_remaining  INT              DEFAULT NULL COMMENT 'الوقت المتبقي بالثواني (للـ auto-save)',
    ip_address      VARCHAR(45)      DEFAULT NULL,
    UNIQUE KEY unique_user_exam (exam_id, user_id),
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- جدول إجابات الطلاب (Student Answers)
-- =============================================
CREATE TABLE IF NOT EXISTS exam_answers (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    session_id      INT              NOT NULL,
    question_id     INT              NOT NULL,
    answer_mcq      ENUM('a','b','c','d') DEFAULT NULL COMMENT 'إجابة MCQ',
    answer_essay    TEXT             DEFAULT NULL COMMENT 'إجابة مقالية',
    saved_at        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_session_question (session_id, question_id),
    FOREIGN KEY (session_id)  REFERENCES exam_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES exam_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- جدول النتائج النهائية (Exam Results)
-- =============================================
CREATE TABLE IF NOT EXISTS exam_results (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    session_id      INT              NOT NULL UNIQUE,
    exam_id         INT              NOT NULL,
    user_id         INT              NOT NULL,
    total_marks     SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'الدرجة الكلية للامتحان',
    obtained_marks  SMALLINT UNSIGNED DEFAULT NULL COMMENT 'الدرجة التي حصل عليها (يُضيفها الأدمن)',
    is_reviewed     TINYINT(1)       NOT NULL DEFAULT 0 COMMENT '0 = لم يُراجع بعد',
    reviewed_by     INT              DEFAULT NULL COMMENT 'معرف الأدمن الذي راجع',
    reviewed_at     TIMESTAMP        NULL DEFAULT NULL,
    notes           TEXT             DEFAULT NULL COMMENT 'ملاحظات الأدمن',
    FOREIGN KEY (session_id)  REFERENCES exam_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id)     REFERENCES exams(id)         ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)         ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id)         ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- جدول سجل محاولات الغش (Cheat Log) - Backend Security
-- =============================================
CREATE TABLE IF NOT EXISTS exam_cheat_log (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    session_id      INT              NOT NULL,
    user_id         INT              NOT NULL,
    exam_id         INT              NOT NULL,
    cheat_type      ENUM('tab_switch','copy_attempt','paste_attempt','screenshot','window_blur','right_click') NOT NULL,
    attempt_num     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    logged_at       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    ip_address      VARCHAR(45)      DEFAULT NULL,
    FOREIGN KEY (session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)         ON DELETE CASCADE,
    FOREIGN KEY (exam_id)    REFERENCES exams(id)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
