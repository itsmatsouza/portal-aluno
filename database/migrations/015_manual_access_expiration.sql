-- Vencimento individual de concessões manuais.  matrículas comerciais mantêm a regra da turma.
CREATE OR REPLACE VIEW enrollment_access AS
SELECT uc.id, uc.user_id, uc.course_id, uc.hotmart_transaction_id,
    CASE
        WHEN uc.status <> 'ACTIVE' THEN uc.status
        WHEN cc.id IS NULL THEN 'PENDING'
        WHEN uc.is_manual = 1 AND uc.access_expires_at IS NOT NULL THEN
            CASE WHEN uc.access_expires_at <= NOW() THEN 'EXPIRED' ELSE 'ACTIVE' END
        WHEN uc.is_manual = 0 AND (uc.sync_pending = 1 OR uc.club_status IS NULL) THEN 'PENDING'
        WHEN uc.is_manual = 0 AND uc.club_status <> 'ACTIVE' THEN 'SUSPENDED'
        WHEN cc.is_lifetime = 0 AND (cc.access_days IS NULL OR uc.purchased_at IS NULL) THEN 'PENDING'
        WHEN cc.is_lifetime = 0 AND DATE_ADD(uc.purchased_at, INTERVAL cc.access_days DAY) <= NOW() THEN 'EXPIRED'
        ELSE 'ACTIVE'
    END AS status,
    uc.purchased_at,
    CASE WHEN uc.is_manual = 1 AND uc.access_expires_at IS NOT NULL THEN uc.access_expires_at
        WHEN cc.is_lifetime = 1 THEN NULL ELSE DATE_ADD(uc.purchased_at, INTERVAL cc.access_days DAY) END AS access_expires_at, uc.created_at, uc.updated_at,
    uc.class_id, uc.hotmart_class_id, uc.club_status, uc.sync_pending, uc.sync_version,
    uc.synced_at, uc.sync_error, cc.name AS class_name,
    CASE WHEN uc.is_manual = 1 AND uc.access_expires_at IS NOT NULL THEN 0 ELSE cc.is_lifetime END AS is_lifetime, cc.access_days,
    uc.status AS purchase_status, uc.is_manual
FROM user_courses uc
LEFT JOIN course_classes cc ON cc.id = uc.class_id AND cc.course_id = uc.course_id;

