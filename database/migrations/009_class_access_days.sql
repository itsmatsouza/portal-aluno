SET @class_days_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'course_classes' AND COLUMN_NAME = 'access_days');
SET @class_days_sql = IF(@class_days_exists = 0,
    CONCAT('ALTER TABLE course_classes ', IF(VERSION() LIKE '%MariaDB%', 'DROP CONSTRAINT ', 'DROP CHECK '),
        'chk_class_expiration, ADD COLUMN access_days INT UNSIGNED NULL, ADD CONSTRAINT chk_class_duration CHECK ((is_lifetime = 1 AND access_days IS NULL) OR (is_lifetime = 0 AND ((access_days IS NOT NULL AND access_days BETWEEN 1 AND 36500) OR (access_days IS NULL AND expires_at IS NOT NULL))))'),
    'SELECT 1');
PREPARE class_days_statement FROM @class_days_sql;
EXECUTE class_days_statement;
DEALLOCATE PREPARE class_days_statement;
