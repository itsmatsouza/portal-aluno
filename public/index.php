<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Core\Router;
use Leilabrito\PortalAluno\Controllers\HomeController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);

try {

    $router->dispatch(
        $_SERVER['REQUEST_METHOD'],
        $_SERVER['REQUEST_URI']
    );

} catch (Throwable $e) {

    if (http_response_code() === 404) {
        echo 'Página não encontrada.';
    } else {
        http_response_code(500);
        echo 'Erro interno da aplicação.';
    }
}