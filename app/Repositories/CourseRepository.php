<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Repositories;

use Leilabrito\PortalAluno\Core\Database;
use Leilabrito\PortalAluno\Models\Course;
use PDO;

class CourseRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?Course
    {
        $sql = "
            SELECT
                id,
                name,
                description,
                hotmart_product_ucode,
                is_active,
                deleted_at,
                created_at,
                updated_at
            FROM courses
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

        return $this->mapToCourse($data);
    }

    public function findByHotmartProductUcode(
        string $ucode
    ): ?Course {
        $sql = "
            SELECT
                id,
                name,
                description,
                hotmart_product_ucode,
                is_active,
                deleted_at,
                created_at,
                updated_at
            FROM courses
            WHERE hotmart_product_ucode = :ucode
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'ucode' => $ucode,
        ]);

        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->mapToCourse($data);
    }

    /**
     * @return Course[]
     */
    public function findAllAvailable(): array
    {
        $sql = "
            SELECT
                id,
                name,
                description,
                hotmart_product_ucode,
                is_active,
                deleted_at,
                created_at,
                updated_at
            FROM courses
            WHERE is_active = 1
              AND deleted_at IS NULL
            ORDER BY name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $courses = [];

        while ($data = $stmt->fetch()) {
            $courses[] = $this->mapToCourse($data);
        }

        return $courses;
    }

    public function countAll(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM courses
            WHERE deleted_at IS NULL
        ";

        return (int) $this->db
            ->query($sql)
            ->fetchColumn();
    }

    public function countActive(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM courses
            WHERE is_active = 1
            AND deleted_at IS NULL
        ";

        return (int) $this->db
            ->query($sql)
            ->fetchColumn();
    }

    public function countInactive(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM courses
            WHERE is_active = 0
            AND deleted_at IS NULL
        ";

        return (int) $this->db
            ->query($sql)
            ->fetchColumn();
    }

    public function findPaginated(int $page = 1, int $perPage = 20, string $search = '', string $status = ''): array
    {
        $perPage = max(1, min(100, $perPage));
        $where = ['deleted_at IS NULL'];
        $params = [];
        if ($search !== '') {
            $where[] = '(name LIKE :name OR hotmart_product_ucode LIKE :ucode)';
            $params = ['name' => '%' . $search . '%', 'ucode' => '%' . $search . '%'];
        }
        if (in_array($status, ['active', 'inactive'], true)) {
            $where[] = 'is_active = :active';
            $params['active'] = $status === 'active' ? 1 : 0;
        }
        $whereSql = implode(' AND ', $where);
        $count = $this->db->prepare("SELECT COUNT(*) FROM courses WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $stmt = $this->db->prepare("SELECT * FROM courses WHERE {$whereSql}
            ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map($this->mapToCourse(...), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'total_pages' => $totalPages,
        ];
    }

    public function create(string $name, ?string $description, ?string $ucode, bool $active, ?int $accessDays = null): int
    {
        $stmt = $this->db->prepare('INSERT INTO courses
            (name, description, hotmart_product_ucode, is_active, access_days)
            VALUES (:name, :description, :ucode, :active, :access_days)');
        $stmt->execute(['name' => $name, 'description' => $description, 'ucode' => $ucode, 'active' => (int) $active, 'access_days' => $accessDays]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name, ?string $description, ?string $ucode, bool $active, ?int $accessDays = null): void
    {
        $stmt = $this->db->prepare('UPDATE courses SET name = :name, description = :description,
            hotmart_product_ucode = :ucode, is_active = :active, access_days = :access_days, updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id, 'name' => $name, 'description' => $description, 'ucode' => $ucode, 'active' => (int) $active, 'access_days' => $accessDays]);
    }

    public function accessDays(int $id): ?int
    {
        $stmt = $this->db->prepare('SELECT access_days FROM courses WHERE id = ?');
        $stmt->execute([$id]);
        $days = $stmt->fetchColumn();
        return $days === null || $days === false ? null : (int) $days;
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->db->prepare('UPDATE courses SET is_active = :active, updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id, 'active' => (int) $active]);
    }

    private function mapToCourse(array $data): Course
    {
        return new Course(
            (int) $data['id'],
            $data['name'],
            $data['description'],
            $data['hotmart_product_ucode'],
            (bool) $data['is_active'],
            $data['deleted_at'],
            $data['created_at'],
            $data['updated_at']
        );
    }
}
