<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Repositories;

use Leilabrito\PortalAluno\Core\Database;
use Leilabrito\PortalAluno\Models\UserCourse;
use Leilabrito\PortalAluno\Models\Course;
use PDO;

class UserCourseRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?UserCourse
    {
        $sql = "
            SELECT
                id,
                user_id,
                course_id,
                hotmart_transaction_id,
                status,
                purchased_at,
                access_expires_at,
                created_at,
                updated_at
            FROM user_courses
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

        return $this->mapToUserCourse($data);
    }

    /**
     * Retorna todos os cursos vinculados ao usuário.
     *
     * @return UserCourse[]
     */
    public function findByUserId(int $userId): array
    {
        $sql = "
            SELECT
                id,
                user_id,
                course_id,
                hotmart_transaction_id,
                status,
                purchased_at,
                access_expires_at,
                created_at,
                updated_at
            FROM user_courses
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
        ]);

        $userCourses = [];

        while ($data = $stmt->fetch()) {
            $userCourses[] = $this->mapToUserCourse($data);
        }

        return $userCourses;
    }

    public function findByUserAndCourse(
        int $userId,
        int $courseId
    ): ?UserCourse {
        $sql = "
            SELECT
                id,
                user_id,
                course_id,
                hotmart_transaction_id,
                status,
                purchased_at,
                access_expires_at,
                created_at,
                updated_at
            FROM user_courses
            WHERE user_id = :user_id
              AND course_id = :course_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
            'course_id' => $courseId,
        ]);

        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->mapToUserCourse($data);
    }

    public function hasAccess(
        int $userId,
        int $courseId
    ): bool {
        $sql = "
            SELECT 1
            FROM user_courses
            WHERE user_id = :user_id
              AND course_id = :course_id
              AND status = 'ACTIVE'
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
            'course_id' => $courseId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * @return UserCourse[]
     */
    public function findActiveByUserId(int $userId): array
    {
        $sql = "
            SELECT
                id,
                user_id,
                course_id,
                hotmart_transaction_id,
                status,
                purchased_at,
                access_expires_at,
                created_at,
                updated_at
            FROM user_courses
            WHERE user_id = :user_id
              AND status = 'ACTIVE'
            ORDER BY created_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
        ]);

        $userCourses = [];

        while ($data = $stmt->fetch()) {
            $userCourses[] = $this->mapToUserCourse($data);
        }

        return $userCourses;
    }

    public function countAll(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM user_courses
        ";

        return (int) $this->db
            ->query($sql)
            ->fetchColumn();
    }

    private function mapToUserCourse(array $data): UserCourse
    {
        return new UserCourse(
            (int) $data['id'],
            (int) $data['user_id'],
            (int) $data['course_id'],
            $data['hotmart_transaction_id'],
            $data['status'],
            $data['purchased_at'],
            $data['access_expires_at'],
            $data['created_at'],
            $data['updated_at']
        );
    }

    public function findActiveCoursesByUser(int $userId): array
    {
    $sql = "
        SELECT
            c.id,
            c.name,
            c.description,
            c.hotmart_product_ucode,
            c.is_active,
            c.deleted_at,
            c.created_at,
            c.updated_at
        FROM user_courses uc
        INNER JOIN courses c
            ON c.id = uc.course_id
        WHERE uc.user_id = :user_id
          AND uc.status = 'ACTIVE'
          AND c.is_active = 1
          AND c.deleted_at IS NULL
        ORDER BY c.name ASC
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        'user_id' => $userId,
    ]);

    $courses = [];

    while ($data = $stmt->fetch()) {
        $courses[] = new Course(
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

        return $courses;
    }
}
