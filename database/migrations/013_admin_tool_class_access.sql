-- A turma reservada "admin" tem associação automática pelo papel atual da conta.
-- Matrículas comerciais permanecem intactas e continuam sob responsabilidade da Hotmart.
CREATE OR REPLACE VIEW tool_class_access AS
SELECT ea.user_id, ea.course_id, ea.class_id
FROM enrollment_access ea
JOIN users u ON u.id = ea.user_id
JOIN course_classes cc ON cc.id = ea.class_id AND cc.course_id = ea.course_id
WHERE ea.status = 'ACTIVE' AND u.is_active = 1 AND u.deleted_at IS NULL
    AND cc.hotmart_class_id <> 'admin'
UNION
SELECT u.id AS user_id, cc.course_id, cc.id AS class_id
FROM users u
JOIN course_classes cc ON cc.hotmart_class_id = 'admin'
WHERE u.role = 'ADMIN' AND u.is_active = 1 AND u.deleted_at IS NULL;
