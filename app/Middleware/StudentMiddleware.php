<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Middleware;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Services\AuthService;

class StudentMiddleware
{
    public function __construct(
        private AuthService $auth
    ) {
    }

    public function handle(): void
    {
        $user = $this->auth->user();

        if ($user === null) {
            Response::viewError(
                '401',
                401,
                [
                    'message' => 'Você precisa estar autenticado para acessar esta página.'
                ]
            );
        }

        if ($user->isAdmin()) {
            Response::redirect('/admin');
        }
    }
}
