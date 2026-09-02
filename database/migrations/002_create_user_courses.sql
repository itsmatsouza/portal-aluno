CREATE TABLE user_courses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    user_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,

    hotmart_transaction_id VARCHAR(150) NULL,

    status ENUM(
        'ACTIVE',
        'CANCELLED',
        'REFUNDED',
        'CHARGEBACK',
        'EXPIRED',
        'SUSPENDED'
    ) NOT NULL DEFAULT 'ACTIVE',

    purchased_at DATETIME NULL,
    access_expires_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_user_course (
        user_id,
        course_id
    ),

    UNIQUE KEY uq_hotmart_transaction (
        hotmart_transaction_id
    ),

    KEY idx_user_courses_user (
        user_id
    ),

    KEY idx_user_courses_course (
        course_id
    ),

    KEY idx_user_courses_status (
        status
    ),

    CONSTRAINT fk_user_courses_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_user_courses_course
        FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;