<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Repositories;

use Leilabrito\PortalAluno\Core\Database;
use Leilabrito\PortalAluno\Models\User;
use PDO;
use RuntimeException;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): ?User
    {
        $sql = "
            SELECT
                id,
                name,
                email,
                password_hash,
                role,
                hotmart_buyer_id,
                hotmart_email,
                is_active,
                deleted_at,
                last_login_at,
                created_at,
                updated_at
            FROM users
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'email' => $email,
        ]);

        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->mapToUser($data);
    }

    public function findById(int $id): ?User
    {
        $sql = "
            SELECT
                id,
                name,
                email,
                password_hash,
                role,
                hotmart_buyer_id,
                hotmart_email,
                is_active,
                deleted_at,
                last_login_at,
                created_at,
                updated_at
            FROM users
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

        return $this->mapToUser($data);
    }

    public function updateLastLogin(int $userId): void
    {
        $sql = "
            UPDATE users
            SET last_login_at = NOW()
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $userId,
        ]);
    }

    public function updatePassword(
        int $userId,
        string $passwordHash
    ): void {
        $sql = "
            UPDATE users
            SET
                password_hash = :password_hash,
                updated_at = NOW()
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => $userId,
        ]);
    }

    public function countAll(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM users
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
            FROM users
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
            FROM users
            WHERE is_active = 0
            AND deleted_at IS NULL
        ";

        return (int) $this->db
            ->query($sql)
            ->fetchColumn();
    }

    public function findPaginated(
        int $page = 1,
        int $perPage = 20,
        ?string $search = null,
        ?string $status = null,
        ?string $role = null
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $offset = ($page - 1) * $perPage;

        $where = [
            'deleted_at IS NULL',
        ];

        $params = [];

        if ($search !== null && trim($search) !== '') {
            $where[] = '(name LIKE :search OR email LIKE :search)';

            $params['search'] = '%' . trim($search) . '%';
        }

        if ($status === 'active') {
            $where[] = 'is_active = 1';
        }

        if ($status === 'inactive') {
            $where[] = 'is_active = 0';
        }

        if ($role === 'ADMIN' || $role === 'ALUNO') {
            $where[] = 'role = :role';

            $params['role'] = $role;
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "
            SELECT COUNT(*)
            FROM users
            WHERE {$whereSql}
        ";

        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);

        $total = (int) $countStmt->fetchColumn();

        $sql = "
            SELECT
                id,
                name,
                email,
                password_hash,
                role,
                hotmart_buyer_id,
                hotmart_email,
                is_active,
                deleted_at,
                last_login_at,
                created_at,
                updated_at
            FROM users
            WHERE {$whereSql}
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(
                ':' . $key,
                $value,
                PDO::PARAM_STR
            );
        }

        $stmt->bindValue(
            ':limit',
            $perPage,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $users = [];

        while ($data = $stmt->fetch()) {
            $users[] = $this->mapToUser($data);
        }

        return [
            'items' => $users,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    private function mapToUser(array $data): User
    {
        return new User(
            (int) $data['id'],
            $data['name'],
            $data['email'],
            $data['password_hash'],
            $data['role'],
            $data['hotmart_buyer_id'],
            $data['hotmart_email'],
            (bool) $data['is_active'],
            $data['deleted_at'],
            $data['last_login_at'],
            $data['created_at'],
            $data['updated_at']
        );
    }
}