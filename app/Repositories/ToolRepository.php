<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Repositories;

use Leilabrito\PortalAluno\Core\Database;
use Leilabrito\PortalAluno\Models\Tool;
use PDO;

class ToolRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?Tool
    {
        $sql = "
            SELECT
                id,
                name,
                description,
                slug,
                url,
                is_active,
                deleted_at,
                created_at,
                updated_at
            FROM tools
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id,
        ]);

        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->mapToTool($data);
    }

    public function findBySlug(string $slug): ?Tool
    {
        $sql = "
            SELECT
                id,
                name,
                description,
                slug,
                url,
                is_active,
                deleted_at,
                created_at,
                updated_at
            FROM tools
            WHERE slug = :slug
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'slug' => $slug,
        ]);

        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->mapToTool($data);
    }

    public function findActiveByCourseId(int $courseId): array
    {
        $sql = "
            SELECT DISTINCT
                t.id,
                t.name,
                t.description,
                t.slug,
                t.url,
                t.is_active,
                t.deleted_at,
                t.created_at,
                t.updated_at
            FROM course_tools ct
            INNER JOIN tools t
                ON t.id = ct.tool_id
            WHERE ct.course_id = :course_id
              AND ct.is_active = 1
              AND t.is_active = 1
              AND t.deleted_at IS NULL
            ORDER BY t.name ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'course_id' => $courseId,
        ]);

        $tools = [];

        while ($data = $stmt->fetch()) {
            $tools[] = $this->mapToTool($data);
        }

        return $tools;
    }

    public function findActiveByCourseIds(array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }

        $courseIds = array_values(
            array_unique(
                array_map('intval', $courseIds)
            )
        );

        $placeholders = [];

        foreach ($courseIds as $index => $courseId) {
            $placeholders[] = ':course_id_' . $index;
        }

        $sql = "
            SELECT DISTINCT
                ct.course_id,
                t.id,
                t.name,
                t.description,
                t.slug,
                t.url,
                t.is_active,
                t.deleted_at,
                t.created_at,
                t.updated_at
            FROM course_tools ct
            INNER JOIN tools t
                ON t.id = ct.tool_id
            WHERE ct.course_id IN (" . implode(', ', $placeholders) . ")
            AND ct.is_active = 1
            AND t.is_active = 1
            AND t.deleted_at IS NULL
            ORDER BY ct.course_id ASC, t.name ASC
        ";

        $stmt = $this->db->prepare($sql);

        $params = [];

        foreach ($courseIds as $index => $courseId) {
            $params['course_id_' . $index] = $courseId;
        }

        $stmt->execute($params);

        $tools = [];

        while ($data = $stmt->fetch()) {
            $tools[] = [
                'course_id' => (int) $data['course_id'],
                'tool' => $this->mapToTool($data),
            ];
        }

        return $tools;
    }

    public function hasActiveAccessForUser(
        int $userId,
        int $toolId
    ): bool {
        $sql = "
            SELECT 1
            FROM user_courses uc
            INNER JOIN courses c
                ON c.id = uc.course_id
            INNER JOIN course_tools ct
                ON ct.course_id = uc.course_id
            INNER JOIN tools t
                ON t.id = ct.tool_id
            WHERE uc.user_id = :user_id
            AND uc.status = 'ACTIVE'
          AND (uc.access_expires_at IS NULL OR uc.access_expires_at > NOW())
            AND c.is_active = 1
            AND c.deleted_at IS NULL
            AND ct.tool_id = :tool_id
            AND ct.is_active = 1
            AND t.is_active = 1
            AND t.deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
            'tool_id' => $toolId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function findForCourseAdmin(int $courseId): array
    {
        $stmt = $this->db->prepare('SELECT t.id, t.name, t.description, t.slug, t.is_active,
                COALESCE(ct.is_active, 0) AS linked
            FROM tools t
            LEFT JOIN course_tools ct ON ct.tool_id = t.id AND ct.course_id = :course_id
            WHERE t.deleted_at IS NULL
            ORDER BY t.name ASC, t.id ASC');
        $stmt->execute(['course_id' => $courseId]);

        return $stmt->fetchAll();
    }

    public function syncCourseTools(int $courseId, array $toolIds): void
    {
        $this->db->beginTransaction();
        try {
            // Serializa alterações da seleção de ferramentas do mesmo curso.
            $course = $this->db->prepare('SELECT id FROM courses WHERE id = :id AND deleted_at IS NULL FOR UPDATE');
            $course->execute(['id' => $courseId]);
            if ($course->fetchColumn() === false) {
                throw new \InvalidArgumentException('Curso não encontrado.');
            }
            if ($toolIds !== []) {
                $placeholders = implode(',', array_fill(0, count($toolIds), '?'));
                $tools = $this->db->prepare("SELECT id FROM tools WHERE deleted_at IS NULL AND id IN ({$placeholders}) FOR UPDATE");
                $tools->execute($toolIds);
                if (count($tools->fetchAll()) !== count($toolIds)) {
                    throw new \InvalidArgumentException('Uma ferramenta selecionada não está mais disponível. Recarregue a página.');
                }
            }
            $disable = $this->db->prepare('UPDATE course_tools SET is_active = 0, updated_at = NOW()
                WHERE course_id = :course_id AND is_active = 1');
            $disable->execute(['course_id' => $courseId]);
            $enable = $this->db->prepare('INSERT INTO course_tools (course_id, tool_id, is_active)
                VALUES (:course_id, :tool_id, 1)
                ON DUPLICATE KEY UPDATE is_active = 1, updated_at = NOW()');
            foreach ($toolIds as $toolId) {
                $enable->execute(['course_id' => $courseId, 'tool_id' => $toolId]);
            }
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    public function findPaginated(int $page = 1, string $search = '', string $status = ''): array
    {
        $where = ['t.deleted_at IS NULL'];
        $params = [];
        if ($search !== '') {
            $where[] = '(t.name LIKE :name OR t.slug LIKE :slug)';
            $params = ['name' => '%' . $search . '%', 'slug' => '%' . $search . '%'];
        }
        if (in_array($status, ['active', 'inactive'], true)) {
            $where[] = 't.is_active = :active';
            $params['active'] = $status === 'active' ? 1 : 0;
        }
        $whereSql = implode(' AND ', $where);
        $count = $this->db->prepare("SELECT COUNT(*) FROM tools t WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / 20));
        $page = max(1, min($page, $pages));
        $stmt = $this->db->prepare("SELECT t.*,
            (SELECT COUNT(*) FROM course_tools ct JOIN courses c ON c.id = ct.course_id
                WHERE ct.tool_id = t.id AND ct.is_active = 1 AND c.deleted_at IS NULL) AS course_count
            FROM tools t WHERE {$whereSql} ORDER BY t.name ASC, t.id ASC LIMIT 20 OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', ($page - 1) * 20, PDO::PARAM_INT);
        $stmt->execute();
        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = ['tool' => $this->mapToTool($row), 'course_count' => (int) $row['course_count']];
        }

        return ['items' => $items, 'total' => $total, 'page' => $page, 'total_pages' => $pages];
    }

    public function findLinkedCourses(int $toolId): array
    {
        $stmt = $this->db->prepare('SELECT c.id, c.name, c.is_active FROM courses c
            JOIN course_tools ct ON ct.course_id = c.id
            WHERE ct.tool_id = :tool_id AND ct.is_active = 1 AND c.deleted_at IS NULL
            ORDER BY c.name ASC, c.id ASC');
        $stmt->execute(['tool_id' => $toolId]);

        return $stmt->fetchAll();
    }

    public function updateMetadata(int $id, string $name, ?string $description): void
    {
        $stmt = $this->db->prepare('UPDATE tools SET name = :name, description = :description,
            updated_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id, 'name' => $name, 'description' => $description]);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->db->prepare('UPDATE tools SET is_active = :active, updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id, 'active' => (int) $active]);
    }

    private function mapToTool(array $data): Tool
    {
        return new Tool(
            (int) $data['id'],
            $data['name'],
            $data['description'],
            $data['slug'],
            $data['url'],
            (bool) $data['is_active'],
            $data['deleted_at'],
            $data['created_at'],
            $data['updated_at']
        );
    }
}
