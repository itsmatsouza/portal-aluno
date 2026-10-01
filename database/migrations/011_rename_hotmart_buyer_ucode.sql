SET @buyer_id_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'hotmart_buyer_id');
SET @buyer_ucode_sql = IF(@buyer_id_exists = 1,
    'ALTER TABLE users CHANGE COLUMN hotmart_buyer_id hotmart_buyer_ucode VARCHAR(100) NULL, DROP INDEX uq_users_hotmart_buyer_id, ADD UNIQUE KEY uq_users_hotmart_buyer_ucode (hotmart_buyer_ucode)',
    'SELECT 1');
PREPARE buyer_ucode_statement FROM @buyer_ucode_sql;
EXECUTE buyer_ucode_statement;
DEALLOCATE PREPARE buyer_ucode_statement;
