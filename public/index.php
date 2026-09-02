<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Core\Database;

try {

    $pdo = Database::getConnection();

    echo "Portal do aluno funcionando.";
    echo "<br>";
    echo "Conexão com o banco realizada com sucesso.";

} catch (Throwable $e) {

    http_response_code(500);

    echo "Erro interno da aplicação.";
}