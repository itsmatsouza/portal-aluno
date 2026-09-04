<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Middleware;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Services\AuthService;

class AdminMiddleware
{
    public function __construct(
        private AuthService $auth
    ) {
    }

    public function handle(): void
    {
        $user = $this->auth->user();

        if ($user === null) {
            Response::json([
                'success' => false,
                'message' => 'Não autenticado.'
            ], 401);
        }

        if (!$user->isAdmin()) {
            Response::json([
                'success' => false,
                'message' => 'Acesso não autorizado.'
            ], 403);
        }
    }
}