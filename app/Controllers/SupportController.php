<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Services\AuthService;

class SupportController
{
    public function __construct(
        private AuthService $auth
    ) {
    }

    public function index(): never
    {
        $user = $this->auth->user();

        if ($user === null) {
            Response::redirect('/login');
        }

        require dirname(__DIR__) . '/Views/support.php';

        exit;
    }
}
