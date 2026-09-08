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
        $params = [];

        /*
        * Tenta encontrar uma rota dinâmica caso
        * não exista uma rota estática correspondente.
        */
        if ($route === null) {
            foreach ($this->routes[$method] ?? [] as $routePath => $routeData) {

                $pattern = preg_replace(
                    '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
                    '([^/]+)',
                    $routePath
                );

                $pattern = '#^' . $pattern . '$#';

                if (
                    preg_match(
                        $pattern,
                        $path,
                        $matches
                    )
                ) {
                    array_shift($matches);

                    preg_match_all(
                        '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
                        $routePath,
                        $parameterNames
                    );

                    foreach (
                        $parameterNames[1] as $index => $name
                    ) {
                        $params[$name] = $matches[$index] ?? null;
                    }

                    $route = $routeData;

                    break;
                }
            }
        }

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

        $args = array_values($params);

        foreach ($args as $index => $value) {
            if (is_string($value) && ctype_digit($value)) {
                $args[$index] = (int) $value;
            }
        }

        return $instance->$action(...$args);
            }
}