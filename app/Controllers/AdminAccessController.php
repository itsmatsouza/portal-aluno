<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Repositories\UserCourseRepository;
use Leilabrito\PortalAluno\Repositories\UserRepository;

class AdminAccessController
{
    private const STATUS_LABELS = [
        'ACTIVE' => 'Ativa',
        'CANCELLED' => 'Cancelada',
        'REFUNDED' => 'Reembolsada',
        'CHARGEBACK' => 'Contestada',
        'EXPIRED' => 'Expirada',
        'SUSPENDED' => 'Suspensa',
    ];

    public function __construct(private UserCourseRepository $access, private UserRepository $users)
    {
    }

    public function index(): never
    {
        $search = trim(is_string($_GET['search'] ?? null) ? $_GET['search'] : '');
        $status = is_string($_GET['status'] ?? null) && isset(self::STATUS_LABELS[$_GET['status']]) ? $_GET['status'] : '';
        $courseId = is_string($_GET['course'] ?? null)
            ? filter_var($_GET['course'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
        $courseId = $courseId === false ? null : $courseId;
        $page = is_scalar($_GET['page'] ?? null) ? (int) $_GET['page'] : 1;
        $result = $this->access->findAdminPaginated($page, $search, $status, $courseId);
        $courses = $this->access->findAdminCourseOptions();
        $this->render(compact('search', 'status', 'courseId', 'result', 'courses'));
    }

    public function details(int|string $id): never
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $enrollment = $id === false ? null : $this->access->findAdminDetails($id);
        if ($enrollment === null) {
            Response::viewError('404', 404, ['message' => 'Matrícula não encontrada.']);
        }
        $this->render(compact('enrollment'));
    }

    private function render(array $data): never
    {
        $user = $this->users->findById((int) SessionManager::get('user_id'));
        if ($user === null) {
            Response::viewError('401', 401);
        }
        $admin = ['name' => $user->getName(), 'email' => $user->getEmail()];
        $statusLabels = self::STATUS_LABELS;
        extract($data, EXTR_SKIP);
        require dirname(__DIR__) . '/Views/admin/access.php';
        exit;
    }
}
