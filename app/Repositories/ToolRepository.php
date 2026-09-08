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
