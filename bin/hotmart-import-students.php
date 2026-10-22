<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
date_default_timezone_set('America/Sao_Paulo');

$options = getopt('', ['from:', 'to:', 'email:', 'product-id:', 'dry-run', 'help']);
if (isset($options['help'])) {
    echo "Uso: php bin/hotmart-import-students.php --from=AAAA-MM-DD --to=AAAA-MM-DD [--email=aluno@example.com] [--product-id=123] [--dry-run]\n";
    echo "--dry-run: retorna JSON com compras, consulta ao Club e correspondência local, sem gravar.\n";
    echo "Importa compras APPROVED e COMPLETE; preserva transações existentes. Depois execute hotmart-sync-classes.php.\n";
    exit;
}

$dryRun = isset($options['dry-run']);
$preview = [];
$clubCache = [];
$locked = false;
try {
    $dates = [];
    foreach (['from', 'to'] as $key) {
        $value = $options[$key] ?? null;
        $date = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;
        if (!$date || $date->format('Y-m-d') !== $value || $date < new DateTimeImmutable('2001-09-09')) {
            throw new InvalidArgumentException('Informe --from e --to como datas válidas AAAA-MM-DD.');
        }
        $dates[$key] = $date;
    }
    if ($dates['from'] > $dates['to'] || $dates['to'] > new DateTimeImmutable('today')) {
        throw new InvalidArgumentException('Período invertido ou data final no futuro.');
    }
    $email = $options['email'] ?? null;
    if ($email !== null && (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        throw new InvalidArgumentException('Filtro --email inválido.');
    }
    $email = $email === null ? null : strtolower($email);
    $productId = $options['product-id'] ?? null;
    if ($productId !== null && (!is_string($productId) || !ctype_digit($productId) || (int) $productId < 1)) {
        throw new InvalidArgumentException('Filtro --product-id inválido.');
    }
    Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
    $db = Leilabrito\PortalAluno\Core\Database::getConnection();
    if ($dryRun) {
        // O servidor rejeita escritas nesta conexão durante toda a simulação.
        $db->exec('SET SESSION TRANSACTION READ ONLY');
    } else {
        $lockName = 'portal-student-import-' . substr(hash('sha256', (string) $db->query('SELECT DATABASE()')->fetchColumn()), 0, 30);
        $stmt = $db->prepare('SELECT GET_LOCK(?, 0)');
        $stmt->execute([$lockName]);
        $locked = (int) $stmt->fetchColumn() === 1;
        if (!$locked) {
            throw new RuntimeException('Outra importação de alunos está em andamento.');
        }
    }
    $api = new Leilabrito\PortalAluno\Services\HotmartApiService();
    $service = new Leilabrito\PortalAluno\Services\HotmartStudentImportService(
        $db, new Leilabrito\PortalAluno\Repositories\HotmartRepository($db)
    );
    $club = new Leilabrito\PortalAluno\Services\ClubEnrollmentService(
        new Leilabrito\PortalAluno\Repositories\ClubEnrollmentRepository($db), $api
    );
    $previewRepository = new Leilabrito\PortalAluno\Repositories\HotmartImportPreviewRepository($db);
    // O histórico informa ID numérico; o portal identifica cursos por ucode.
    $products = [];
    foreach (importPages(static fn (?string $page): array => $api->products($page)) as $product) {
        if (!is_array($product) || !isset($product['id']) || !is_scalar($product['id'])
            || !ctype_digit((string) $product['id']) || !is_string($product['ucode'] ?? null)
            || !preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $product['ucode'])) {
            throw new RuntimeException('Catálogo sem ID numérico ou ucode válido.');
        }
        $id = (string) $product['id'];
        if (isset($products[$id]) && $products[$id] !== $product['ucode']) {
            throw new RuntimeException('Produto com mapeamento ambíguo no catálogo.');
        }
        $products[$id] = $product['ucode'];
    }
    if ($productId !== null && !isset($products[$productId])) {
        throw new RuntimeException('Produto solicitado não consta no catálogo.');
    }
    $counts = ['imported' => 0, 'existing' => 0, 'preserved_access' => 0, 'missing_course' => 0,
        'missing_product' => 0, 'ignored_status' => 0, 'filtered' => 0];
    $end = min(($dates['to']->modify('+1 day')->getTimestamp() * 1000) - 1, (int) floor(microtime(true) * 1000));
    // Janelas mensais evitam depender do período padrão da API.
    for ($start = $dates['from']; $start->getTimestamp() * 1000 <= $end; $start = $next) {
        $next = $start->modify('first day of next month');
        $windowEnd = min($end, $next->getTimestamp() * 1000 - 1);
        foreach (['APPROVED', 'COMPLETE'] as $status) {
            $filters = ['start_date' => $start->getTimestamp() * 1000, 'end_date' => $windowEnd,
                'transaction_status' => $status, 'max_results' => 100];
            if ($email !== null) {
                $filters['buyer_email'] = $email;
            }
            if ($productId !== null) {
                $filters['product_id'] = $productId;
            }
            foreach (importPages(static fn (?string $page): array => $api->sales(
                $page === null ? $filters : $filters + ['page_token' => $page]
            )) as $sale) {
                if (!is_array($sale) || !is_scalar($sale['product']['id'] ?? null)) {
                    throw new RuntimeException('Venda sem produto válido.');
                }
                $id = (string) $sale['product']['id'];
                if (($email !== null && (!is_string($sale['buyer']['email'] ?? null)
                        || strtolower(trim($sale['buyer']['email'])) !== $email))
                    || ($productId !== null && $id !== $productId)) {
                    $counts['filtered']++;
                    continue;
                }
                if (!isset($products[$id])) {
                    if ($dryRun) {
                        $preview[] = ['sale' => $sale, 'error' => 'Produto ausente do catálogo.'];
                    }
                    $counts['missing_product']++;
                    continue;
                }
                if ($dryRun) {
                    $data = $service->validateSale($sale);
                    if ($data === null) {
                        $counts['ignored_status']++;
                        continue;
                    }
                    $ucode = $products[$id];
                    $cacheKey = $ucode . ':' . $data['email'];
                    if (!isset($clubCache[$cacheKey])) {
                        try {
                            $clubCache[$cacheKey] = ['response' => $club->lookup($ucode, $data['email']), 'error' => null];
                        } catch (Throwable $error) {
                            $clubCache[$cacheKey] = ['response' => null, 'error' => $error->getMessage()];
                        }
                    }
                    $clubResult = $clubCache[$cacheKey];
                    $local = $previewRepository->snapshot($ucode, $data['email'], $data['transaction'], $clubResult['response']['class_id'] ?? null);
                    $class = $local['class'];
                    $expiration = $class && !$class['is_lifetime']
                        ? (new DateTimeImmutable($data['purchasedAt']))->modify('+' . (int) $class['access_days'] . ' days')->format('Y-m-d H:i:s') : null;
                    $preview[] = ['sale' => $sale, 'product_ucode' => $ucode,
                        'purchased_at' => $data['purchasedAt'], 'club' => $clubResult,
                        'local' => $local, 'purchase_expires_at' => $expiration,
                        'pending_reason' => $clubResult['error'] ?? ($local['course'] === null
                            ? 'Curso ainda não cadastrado.' : ($class === null ? 'Turma ainda não cadastrada neste curso.' : null))];
                } else {
                    $counts[$service->importSale($sale, $products[$id])]++;
                }
            }
            fwrite($dryRun ? STDERR : STDOUT, $start->format('Y-m-d') . ' / ' . $status . ': concluído.' . PHP_EOL);
        }
    }
    if ($dryRun) {
        echo json_encode(['mode' => 'dry_run', 'writes' => false, 'complete' => true,
            'from' => $options['from'], 'to' => $options['to'], 'total' => count($preview),
            'note' => 'Compras elegíveis e consulta atual ao Club. local representa o banco antes da importação. purchase_expires_at corresponde a cada compra, não substitui a matrícula vigente. A importação preserva transações e matrículas conforme suas regras; sincronização é uma etapa separada.',
            'items' => $preview], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
        $exitCode = 0;
    } else {
        $labels = ['imported' => 'Compras importadas', 'existing' => 'Transações já existentes',
            'preserved_access' => 'Matrículas preservadas para revisão', 'missing_course' => 'Vendas sem curso cadastrado',
            'missing_product' => 'Vendas sem produto no catálogo', 'ignored_status' => 'Status não elegíveis', 'filtered' => 'Fora dos filtros'];
        foreach ($labels as $key => $label) {
            echo $label . ': ' . $counts[$key] . PHP_EOL;
        }
        echo "Execute hotmart-sync-classes.php para confirmar turmas e liberar acessos elegíveis.\n";
        $exitCode = $counts['missing_course'] + $counts['missing_product'] + $counts['preserved_access'] > 0 ? 1 : 0;
    }
} catch (Throwable $error) {
    // Não imprime payloads, e-mails, transações ou detalhes SQL de contas.
    $message = $error instanceof PDOException ? 'Falha no banco ou conflito de identificador do comprador.' : $error->getMessage();
    if ($dryRun) {
        echo json_encode(['mode' => 'dry_run', 'writes' => false, 'complete' => false, 'error' => $message,
            'items' => $preview], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL;
    } else {
        fwrite(STDERR, $message . " Importação interrompida; registros concluídos foram preservados. Corrija e repita o período.\n");
    }
    $exitCode = 1;
} finally {
    if ($locked) {
        $db->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]);
    }
}
exit($exitCode);

/** @return Generator<array> */
function importPages(callable $fetch): Generator
{
    $page = null;
    $seen = [];
    do {
        $result = $fetch($page);
        if (!is_array($result['items'] ?? null) || !array_is_list($result['items'])) {
            throw new RuntimeException('Resposta Hotmart sem listagem válida.');
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
