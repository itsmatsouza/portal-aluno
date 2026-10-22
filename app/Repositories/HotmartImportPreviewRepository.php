<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Repositories;

use PDO;

class HotmartImportPreviewRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function snapshot(string $productUcode, string $email, string $transaction, ?string $classId): array
    {
        $course = $this->one('SELECT id, name, is_active FROM courses WHERE hotmart_product_ucode = ?', [$productUcode]);
        $user = $this->one('SELECT id FROM users WHERE email = ?', [$email]);
        return [
            'course' => $course,
            'user_id' => $user['id'] ?? null,
            'purchase' => $this->one('SELECT transaction_id, status, purchased_at FROM hotmart_purchases WHERE transaction_id = ?', [$transaction]),
            'enrollment' => $course && $user ? $this->one('SELECT status, hotmart_transaction_id, purchased_at, class_id, sync_pending FROM user_courses WHERE user_id = ? AND course_id = ?', [$user['id'], $course['id']]) : null,
            'class' => $course && $classId ? $this->one('SELECT id, name, hotmart_class_id, is_lifetime, access_days FROM course_classes WHERE course_id = ? AND hotmart_class_id = ?', [$course['id'], $classId]) : null,
        ];
    }

    private function one(string $sql, array $parameters): ?array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
