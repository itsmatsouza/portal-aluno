CREATE TABLE IF NOT EXISTS course_classes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    hotmart_class_id VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    is_lifetime TINYINT(1) NOT NULL DEFAULT 0,
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_hotmart (course_id, hotmart_class_id),
    UNIQUE KEY uq_class_course (id, course_id),
    CONSTRAINT fk_class_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT,
    CONSTRAINT chk_class_expiration CHECK ((is_lifetime = 1 AND expires_at IS NULL) OR (is_lifetime = 0 AND expires_at IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS class_tools (
    class_id BIGINT UNSIGNED NOT NULL,
    tool_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (class_id, tool_id),
    CONSTRAINT fk_class_tool_class FOREIGN KEY (class_id) REFERENCES course_classes(id) ON DELETE RESTRICT,
    CONSTRAINT fk_class_tool_tool FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
