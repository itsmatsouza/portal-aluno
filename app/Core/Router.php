<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Core;

use Closure;

class Router
{
    private array $routes = [];

    public function get(
        string $path,
        Closure|array $handler,
        array $middleware = []
    ): void {
        $this->addRoute(
            'GET',
            $path,
            $handler,
            $middleware
        );
    }

    public function post(
        string $path,
        Closure|array $handler,
        array $middleware = []
    ): void {
        $this->addRoute(
            'POST',
            $path,
            $handler,
            $middleware
        );
    }

    private function addRoute(
        string $method,
        string $path,
        Closure|array $handler,
        array $middleware = []
    ): void {
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(
        string $method,
        string $uri
    ): mixed {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            Response::json([
                'success' => false,
                'message' => 'Página não encontrada.'
            ], 404);
        }

        foreach ($route['middleware'] as $middleware) {
            $middleware->handle();
        }

        $handler = $route['handler'];

        if ($handler instanceof Closure) {
            return $handler();
        }

        [$controller, $action] = $handler;

        $instance = $controller;

        return $instance->$action();
    }
}