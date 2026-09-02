<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Core;

use Closure;
use RuntimeException;

class Router
{
    private array $routes = [];

    public function get(string $path, Closure|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, Closure|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(
        string $method,
        string $path,
        Closure|array $handler
    ): void {
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(
        string $method,
        string $uri
    ): mixed {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);

            throw new RuntimeException(
                'Página não encontrada.'
            );
        }

        if ($handler instanceof Closure) {
            return $handler();
        }

        [$controller, $action] = $handler;

        $instance = new $controller();

        return $instance->$action();
    }
}
