<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Request;
use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Services\AuthService;

class AuthController
{
    public function __construct(
        private AuthService $auth
    ) {
    }

    public function login(): never
    {
        $request = new Request();

        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');

        $success = $this->auth->login(
            $email,
            $password
        );

        if (!$success) {
            Response::json([
                'success' => false,
                'message' => 'E-mail ou senha inválidos.'
            ], 401);
        }

        Response::json([
            'success' => true,
            'message' => 'Login realizado com sucesso.'
        ]);
    }

    public function logout(): never
    {
        $this->auth->logout();

        Response::json([
            'success' => true,
            'message' => 'Logout realizado com sucesso.'
        ]);
    }

    public function me(): never
    {
        $user = $this->auth->user();

        if ($user === null) {
            Response::json([
                'success' => false,
                'authenticated' => false
            ], 401);
        }

        Response::json([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
            ]
        ]);
    }
}