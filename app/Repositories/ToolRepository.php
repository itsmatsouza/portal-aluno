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

    public function findActiveForUser(int $userId, array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }
        $ids = array_values(array_unique(array_map('intval', $courseIds)));
        $stmt = $this->db->prepare("SELECT DISTINCT uc.course_id, t.*
            FROM enrollment_access uc
            JOIN courses c ON c.id = uc.course_id
            JOIN class_tools ct ON ct.class_id = uc.class_id
            JOIN tools t ON t.id = ct.tool_id
            WHERE uc.user_id = ? AND uc.status = 'ACTIVE'
                AND c.deleted_at IS NULL
                AND t.is_active = 1 AND t.deleted_at IS NULL
                AND uc.course_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")
            ORDER BY uc.course_id, t.name");
        $stmt->execute([$userId, ...$ids]);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[] = ['course_id' => (int) $row['course_id'], 'tool' => $this->mapToTool($row)];
        }
        return $result;
    }

    public function hasActiveAccessForUser(int $userId, int $toolId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM enrollment_access uc
            JOIN courses c ON c.id = uc.course_id
            JOIN class_tools ct ON ct.class_id = uc.class_id
            JOIN tools t ON t.id = ct.tool_id
            WHERE uc.user_id = ? AND ct.tool_id = ? AND uc.status = 'ACTIVE'
                AND c.deleted_at IS NULL
                AND t.is_active = 1 AND t.deleted_at IS NULL LIMIT 1");
        $stmt->execute([$userId, $toolId]);
        return $stmt->fetchColumn() !== false;
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
            (SELECT COUNT(*) FROM class_tools ct WHERE ct.tool_id = t.id) AS class_count
            FROM tools t WHERE {$whereSql} ORDER BY t.name ASC, t.id ASC LIMIT 20 OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', ($page - 1) * 20, PDO::PARAM_INT);
        $stmt->execute();
        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = ['tool' => $this->mapToTool($row), 'class_count' => (int) $row['class_count']];
        }

        return ['items' => $items, 'total' => $total, 'page' => $page, 'total_pages' => $pages];
    }

    public function findLinkedClasses(int $toolId): array
    {
        $stmt = $this->db->prepare('SELECT cc.id, cc.name, c.name AS course_name FROM course_classes cc
            JOIN courses c ON c.id = cc.course_id JOIN class_tools ct ON ct.class_id = cc.id
            WHERE ct.tool_id = ? ORDER BY c.name, cc.name');
        $stmt->execute([$toolId]);
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
