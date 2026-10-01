<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use Leilabrito\PortalAluno\Repositories\ClubEnrollmentRepository;
use RuntimeException;

class ClubEnrollmentService
{
    public function __construct(private ClubEnrollmentRepository $enrollments, private HotmartApiService $api)
    {
    }

    public function reconcile(int $userId, int $courseId): bool
    {
        $snapshot = $this->enrollments->snapshot($userId, $courseId);
        if ($snapshot === null) {
            return true;
        }
        try {
            $mapping = json_decode($_ENV['HOTMART_COURSE_SUBDOMAINS'] ?? '{}', true, 32, JSON_THROW_ON_ERROR);
            $subdomain = is_array($mapping) ? ($mapping[$snapshot['hotmart_product_ucode']] ?? null) : null;
            if (!is_string($subdomain) || !preg_match('/^[a-zA-Z0-9-]{1,150}$/D', $subdomain)) {
                throw new RuntimeException('Subdomínio não configurado para o produto ' . $snapshot['hotmart_product_ucode'] . ' em HOTMART_COURSE_SUBDOMAINS.');
            }
            $email = strtolower(trim($snapshot['email']));
            $matches = [];
            $page = null;
            $seen = [];
            do {
                $result = $this->api->clubUsers($subdomain, $email, $page);
                if (!is_array($result['items'] ?? null) || !array_is_list($result['items'])) {
                    throw new RuntimeException('Resposta do Club sem listagem de alunos válida.');
                }
                foreach ($result['items'] as $item) {
                    if (is_array($item) && is_string($item['email'] ?? null)
                        && strtolower(trim($item['email'])) === $email) {
                        $matches[] = $item;
                    }
                }
                $page = $result['page_info']['next_page_token'] ?? null;
                if ($page === '') {
                    $page = null;
                }
                if ($page !== null && (!is_string($page) || isset($seen[$page]) || count($seen) >= 100)) {
                    throw new RuntimeException('Paginação do Club inválida.');
                }
                if ($page !== null) {
                    $seen[$page] = true;
                }
            } while ($page !== null);
            if (count($matches) !== 1) {
                throw new RuntimeException('Aluno ausente ou ambíguo no Club. Vínculo permanece pendente.');
            }
            $item = $matches[0];
            if (!is_string($item['class_id'] ?? null) || !preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $item['class_id'])
                || !is_string($item['status'] ?? null) || !preg_match('/^[A-Z_]{1,40}$/D', $item['status'])) {
                throw new RuntimeException('Turma ou status inválido na resposta do Club.');
            }
            return $this->enrollments->apply($snapshot, $item['class_id'], $item['status'], null);
        } catch (\Throwable $error) {
            // Não mantém uma turma antiga autorizada após falha de reconciliação.
            $message = $error instanceof \JsonException ? 'HOTMART_COURSE_SUBDOMAINS contém JSON inválido.' : $error->getMessage();
            $this->enrollments->apply($snapshot, null, null, substr($message, 0, 250));
            return false;
        }
    }
}
