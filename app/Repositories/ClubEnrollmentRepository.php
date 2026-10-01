<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Repositories;

use PDO;

class ClubEnrollmentRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function snapshot(int $userId, int $courseId): ?array
    {
        $stmt = $this->db->prepare('SELECT uc.*, COALESCE(u.hotmart_email, u.email) AS email,
            c.hotmart_product_ucode FROM user_courses uc JOIN users u ON u.id = uc.user_id
            JOIN courses c ON c.id = uc.course_id WHERE uc.user_id = ? AND uc.course_id = ?');
        $stmt->execute([$userId, $courseId]);
        return $stmt->fetch() ?: null;
    }

    public function batch(int $afterId, bool $pendingOnly, ?string $email = null): array
    {
        $sql = 'SELECT uc.id, uc.user_id, uc.course_id FROM user_courses uc
            JOIN users u ON u.id = uc.user_id WHERE uc.id > ?';
        $params = [$afterId];
        if ($pendingOnly) {
            $sql .= ' AND (uc.sync_pending = 1 OR uc.class_id IS NULL)';
        }
        if ($email !== null) {
            $sql .= ' AND LOWER(COALESCE(u.hotmart_email, u.email)) = ?';
            $params[] = strtolower(trim($email));
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY uc.id LIMIT 100');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function apply(array $snapshot, ?string $hotmartClassId, ?string $clubStatus, ?string $error): bool
    {
        $this->db->beginTransaction();
        try {
            // Mesma ordem de bloqueio do webhook e do cadastro de turmas.
            $stmt = $this->db->prepare('SELECT id FROM courses WHERE id = ? FOR UPDATE');
            $stmt->execute([$snapshot['course_id']]);
            $stmt = $this->db->prepare('SELECT sync_version FROM user_courses WHERE id = ? FOR UPDATE');
            $stmt->execute([$snapshot['id']]);
            if ((int) $stmt->fetchColumn() !== (int) $snapshot['sync_version']) {
                $this->db->rollBack();
                return false;
            }
            $classId = null;
            if ($error === null && $hotmartClassId !== null) {
                $stmt = $this->db->prepare('SELECT id FROM course_classes WHERE course_id = ? AND hotmart_class_id = ?');
                $stmt->execute([$snapshot['course_id'], $hotmartClassId]);
                $classId = $stmt->fetchColumn() ?: null;
            }
            $stmt = $this->db->prepare('UPDATE user_courses SET class_id = ?, hotmart_class_id = ?, club_status = ?,
                sync_pending = ?, sync_version = sync_version + 1, synced_at = ?, sync_error = ? WHERE id = ?');
            $stmt->execute([$classId, $hotmartClassId, $clubStatus, $error === null ? 0 : 1,
                $error === null ? date('Y-m-d H:i:s') : $snapshot['synced_at'],
                $error ?? ($classId === null ? 'Turma Hotmart ainda não cadastrada neste curso.' : null), $snapshot['id']]);
            $this->db->commit();
            return true;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
}
