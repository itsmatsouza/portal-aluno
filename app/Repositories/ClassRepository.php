<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Repositories;

use PDO;
use InvalidArgumentException;

class ClassRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function listing(string $search = '', int $page = 1): array
    {
        $params = ['search' => '%' . $search . '%', 'course' => '%' . $search . '%', 'hotmart' => '%' . $search . '%'];
        $from = 'FROM course_classes cc JOIN courses c ON c.id = cc.course_id
            WHERE (cc.name LIKE :search OR c.name LIKE :course OR cc.hotmart_class_id LIKE :hotmart)';
        $count = $this->db->prepare('SELECT COUNT(*) ' . $from);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / 20));
        $page = max(1, min($page, $pages));
        $stmt = $this->db->prepare('SELECT cc.*, c.name AS course_name,
            (SELECT COUNT(*) FROM class_tools ct WHERE ct.class_id = cc.id) AS tool_count ' . $from . '
            ORDER BY cc.id DESC LIMIT 20 OFFSET ' . (($page - 1) * 20));
        $stmt->execute($params);
        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'total_pages' => $pages];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT cc.*, c.name AS course_name FROM course_classes cc
            JOIN courses c ON c.id = cc.course_id WHERE cc.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function tools(?int $id): array
    {
        $stmt = $this->db->prepare('SELECT t.id, t.name, t.is_active, ct.tool_id IS NOT NULL AS linked
            FROM tools t LEFT JOIN class_tools ct ON ct.tool_id = t.id AND ct.class_id = ?
            WHERE t.deleted_at IS NULL ORDER BY t.name, t.id');
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function save(?int $id, array $values, array $tools): int
    {
        $this->db->beginTransaction();
        try {
            $course = $this->db->prepare('SELECT id, is_active FROM courses WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
            $course->execute([$values['course_id']]);
            $courseData = $course->fetch();
            if ($courseData === false) {
                throw new InvalidArgumentException('Curso não encontrado.');
            }
            if ($id === null && !(bool) $courseData['is_active']) {
                throw new InvalidArgumentException('Curso inativo na Hotmart. Não é possível criar novas turmas.');
            }
            if ($id !== null) {
                $existing = $this->find($id);
                if ($existing === null || (int) $existing['course_id'] !== $values['course_id']
                    || $existing['hotmart_class_id'] !== $values['hotmart_class_id']) {
                    throw new InvalidArgumentException('Curso e ID Hotmart não podem mudar após o cadastro da turma.');
                }
            }
            if ($tools !== []) {
                $stmt = $this->db->prepare('SELECT id FROM tools WHERE deleted_at IS NULL AND id IN (' . implode(',', array_fill(0, count($tools), '?')) . ') FOR UPDATE');
                $stmt->execute($tools);
                if (count($stmt->fetchAll()) !== count($tools)) {
                    throw new InvalidArgumentException('Ferramenta não encontrada. Recarregue a página.');
                }
            }
            if ($id === null) {
                $stmt = $this->db->prepare('INSERT INTO course_classes (course_id, name, hotmart_class_id, is_lifetime, access_days) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$values['course_id'], $values['name'], $values['hotmart_class_id'], $values['is_lifetime'], $values['access_days']]);
                $id = (int) $this->db->lastInsertId();
            } else {
                $stmt = $this->db->prepare('UPDATE course_classes SET name = ?, is_lifetime = ?, access_days = ? WHERE id = ?');
                $stmt->execute([$values['name'], $values['is_lifetime'], $values['access_days'], $id]);
            }
            $this->db->prepare('DELETE FROM class_tools WHERE class_id = ?')->execute([$id]);
            $link = $this->db->prepare('INSERT INTO class_tools (class_id, tool_id) VALUES (?, ?)');
            foreach ($tools as $toolId) {
                $link->execute([$id, $toolId]);
            }
            // O Club já confirmou estas matrículas. O cadastro resolve apenas a turma desconhecida.
            $this->db->prepare('UPDATE user_courses SET class_id = ?, sync_error = NULL
                WHERE course_id = ? AND hotmart_class_id = ? AND class_id IS NULL AND sync_pending = 0')
                ->execute([$id, $values['course_id'], $values['hotmart_class_id']]);
            $this->db->commit();
            return $id;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
}
