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