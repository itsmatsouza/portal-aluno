CREATE OR REPLACE VIEW enrollment_access AS
SELECT uc.id, uc.user_id, uc.course_id, uc.hotmart_transaction_id,
    CASE
        WHEN uc.status <> 'ACTIVE' THEN uc.status
        WHEN uc.sync_pending = 1 OR cc.id IS NULL OR uc.club_status IS NULL THEN 'PENDING'
        WHEN uc.club_status <> 'ACTIVE' THEN 'SUSPENDED'
        WHEN cc.is_lifetime = 0 AND (cc.expires_at IS NULL OR cc.expires_at <= NOW()) THEN 'EXPIRED'
        ELSE 'ACTIVE'
    END AS status,
    uc.purchased_at, cc.expires_at AS access_expires_at, uc.created_at, uc.updated_at,
    uc.class_id, uc.hotmart_class_id, uc.club_status, uc.sync_pending, uc.sync_version,
    uc.synced_at, uc.sync_error, cc.name AS class_name, cc.is_lifetime,
    uc.status AS purchase_status
FROM user_courses uc
LEFT JOIN course_classes cc ON cc.id = uc.class_id AND cc.course_id = uc.course_id;
