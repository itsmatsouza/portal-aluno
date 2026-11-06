<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use DateTimeImmutable;
use Generator;
use Leilabrito\PortalAluno\Repositories\ClubEnrollmentRepository;
use RuntimeException;
use Throwable;

class ClubStudentDiscoveryService
{
    public function __construct(
        private ClubEnrollmentRepository $enrollments,
        private HotmartApiService $api,
        private HotmartStudentImportService $importer
    ) {
    }

    /** Não concede acesso: a reconciliação posterior confirma novamente o Club. */
    public function discover(?string $onlyEmail = null): array
    {
        $report = ['imported' => 0, 'existing' => 0, 'unchanged' => 0, 'skipped' => 0,
            'pending' => [], 'deferred_enrollments' => []];
        $courses = [];
        foreach ($this->enrollments->discoverableClasses() as $row) {
            $id = (int) $row['course_id'];
            $courses[$id]['ucode'] = $row['hotmart_product_ucode'];
            $courses[$id]['classes'][$row['hotmart_class_id']] = true;
        }
        if ($courses === []) {
            return $report;
        }
        $mapping = json_decode($_ENV['HOTMART_COURSE_SUBDOMAINS'] ?? '{}', true, 32, JSON_THROW_ON_ERROR);
        $products = [];
        foreach ($this->pages(fn ($page) => $this->api->products($page)) as $product) {
            if (!is_array($product) || !is_string($product['ucode'] ?? null)
                || !is_scalar($product['id'] ?? null) || !ctype_digit((string) $product['id']) || (int) $product['id'] < 1) {
                throw new RuntimeException('Catálogo Hotmart inválido.');
            }
            if (isset($products[$product['ucode']])) {
                throw new RuntimeException('Produto ambíguo no catálogo.');
            }
            $products[$product['ucode']] = $product;
        }
        foreach ($courses as $courseId => $course) {
            $candidates = [];
            try {
                $subdomain = $mapping[$course['ucode']] ?? null;
                if (!is_string($subdomain) || !preg_match('/^[A-Za-z0-9-]{1,150}$/D', $subdomain)) {
                    throw new RuntimeException('Subdomínio do Club não configurado.');
                }
                $product = $products[$course['ucode']] ?? null;
                if ($product === null) {
                    throw new RuntimeException('Curso ausente do catálogo Hotmart.');
                }
                $members = [];
                foreach ($this->pages(fn ($page) => $onlyEmail === null
                    ? $this->api->clubMembers($subdomain, $page)
                    : $this->api->clubUsers($subdomain, $onlyEmail, $page)) as $member) {
                    if (!is_array($member) || !is_string($member['email'] ?? null)) {
                        throw new RuntimeException('Aluno inválido na resposta do Club.');
                    }
                    $email = strtolower(trim($member['email']));
                    if ($onlyEmail !== null && $email !== strtolower(trim($onlyEmail))) {
                        continue;
                    }
                    $members[$email][] = $member;
                }
                foreach ($members as $email => $matches) {
                    if (count($matches) !== 1) {
                        $this->pending($report, $courseId, $email, 'Aluno ambíguo no Club.');
                        continue;
                    }
                    $member = $matches[0];
                    if (($member['status'] ?? null) !== 'ACTIVE'
                        || !is_string($member['class_id'] ?? null)
                        || !isset($course['classes'][$member['class_id']])) {
                        $report['skipped']++;
                        continue;
                    }
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->pending($report, $courseId, $email, 'E-mail inválido no Club.');
                        continue;
                    }
                    try {
                        $user = $this->enrollments->discoveryUser($email);
                        if ($user !== null && (!$user['is_active'] || $user['deleted_at'] !== null)) {
                            $report['skipped']++;
                            continue;
                        }
                        if ($user !== null && strtolower($user['email']) !== $email) {
                            throw new RuntimeException('E-mail do Club diverge da conta; revisar identidade.');
                        }
                        $snapshot = $user === null ? null
                            : $this->enrollments->snapshot((int) $user['id'], $courseId);
                        if ($snapshot !== null && (bool) $snapshot['is_manual']) {
                            $report['skipped']++;
                            continue;
                        }
                        if ($snapshot !== null && $snapshot['hotmart_class_id'] === $member['class_id']
                            && $snapshot['purchased_at'] !== null) {
                            $report['unchanged']++;
                            continue;
                        }
                        $candidates[$email] = $snapshot;
                    } catch (Throwable $error) {
                        $this->pending($report, $courseId, $email, 'Identidade exige revisão.');
                    }
                }
                if ($candidates === []) {
                    continue;
                }
                // Janela mensal: a API rejeita consultas históricas extensas em uma só requisição.
                $sales = $this->latestSales($product, $candidates);
                foreach ($candidates as $email => $snapshot) {
                    if (!isset($sales[$email])) {
                        $this->pending($report, $courseId, $email, 'Compra aprovada/concluída não localizada.', $snapshot);
                        continue;
                    }
                    try {
                        $outcome = $this->importer->importSale($sales[$email], $course['ucode']);
                        if (!in_array($outcome, ['imported', 'existing'], true)) {
                            $this->pending($report, $courseId, $email, 'Matrícula existente preservada; revisar vínculo.');
                            continue;
                        }
                        // Uma revisão comercial só termina após uma compra válida ser confirmada.
                        if ($snapshot !== null) {
                            $current = $this->enrollments->snapshot((int) $snapshot['user_id'], $courseId);
                            if ($current !== null && str_starts_with($current['sync_error'] ?? '', ClubEnrollmentService::PURCHASE_REVIEW_PREFIX)) {
                                $this->enrollments->apply($current, null, null, 'Aguardando confirmação do Club.');
                            }
                        }
                        $report[$outcome]++;
                    } catch (Throwable $error) {
                        $this->pending($report, $courseId, $email, 'Falha ao importar compra; revisar identidade e banco.', $snapshot);
                    }
                }
            } catch (Throwable $error) {
                $this->pending($report, $courseId, null, $error instanceof RuntimeException
                    ? $error->getMessage() : 'Falha na consulta de descoberta.');
                foreach ($candidates as $email => $snapshot) {
                    $this->pending($report, $courseId, $email, 'Consulta de compra incompleta; aguarde nova execução.', $snapshot);
                }
            }
        }
        return $report;
    }

    private function latestSales(array $product, array $candidates): array
    {
        $end = (int) floor(microtime(true) * 1000);
        $created = $product['created_at'] ?? null;
        if (!is_int($created) || $created < 1000000000000 || $created > $end) {
            throw new RuntimeException('Data de criação do produto inválida.');
        }
        $start = (new DateTimeImmutable('@' . intdiv($created, 1000)))
            ->setTimezone(new \DateTimeZone('America/Sao_Paulo'))->modify('first day of this month')->setTime(0, 0);
        $latest = [];
        for (; $start->getTimestamp() * 1000 <= $end; $start = $next) {
            $next = $start->modify('first day of next month');
            foreach (['APPROVED', 'COMPLETE'] as $status) {
                $filters = ['product_id' => (string) $product['id'], 'transaction_status' => $status,
                    'start_date' => $start->getTimestamp() * 1000,
                    'end_date' => min($end, $next->getTimestamp() * 1000 - 1), 'max_results' => 100];
                // Com um só candidato, evita trazer o histórico de todos os compradores.
                if (count($candidates) === 1) {
                    $filters['buyer_email'] = array_key_first($candidates);
                }
                foreach ($this->pages(fn ($page) => $this->api->sales(
                    $page === null ? $filters : $filters + ['page_token' => $page]
                )) as $sale) {
                    $email = is_string($sale['buyer']['email'] ?? null) ? strtolower(trim($sale['buyer']['email'])) : '';
                    if (!array_key_exists($email, $candidates)
                        || (string) ($sale['product']['id'] ?? '') !== (string) $product['id']) {
                        continue;
                    }
                    $valid = $this->importer->validateSale($sale);
                    if ($valid === null) {
                        continue;
                    }
                    $previous = $latest[$email] ?? null;
                    if ($previous === null || $valid['approvedAt'] > $previous['purchase']['approved_date']
                        || ($valid['approvedAt'] === $previous['purchase']['approved_date']
                            && strcmp($valid['transaction'], $previous['purchase']['transaction']) > 0)) {
                        $latest[$email] = $sale;
                    }
                }
            }
        }
        return $latest;
    }

    private function pending(array &$report, int $courseId, ?string $email, string $reason, ?array $snapshot = null): void
    {
        $report['pending'][] = ['course_id' => $courseId, 'email' => $email, 'reason' => $reason];
        if ($snapshot !== null && !(bool) $snapshot['is_manual']) {
            $this->enrollments->apply($snapshot, null, null, ClubEnrollmentService::PURCHASE_REVIEW_PREFIX . $reason);
            $report['deferred_enrollments'][] = (int) $snapshot['id'];
        }
    }

    private function pages(callable $fetch): Generator
    {
        $page = null;
        $seen = [];
        do {
            $result = $fetch($page);
            if (!is_array($result['items'] ?? null) || !array_is_list($result['items'])) {
                throw new RuntimeException('Listagem Hotmart inválida.');
            }
            yield from $result['items'];
            $page = $result['page_info']['next_page_token'] ?? null;
            if ($page === '') {
                $page = null;
            }
            if ($page !== null) {
                if (!is_string($page) || isset($seen[$page]) || count($seen) >= 10000) {
                    throw new RuntimeException('Paginação Hotmart inválida ou repetida.');
                }
                $seen[$page] = true;
            }
        } while ($page !== null);
    }
}
