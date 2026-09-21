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
    $runner = new Leilabrito\PortalAluno\Database\MigrationRunner(
        Leilabrito\PortalAluno\Core\Database::getConnection(),
        dirname(__DIR__) . '/database/migrations'
    );
    $runner->run();
    echo "Migrations aplicadas.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
