ALTER TABLE courses ADD COLUMN access_days INT UNSIGNED NULL;

CREATE TABLE hotmart_events (
    event_id VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    event_type VARCHAR(80) NOT NULL,
    transaction_id VARCHAR(150) NOT NULL,
    occurred_at BIGINT UNSIGNED NOT NULL,
    outcome VARCHAR(40) NOT NULL DEFAULT 'processed',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hotmart_purchases (
    transaction_id VARCHAR(150) NOT NULL PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    status VARCHAR(20) NOT NULL,
    occurred_at BIGINT UNSIGNED NOT NULL,
    purchased_at DATETIME NULL,
    access_expires_at DATETIME NULL,
    KEY idx_hotmart_access (user_id, course_id, status),
    CONSTRAINT fk_hotmart_course FOREIGN KEY (course_id) REFERENCES courses(id),
    CONSTRAINT fk_hotmart_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
