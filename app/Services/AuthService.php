<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Repositories\UserRepository;
use Leilabrito\PortalAluno\Models\User;

class AuthService
{
    public function __construct(
        private UserRepository $users
    ) {
    }

    public function login(
        string $email,
        string $password
    ): bool {
        $email = trim(strtolower($email));

        if ($email === '' || $password === '') {
            return false;
        }

        $user = $this->users->findByEmail($email);

        if ($user === null) {
            return false;
        }

        if (!$user->canLogin()) {
            return false;
        }

        if (!password_verify(
            $password,
            $user->getPasswordHash()
        )) {
            return false;
        }

        SessionManager::regenerate();

        SessionManager::set(
            'user_id',
            $user->getId()
        );

        SessionManager::set(
            'role',
            $user->getRole()
        );

        $this->users->updateLastLogin(
            $user->getId()
        );

        return true;
    }

    public function logout(): void
    {
        SessionManager::destroy();
    }

    public function user(): ?User
    {
        $userId = SessionManager::get('user_id');

        if ($userId === null) {
            return null;
        }

        $user = $this->users->findById(
            (int) $userId
        );

        if ($user === null || !$user->canLogin()) {
            SessionManager::destroy();

            return null;
        }

        return $user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }
}
