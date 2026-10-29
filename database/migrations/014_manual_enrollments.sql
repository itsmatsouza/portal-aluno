SET @manual_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_courses' AND COLUMN_NAME = 'is_manual');
SET @manual_sql = IF(@manual_exists = 0, 'ALTER TABLE user_courses ADD COLUMN is_manual TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE manual_statement FROM @manual_sql;
EXECUTE manual_statement;
DEALLOCATE PREPARE manual_statement;

CREATE OR REPLACE VIEW enrollment_access AS
SELECT uc.id, uc.user_id, uc.course_id, uc.hotmart_transaction_id,
    CASE
        WHEN uc.status <> 'ACTIVE' THEN uc.status
        WHEN cc.id IS NULL THEN 'PENDING'
        WHEN uc.is_manual = 0 AND (uc.sync_pending = 1 OR uc.club_status IS NULL) THEN 'PENDING'
        WHEN uc.is_manual = 0 AND uc.club_status <> 'ACTIVE' THEN 'SUSPENDED'
        WHEN cc.is_lifetime = 0 AND (cc.access_days IS NULL OR uc.purchased_at IS NULL) THEN 'PENDING'
        WHEN cc.is_lifetime = 0 AND DATE_ADD(uc.purchased_at, INTERVAL cc.access_days DAY) <= NOW() THEN 'EXPIRED'
        ELSE 'ACTIVE'
    END AS status,
    uc.purchased_at,
    CASE WHEN cc.is_lifetime = 1 THEN NULL ELSE DATE_ADD(uc.purchased_at, INTERVAL cc.access_days DAY) END AS access_expires_at, uc.created_at, uc.updated_at,
    uc.class_id, uc.hotmart_class_id, uc.club_status, uc.sync_pending, uc.sync_version,
    uc.synced_at, uc.sync_error, cc.name AS class_name, cc.is_lifetime, cc.access_days,
    uc.status AS purchase_status, uc.is_manual
FROM user_courses uc
LEFT JOIN course_classes cc ON cc.id = uc.class_id AND cc.course_id = uc.course_id;

-- A turma reservada "admin" tem associação automática pelo papel atual da conta.
-- Matrículas comerciais permanecem intactas e continuam sob responsabilidade da Hotmart.
CREATE OR REPLACE VIEW tool_class_access AS
SELECT ea.user_id, ea.course_id, ea.class_id
FROM enrollment_access ea
JOIN users u ON u.id = ea.user_id
JOIN course_classes cc ON cc.id = ea.class_id AND cc.course_id = ea.course_id
WHERE ea.status = 'ACTIVE' AND u.is_active = 1 AND u.deleted_at IS NULL
    AND (cc.hotmart_class_id <> 'admin' OR ea.is_manual = 1)
UNION
SELECT u.id AS user_id, cc.course_id, cc.id AS class_id
FROM users u
JOIN course_classes cc ON cc.hotmart_class_id = 'admin'
WHERE u.role = 'ADMIN' AND u.is_active = 1 AND u.deleted_at IS NULL;
