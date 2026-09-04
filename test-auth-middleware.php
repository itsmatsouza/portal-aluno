<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Middleware\AuthMiddleware;
use Leilabrito\PortalAluno\Services\AuthService;
use Leilabrito\PortalAluno\Repositories\UserRepository;

$auth = new AuthService(
    new UserRepository()
);

$middleware = new AuthMiddleware($auth);

echo "=== TESTE 1: USUÁRIO NÃO AUTENTICADO ===\n";

SessionManager::destroy();

try {
    $middleware->handle();

    echo "FALHOU: deveria bloquear o acesso.\n";
} catch (Throwable $e) {
    echo "Erro inesperado: {$e->getMessage()}\n";
}

echo "\n=== FIM ===\n";
