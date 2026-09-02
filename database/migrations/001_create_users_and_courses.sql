CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,

    password_hash VARCHAR(255) NOT NULL,

    role ENUM('ADMIN', 'ALUNO') NOT NULL DEFAULT 'ALUNO',

    hotmart_buyer_id VARCHAR(100) NULL,
    hotmart_email VARCHAR(255) NULL,

    is_active TINYINT(1) NOT NULL DEFAULT 1,

    deleted_at DATETIME NULL,

    last_login_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_hotmart_buyer_id (hotmart_buyer_id),

    KEY idx_users_active (is_active),
    KEY idx_users_deleted_at (deleted_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE courses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    name VARCHAR(200) NOT NULL,

    description TEXT NULL,

    hotmart_product_ucode VARCHAR(100) NULL,

    is_active TINYINT(1) NOT NULL DEFAULT 1,

    deleted_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_courses_hotmart_product_ucode (
        hotmart_product_ucode
    ),

    KEY idx_courses_active (is_active),
    KEY idx_courses_deleted_at (deleted_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;