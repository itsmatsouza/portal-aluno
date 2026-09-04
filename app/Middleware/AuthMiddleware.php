<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Middleware;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Services\AuthService;

class AuthMiddleware
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    public function handle(): void
    {
        if (!$this->authService->check()) {
            Response::json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }
    }
}