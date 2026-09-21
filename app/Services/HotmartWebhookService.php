<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use InvalidArgumentException;
use Leilabrito\PortalAluno\Repositories\HotmartRepository;
use PDO;
use RuntimeException;
use Throwable;

class HotmartWebhookService
{
    private const STATUSES = [
        'PURCHASE_APPROVED' => 'ACTIVE',
        'PURCHASE_COMPLETE' => 'ACTIVE',
        'PURCHASE_REFUNDED' => 'REFUNDED',
        'PURCHASE_CHARGEBACK' => 'CHARGEBACK',
        'PURCHASE_CANCELED' => 'CANCELLED',
        'PURCHASE_PROTEST' => 'SUSPENDED',
        'PURCHASE_EXPIRED' => 'EXPIRED',
        'PURCHASE_DELAYED' => 'SUSPENDED',
    ];

    public function __construct(private PDO $db, private HotmartRepository $repository)
    {
    }

    public function process(array $event): string
    {
        $id = $this->string($event['id'] ?? null, 100);
        $type = $this->string($event['event'] ?? null, 80);
        if (($event['version'] ?? null) !== '2.0.0' || !is_int($event['creation_date'] ?? null)
            || $event['creation_date'] <= 0 || $event['creation_date'] > 253402300799000) {
            throw new InvalidArgumentException('Versão ou data do evento inválida.');
        }
        if (!isset(self::STATUSES[$type])) {
            return 'ignored_event';
        }
        $data = $event['data'] ?? [];
        $transaction = $this->string($data['purchase']['transaction'] ?? null, 150);
        $ucode = $this->string($data['product']['ucode'] ?? null, 100);
        $status = self::STATUSES[$type];
        $time = $event['creation_date'];

        $this->db->beginTransaction();
        try {
            // Serializa compras do mesmo curso, inclusive transações diferentes do mesmo aluno.
            $course = $this->repository->lockCourse($ucode);
            if ($course === null) {
                throw new RuntimeException('Produto Hotmart sem curso correspondente.');
            }
            $courseId = (int) $course['id'];
            if (!$this->repository->recordEvent($event, $transaction)) {
                $this->db->commit();
                return 'duplicate';
            }
            $previous = $this->repository->purchase($transaction);
            $legacy = $previous === null ? $this->repository->legacyAccess($transaction) : null;
            if ($legacy !== null && (int) $legacy['course_id'] !== $courseId) {
                throw new RuntimeException('Transação existente vinculada a outro curso.');
            }
            if ($previous !== null && (int) $previous['course_id'] !== $courseId) {
                throw new RuntimeException('Transação vinculada a outro produto.');
            }
            // Reembolso e chargeback são terminais para esta transação.
            if ($previous !== null && ($time < (int) $previous['occurred_at']
                || ($status === 'ACTIVE' && $time === (int) $previous['occurred_at'])
                || (in_array($previous['status'], ['REFUNDED', 'CHARGEBACK'], true)
                    && !in_array($status, ['REFUNDED', 'CHARGEBACK'], true)))) {
                $this->repository->outcome($id, 'ignored_stale');
                $this->db->commit();
                return 'ignored_stale';
            }
            $userId = $previous['user_id'] ?? $legacy['user_id'] ?? null;
            $purchasedAt = $previous['purchased_at'] ?? $legacy['purchased_at'] ?? null;
            $expiresAt = $previous['access_expires_at'] ?? $legacy['access_expires_at'] ?? null;
            if ($status === 'ACTIVE') {
                $email = strtolower($this->string($data['buyer']['email'] ?? null, 255));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('E-mail do comprador inválido.');
                }
                $name = $this->string($data['buyer']['name'] ?? null, 150);
                $buyerId = $this->repository->buyer($email, $name);
                if ($userId !== null && (int) $userId !== $buyerId) {
                    throw new RuntimeException('Comprador da transação divergente.');
                }
                $userId = $buyerId;
                $approvedAt = $data['purchase']['approved_date'] ?? $time;
                if (!is_int($approvedAt) || $approvedAt <= 0 || $approvedAt > 253402300799000) {
                    throw new InvalidArgumentException('Data da compra inválida.');
                }
                $purchasedAt ??= date('Y-m-d H:i:s', intdiv($approvedAt, 1000));
                if ($legacy === null && ($previous === null || $previous['purchased_at'] === null)) {
                    $expiresAt = $course['access_days'] === null ? null
                        : (new \DateTimeImmutable($purchasedAt))->modify('+' . (int) $course['access_days'] . ' days')->format('Y-m-d H:i:s');
                }
            }
            $this->repository->savePurchase($transaction, $courseId, $userId === null ? null : (int) $userId, $status, $time, $purchasedAt, $expiresAt);
            if ($userId !== null) {
                $this->updateAccess((int) $userId, $courseId, $transaction, $status, $purchasedAt, $expiresAt);
            }
            $this->db->commit();
            return 'processed';
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    private function updateAccess(int $userId, int $courseId, string $transaction, string $status, ?string $purchasedAt, ?string $expiresAt): void
    {
        $access = $this->repository->access($userId, $courseId);
        // Preserva concessões manuais e vínculos anteriores ainda não conciliados.
        if ($access !== null && ($access['hotmart_transaction_id'] === null
            || ($access['hotmart_transaction_id'] !== $transaction
                && $this->repository->purchase($access['hotmart_transaction_id']) === null))) {
            return;
        }
        $active = $this->repository->activePurchase($userId, $courseId);
        if ($active !== null) {
            $this->repository->saveAccess($userId, $courseId, $active['transaction_id'], 'ACTIVE', $active['purchased_at'], $active['access_expires_at']);
        } elseif ($access !== null || $status === 'ACTIVE') {
            if ($status === 'ACTIVE') {
                $status = 'EXPIRED';
            }
            $this->repository->saveAccess($userId, $courseId, $transaction, $status, $purchasedAt, $expiresAt);
        }
    }

    private function string(mixed $value, int $max): string
    {
        if (!is_string($value) || trim($value) === '' || strlen($value) > $max || preg_match('//u', $value) !== 1) {
            throw new InvalidArgumentException('Campo obrigatório inválido.');
        }
        return trim($value);
    }
}
