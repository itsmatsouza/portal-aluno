<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Repositories\CourseRepository;
use Leilabrito\PortalAluno\Repositories\UserCourseRepository;
use Leilabrito\PortalAluno\Repositories\UserRepository;

class AdminController
{
    public function __construct(
        private UserRepository $users,
        private CourseRepository $courses,
        private UserCourseRepository $userCourses
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
            ],
            'stats' => [
                'users' => [
                    'total' => $this->users->countAll(),
                    'active' => $this->users->countActive(),
                    'inactive' => $this->users->countInactive(),
                ],
                'courses' => [
                    'total' => $this->courses->countAll(),
                    'active' => $this->courses->countActive(),
                    'inactive' => $this->courses->countInactive(),
                ],
                'user_courses' => [
                    'total' => $this->userCourses->countAll(),
                    'active' => $this->userCourses->countByStatus('ACTIVE'),
                    'cancelled' => $this->userCourses->countByStatus('CANCELLED'),
                    'refunded' => $this->userCourses->countByStatus('REFUNDED'),
                    'chargeback' => $this->userCourses->countByStatus('CHARGEBACK'),
                    'expired' => $this->userCourses->countByStatus('EXPIRED'),
                    'suspended' => $this->userCourses->countByStatus('SUSPENDED'),
                ],
            ],
        ]);
    }
}