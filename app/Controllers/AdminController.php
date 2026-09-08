<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Repositories\UserRepository;

class AdminController
{
    public function __construct(
        private UserRepository $users
    ) {
    }

    public function index(): never
    {
        $userId = (int) SessionManager::get('user_id');

        $user = $this->users->findById($userId);

        if ($user === null) {
            Response::json([
                'success' => false,
                'message' => 'Administrador não encontrado.'
            ], 404);
        }

        Response::json([
            'success' => true,
            'message' => 'Área administrativa.',
            'admin' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ]
        ]);
    }
}
