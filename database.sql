-- =============================================
-- Math & CS Platform - Database Schema
-- =============================================



-- =============================================
-- جدول المستخدمين
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255)        NOT NULL,
    code        VARCHAR(100) UNIQUE NOT NULL,
    password    VARCHAR(255)        NOT NULL,
    grade       TINYINT UNSIGNED    NOT NULL COMMENT '1,2,3,4',
    role        ENUM('user','admin') NOT NULL DEFAULT 'user',
    avatar      VARCHAR(255)        DEFAULT NULL,
    created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- جدول المواد الدراسية
-- =============================================
CREATE TABLE IF NOT EXISTS subjects (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255)        NOT NULL,
    grade       TINYINT UNSIGNED    NOT NULL COMMENT '1,2,3,4',
    created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- جدول الملفات المرفوعة
-- =============================================
CREATE TABLE IF NOT EXISTS uploads (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT              NOT NULL,
    subject_id      INT              NOT NULL,
    grade           TINYINT UNSIGNED NOT NULL,
    type            ENUM('lecture','task','assignment','section','schedule','exam_schedule') NOT NULL,
    file_path       VARCHAR(600)     NOT NULL,
    original_name   VARCHAR(255)     DEFAULT NULL,
    section_number  TINYINT UNSIGNED DEFAULT NULL COMMENT 'للسكاشن والجداول',
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- بيانات المواد الدراسية الأولية
-- =============================================

-- الفرقة الأولى
INSERT INTO subjects (name, grade) VALUES
('كهربية ومغناطيسية',     1),
('شكل ظاهري وتصنيف نبات', 1),
('كيمياء عضوية',           1),
('لغات برمجة',             1),
('حسبان (2)',              1),
('مهارات تواصل',           1),
('مهارات البحث العلمي',    1),
('حقوق إنسان',             1);

-- الفرقة الثانية
INSERT INTO subjects (name, grade) VALUES
('البرمجة في الفيزياء',       2),
('إحصاء واحتمالات',           2),
('هندسة البرمجيات',           2),
('البرمجة الشيئية (OOP)',     2),
('نظم قواعد بيانات',          2),
('هياكل بيانات وخوارزميات',  2),
('برمجة خطية',               2);

-- الفرقة الثالثة
INSERT INTO subjects (name, grade) VALUES
('تصميم قواعد البيانات', 3),
('الشبكات العصبية',      3),
('علم حيوان (1)',        3),
('الرسم بالحاسب',        3),
('هندسة تفاضلية',        3),
('دوال خاصة',            3),
('نظم تشغيل',            3);

-- الفرقة الرابعة
INSERT INTO subjects (name, grade) VALUES
('تحليل عددي (2)',            4),
('ديناميكا الموائع الحسابية', 4),
('ميكانيكا الكم',             4),
('شبكات حاسوبية متقدمة',      4),
('طرق رياضية',                4),
('تحليل دالي',                4),
('تصميم لغات برمجة',          4);
