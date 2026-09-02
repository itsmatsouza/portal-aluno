<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Request;
use Leilabrito\PortalAluno\Core\Response;

class HomeController
{
    public function index(): void
    {
        $request = new Request();

        Response::json([
            'success' => true,
            'message' => 'Portal do aluno funcionando.',
            'method' => $request->method(),
            'uri' => $request->uri(),
        ]);
    }
}