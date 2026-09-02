<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Core\Database;
use Leilabrito\PortalAluno\Database\MigrationRunner;

try {

    $pdo = Database::getConnection();

    $runner = new MigrationRunner(
        $pdo,
        __DIR__ . '/database/migrations'
    );

    $runner->run();

    echo "Migrations verificadas com sucesso." . PHP_EOL;

} catch (Throwable $e) {

    echo "ERRO: " . $e->getMessage() . PHP_EOL;

    exit(1);
}