-- Retira apenas a ferramenta de teste, preservando registros e vínculos para auditoria.
UPDATE tools
SET is_active = 0, deleted_at = COALESCE(deleted_at, CURRENT_TIMESTAMP)
WHERE slug = 'diagnostico-precificacao';
