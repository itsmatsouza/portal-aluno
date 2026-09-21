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

    public function findDetailsByUserId(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT c.name AS course_name, uc.status, uc.hotmart_transaction_id,
                   uc.purchased_at, uc.access_expires_at
            FROM user_courses uc
            LEFT JOIN courses c ON c.id = uc.course_id
            WHERE uc.user_id = :user_id
            ORDER BY uc.created_at DESC, uc.id DESC
        ');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
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
              AND (access_expires_at IS NULL OR access_expires_at > NOW())
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
              AND (access_expires_at IS NULL OR access_expires_at > NOW())
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

    public function countByStatus(string $status): int
    {
        $allowedStatuses = [
            'ACTIVE',
            'CANCELLED',
            'REFUNDED',
            'CHARGEBACK',
            'EXPIRED',
            'SUSPENDED',
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new \InvalidArgumentException(
                'Status de vínculo inválido.'
            );
        }

        $sql = "
            SELECT COUNT(*)
            FROM user_courses
            WHERE status = :status
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'status' => $status,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function findAdminPaginated(int $page = 1, string $search = '', string $status = '', ?int $courseId = null): array
    {
        $where = ['1 = 1'];
        $params = [];
        if ($search !== '') {
            $where[] = '(u.name LIKE :name OR u.email LIKE :email OR uc.hotmart_transaction_id LIKE :transaction)';
            $params = ['name' => '%' . $search . '%', 'email' => '%' . $search . '%', 'transaction' => '%' . $search . '%'];
        }
        if ($status !== '') {
            $where[] = 'uc.status = :status';
            $params['status'] = $status;
        }
        if ($courseId !== null) {
            $where[] = 'uc.course_id = :course_id';
            $params['course_id'] = $courseId;
        }
        $whereSql = implode(' AND ', $where);
        $joins = 'FROM user_courses uc LEFT JOIN users u ON u.id = uc.user_id LEFT JOIN courses c ON c.id = uc.course_id';
        $count = $this->db->prepare("SELECT COUNT(*) {$joins} WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $totalPages = max(1, (int) ceil($total / 20));
        $page = max(1, min($page, $totalPages));
        $stmt = $this->db->prepare("SELECT uc.*, u.name AS user_name, u.email AS user_email,
            u.deleted_at AS user_deleted_at, u.is_active AS user_active,
            c.name AS course_name, c.deleted_at AS course_deleted_at, c.is_active AS course_active
            {$joins} WHERE {$whereSql} ORDER BY uc.created_at DESC, uc.id DESC LIMIT 20 OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', ($page - 1) * 20, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'total_pages' => $totalPages];
    }

    public function findAdminDetails(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT uc.*, u.name AS user_name, u.email AS user_email,
            u.deleted_at AS user_deleted_at, u.is_active AS user_active,
            c.name AS course_name, c.deleted_at AS course_deleted_at, c.is_active AS course_active
            FROM user_courses uc LEFT JOIN users u ON u.id = uc.user_id
            LEFT JOIN courses c ON c.id = uc.course_id WHERE uc.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result === false ? null : $result;
    }

    public function findAdminCourseOptions(): array
    {
        return $this->db->query('SELECT c.id, c.name, c.deleted_at FROM courses c
            WHERE EXISTS (SELECT 1 FROM user_courses uc WHERE uc.course_id = c.id)
            ORDER BY c.name ASC, c.id ASC')->fetchAll();
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
          AND (uc.access_expires_at IS NULL OR uc.access_expires_at > NOW())
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
