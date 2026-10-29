<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use InvalidArgumentException;
use RuntimeException;
use PDO;
use Leilabrito\PortalAluno\Repositories\HotmartRepository;

class HotmartStudentImportService
{
    public function __construct(private PDO $db, private HotmartRepository $repository)
    {
    }

    /** Importação inicial: nunca substitui uma transação já conhecida pelo portal. */
    public function importSale(array $sale, string $productUcode): string
    {
        $validated = $this->validateSale($sale);
        if ($validated === null) {
            return 'ignored_status';
        }
        ['transaction' => $transaction, 'email' => $email, 'name' => $name,
            'buyerUcode' => $buyerUcode, 'approvedAt' => $approvedAt, 'purchasedAt' => $purchasedAt] = $validated;
        $this->db->beginTransaction();
        try {
            $course = $this->repository->lockCourse($productUcode);
            if ($course === null) {
                $this->db->commit();
                return 'missing_course';
            }
            $courseId = (int) $course['id'];
            $previous = $this->repository->purchase($transaction);
            if ($previous !== null) {
                if ((int) $previous['course_id'] !== $courseId) {
                    throw new RuntimeException('Transação já vinculada a outro curso.');
                }
                $this->db->commit();
                return 'existing';
            }
            $legacy = $this->repository->legacyAccess($transaction);
            if ($legacy !== null && (int) $legacy['course_id'] !== $courseId) {
                throw new RuntimeException('Matrícula existente vinculada a outro curso.');
            }
            if ($legacy !== null && $legacy['status'] !== 'ACTIVE') {
                $this->db->commit();
                return 'preserved_access';
            }
            $userId = $this->repository->buyer($email, $name, $buyerUcode);
            if ($legacy !== null && (int) $legacy['user_id'] !== $userId) {
                throw new RuntimeException('Comprador diverge da matrícula existente.');
            }
            // A data da consulta não é a data de um evento. Webhooks posteriores à compra continuam válidos.
            $this->repository->savePurchase($transaction, $courseId, $userId, 'ACTIVE', $approvedAt, $purchasedAt, null);
            $access = $this->repository->access($userId, $courseId);
            $active = $this->repository->activePurchase($userId, $courseId);
            $outcome = 'imported';
            if ($access !== null && ((bool) $access['is_manual'] || $access['hotmart_transaction_id'] === null
                || ($access['hotmart_transaction_id'] !== $transaction
                    && $this->repository->purchase($access['hotmart_transaction_id']) === null))) {
                $outcome = 'preserved_access';
            } elseif ($access === null || $access['hotmart_transaction_id'] !== $active['transaction_id']
                || $access['status'] !== 'ACTIVE' || $access['purchased_at'] !== $active['purchased_at']) {
                $this->repository->saveAccess($userId, $courseId, $active['transaction_id'], 'ACTIVE', $active['purchased_at'], null);
            }
            $this->db->commit();
            return $outcome;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    /** Valida os mesmos dados na importação e na simulação. */
    public function validateSale(array $sale): ?array
    {
        $status = $sale['purchase']['status'] ?? null;
        if (!in_array($status, ['APPROVED', 'COMPLETE'], true)) {
            return null;
        }
        $transaction = $this->text($sale['purchase']['transaction'] ?? null, 150);
        $email = strtolower($this->text($sale['buyer']['email'] ?? null, 255));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail de comprador inválido.');
        }
        $name = $this->text($sale['buyer']['name'] ?? null, 150);
        $buyerUcode = isset($sale['buyer']['ucode']) ? $this->text($sale['buyer']['ucode'], 100) : null;
        $approvedAt = $sale['purchase']['approved_date'] ?? null;
        if (!is_int($approvedAt) || $approvedAt < 1000000000000 || $approvedAt > (int) floor(microtime(true) * 1000)) {
            throw new InvalidArgumentException('Data de aprovação inválida; esperado timestamp em milissegundos.');
        }
        $purchasedAt = date('Y-m-d H:i:s', intdiv($approvedAt, 1000));
        return compact('transaction', 'email', 'name', 'buyerUcode', 'approvedAt', 'purchasedAt');
    }

    private function text(mixed $value, int $max): string
    {
        if (!is_string($value) || trim($value) === '' || strlen($value) > $max || preg_match('//u', $value) !== 1) {
            throw new InvalidArgumentException('Campo obrigatório da compra inválido.');
        }
        return trim($value);
    }
}
