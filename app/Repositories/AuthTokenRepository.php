<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Repositories;

use DateTimeImmutable;
use PDO;

class AuthTokenRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function create(
        int $userId,
        string $tokenHash,
        string $type,
        DateTimeImmutable $expiresAt
    ): void {
        $sql = "
            INSERT INTO auth_tokens (
                user_id,
                token_hash,
                type,
                expires_at
            )
            VALUES (
                :user_id,
                :token_hash,
                :type,
                :expires_at
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'type' => $type,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findValid(
        string $tokenHash,
        string $type
    ): ?array {
        $sql = "
            SELECT
                id,
                user_id,
                token_hash,
                type,
                expires_at,
                used_at,
                created_at
            FROM auth_tokens
            WHERE token_hash = :token_hash
              AND type = :type
              AND used_at IS NULL
              AND expires_at > NOW()
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'token_hash' => $tokenHash,
            'type' => $type,
        ]);

        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    public function markAsUsed(int $id): void
    {
        $sql = "
            UPDATE auth_tokens
            SET used_at = NOW()
            WHERE id = :id
              AND used_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id,
        ]);
    }

    public function invalidateActiveTokens(
        int $userId,
        string $type
    ): void {
        $sql = "
            UPDATE auth_tokens
            SET used_at = NOW()
            WHERE user_id = :user_id
              AND type = :type
              AND used_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
        ]);
    }

    public function deleteExpired(): int
    {
        $sql = "
            DELETE FROM auth_tokens
            WHERE expires_at <= NOW()
        ";

        return $this->db->exec($sql);
    }
}
