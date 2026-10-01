<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Repositories;

use PDO;
use Leilabrito\PortalAluno\Services\PersonNameFormatter;

class HotmartRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function syncCourse(string $ucode, string $name, bool $active): void
    {
        if (!$active) {
            $stmt = $this->db->prepare('UPDATE courses SET name = ?, is_active = 0
                WHERE hotmart_product_ucode = ? AND deleted_at IS NULL');
            $stmt->execute([$name, $ucode]);
            return;
        }
        $stmt = $this->db->prepare('INSERT INTO courses (name, hotmart_product_ucode, is_active) VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = IF(deleted_at IS NULL, 1, is_active)');
        $stmt->execute([$name, $ucode]);
    }

    public function lockCourse(string $ucode): ?array
    {
        $stmt = $this->db->prepare('SELECT id FROM courses WHERE hotmart_product_ucode = ? FOR UPDATE');
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

    public function buyer(string $email, string $name, ?string $ucode = null): int
    {
        $name = PersonNameFormatter::format($name);
        if ($ucode !== null && !preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $ucode)) {
            throw new \InvalidArgumentException('Ucode do comprador Hotmart inválido.');
        }
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            // Resolve pelo e-mail, nunca pelo ucode de outra conta.
            // Preserva nome, senha, papel e bloqueios das contas existentes.
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, role, hotmart_email)
                VALUES (?, ?, ?, 'ALUNO', ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)");
            $stmt->execute([$name, $email, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $email]);
            $userId = (int) $this->db->lastInsertId();
            if ($ucode !== null) {
                $stmt = $this->db->prepare('SELECT hotmart_buyer_ucode FROM users WHERE id = ? FOR UPDATE');
                $stmt->execute([$userId]);
                $existing = $stmt->fetchColumn();
                if ($existing !== null && $existing !== $ucode) {
                    throw new \RuntimeException('Ucode do comprador diverge do vínculo existente.');
                }
                $stmt = $this->db->prepare('UPDATE users SET hotmart_buyer_ucode = ? WHERE id = ?');
                $stmt->execute([$ucode, $userId]);
            }
            if ($ownsTransaction) {
                $this->db->commit();
            }
            return $userId;
        } catch (\Throwable $error) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
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
            AND status = 'ACTIVE' ORDER BY purchased_at DESC, occurred_at DESC, transaction_id DESC LIMIT 1");
        $stmt->execute(['user_id' => $userId, 'course_id' => $courseId]);
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
                status = VALUES(status), purchased_at = VALUES(purchased_at), access_expires_at = NULL,
                class_id = NULL, club_status = NULL, sync_pending = 1, sync_version = sync_version + 1');
        $stmt->execute([$userId, $courseId, $transaction, $status, $purchasedAt, $expiresAt]);
    }
}
