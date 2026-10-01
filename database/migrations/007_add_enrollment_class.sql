ALTER TABLE user_courses
    ADD COLUMN class_id BIGINT UNSIGNED NULL,
    ADD COLUMN hotmart_class_id VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin NULL,
    ADD COLUMN club_status VARCHAR(40) NULL,
    ADD COLUMN sync_pending TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN sync_version BIGINT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN synced_at DATETIME NULL,
    ADD COLUMN sync_error VARCHAR(255) NULL,
    ADD CONSTRAINT fk_enrollment_class FOREIGN KEY (class_id, course_id) REFERENCES course_classes(id, course_id) ON DELETE RESTRICT;
