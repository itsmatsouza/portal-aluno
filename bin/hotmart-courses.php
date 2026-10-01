<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
date_default_timezone_set('America/Sao_Paulo');

try {
    $api = new Leilabrito\PortalAluno\Services\HotmartApiService();
    $repository = new Leilabrito\PortalAluno\Repositories\HotmartRepository(Leilabrito\PortalAluno\Core\Database::getConnection());
    $page = null;
    $seen = [];
    $count = 0;
    do {
        $result = $api->products($page);
        if (!is_array($result['items'] ?? null) || !array_is_list($result['items'])) {
            throw new RuntimeException('Catálogo Hotmart sem listagem válida.');
        }
        foreach ($result['items'] as $product) {
            if (!is_array($product) || !is_string($product['ucode'] ?? null) || trim($product['ucode']) === ''
                || strlen($product['ucode']) > 100 || !is_string($product['name'] ?? null)
                || trim($product['name']) === '' || preg_match('//u', $product['name']) !== 1
                || preg_match_all('/./us', $product['name']) > 200
                || !is_string($product['status'] ?? null) || !preg_match('/^[A-Z_]+$/D', $product['status'])) {
                throw new RuntimeException('Produto sem nome, ucode ou status válido. Importação interrompida; pode ser repetida.');
            }
            $repository->syncCourse(trim($product['ucode']), trim($product['name']), $product['status'] === 'ACTIVE');
            $count++;
        }
        $page = $result['page_info']['next_page_token'] ?? null;
        if ($page === '') {
            $page = null;
        }
        if ($page !== null && (!is_string($page) || isset($seen[$page]) || count($seen) >= 10000)) {
            throw new RuntimeException('Paginação do catálogo inválida.');
        }
        if ($page !== null) {
            $seen[$page] = true;
        }
    } while ($page !== null);
    echo "Produtos processados: {$count}. Apenas ativos são cadastrados; inativos existentes são atualizados.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
