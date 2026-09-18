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
            Response::viewError(
                '404',
                404,
                [
                    'message' => 'Administrador não encontrado.'
                ]
            );
        }

        $stats = [
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
        ];

        $admin = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
        ];

        require dirname(__DIR__) . '/Views/admin/dashboard.php';

        exit;
    }

    public function users(): never
    {
        $page = max(
            1,
            (int) (is_scalar($_GET['page'] ?? 1) ? ($_GET['page'] ?? 1) : 1)
        );

        $search = trim(
            is_string($_GET['search'] ?? '') ? ($_GET['search'] ?? '') : ''
        );

        $status = is_string($_GET['status'] ?? '') ? ($_GET['status'] ?? '') : '';

        $role = is_string($_GET['role'] ?? '') ? ($_GET['role'] ?? '') : '';
        $status = in_array($status, ['active', 'inactive'], true) ? $status : '';
        $role = in_array($role, ['ADMIN', 'ALUNO'], true) ? $role : '';

        $result = $this->users->findPaginated(
            page: $page,
            perPage: 20,
            search: $search !== '' ? $search : null,
            status: $status !== '' ? $status : null,
            role: $role !== '' ? $role : null
        );

        $adminId = (int) SessionManager::get('user_id');

        $adminUser = $this->users->findById($adminId);

        if ($adminUser === null) {
            Response::viewError(
                '404',
                404,
                [
                    'message' => 'Administrador não encontrado.',
                ]
            );
        }

        $admin = [
            'id' => $adminUser->getId(),
            'name' => $adminUser->getName(),
            'email' => $adminUser->getEmail(),
        ];

        $users = $result['items'];
        $total = $result['total'];
        $currentPage = $result['page'];
        $perPage = $result['per_page'];
        $totalPages = $result['total_pages'];

        require dirname(__DIR__) . '/Views/admin/users.php';

        exit;
    }

    public function userDetails(int|string $id): never
    {
        $userId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $detailUser = $userId === false ? null : $this->users->findById($userId);

        if ($detailUser === null || $detailUser->isDeleted()) {
            Response::viewError('404', 404, ['message' => 'Usuário não encontrado.']);
        }

        $adminUser = $this->users->findById((int) SessionManager::get('user_id'));
        if ($adminUser === null) {
            Response::viewError('404', 404, ['message' => 'Administrador não encontrado.']);
        }

        $admin = [
            'name' => $adminUser->getName(),
            'email' => $adminUser->getEmail(),
        ];
        $enrollments = $this->userCourses->findDetailsByUserId($userId);

        require dirname(__DIR__) . '/Views/admin/users.php';
        exit;
    }
}
