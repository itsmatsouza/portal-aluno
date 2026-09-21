<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Repositories;

use PDO;

class HotmartRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function lockCourse(string $ucode): ?array
    {
        $stmt = $this->db->prepare('SELECT id, access_days FROM courses WHERE hotmart_product_ucode = ? FOR UPDATE');
        $stmt->execute([$ucode]);
        $course = $stmt->fetch();
        return $course === false ? null : $course;
    }

    public function recordEvent(array $event, string $transaction): bool
    {
        $stmt = $this->db->prepare('INSERT INTO hotmart_events (event_id, event_type, transaction_id, occurred_at)
            VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE event_id = event_id');
        $stmt->execute([$event['id'], $event['event'], $transaction, $event['creation_date']]);
        return $stmt->rowCount() === 1;
    }

    public function outcome(string $id, string $outcome): void
    {
        $stmt = $this->db->prepare('UPDATE hotmart_events SET outcome = ? WHERE event_id = ?');
        $stmt->execute([$outcome, $id]);
    }

    public function purchase(string $transaction): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM hotmart_purchases WHERE transaction_id = ? FOR UPDATE');
        $stmt->execute([$transaction]);
        return $stmt->fetch() ?: null;
    }

    public function buyer(string $email, string $name): int
    {
        // Nunca altere senha, papel ou bloqueio de uma conta existente.
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, role, hotmart_email)
            VALUES (?, ?, ?, 'ALUNO', ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)");
        $stmt->execute([$name, $email, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $email]);
        return (int) $this->db->lastInsertId();
    }

    public function legacyAccess(string $transaction): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_courses WHERE hotmart_transaction_id = ? FOR UPDATE');
        $stmt->execute([$transaction]);
        return $stmt->fetch() ?: null;
    }

    public function savePurchase(string $transaction, int $courseId, ?int $userId, string $status, int $time, ?string $purchasedAt, ?string $expiresAt): void
    {
        $stmt = $this->db->prepare('INSERT INTO hotmart_purchases
            (transaction_id, course_id, user_id, status, occurred_at, purchased_at, access_expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), status = VALUES(status),
                occurred_at = VALUES(occurred_at), purchased_at = VALUES(purchased_at), access_expires_at = VALUES(access_expires_at)');
        $stmt->execute([$transaction, $courseId, $userId, $status, $time, $purchasedAt, $expiresAt]);
    }

    public function activePurchase(int $userId, int $courseId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM hotmart_purchases WHERE user_id = :user_id AND course_id = :course_id
            AND status = 'ACTIVE' AND (access_expires_at IS NULL OR access_expires_at > :now)
            ORDER BY (access_expires_at IS NULL) DESC, access_expires_at DESC, occurred_at DESC LIMIT 1");
        $stmt->execute(['user_id' => $userId, 'course_id' => $courseId, 'now' => date('Y-m-d H:i:s')]);
        return $stmt->fetch() ?: null;
    }

    public function access(int $userId, int $courseId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_courses WHERE user_id = ? AND course_id = ? FOR UPDATE');
        $stmt->execute([$userId, $courseId]);
        return $stmt->fetch() ?: null;
    }

    public function saveAccess(int $userId, int $courseId, string $transaction, string $status, ?string $purchasedAt, ?string $expiresAt): void
    {
        $stmt = $this->db->prepare('INSERT INTO user_courses
            (user_id, course_id, hotmart_transaction_id, status, purchased_at, access_expires_at) VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE hotmart_transaction_id = VALUES(hotmart_transaction_id),
                status = VALUES(status), purchased_at = VALUES(purchased_at), access_expires_at = VALUES(access_expires_at)');
        $stmt->execute([$userId, $courseId, $transaction, $status, $purchasedAt, $expiresAt]);
    }
}
