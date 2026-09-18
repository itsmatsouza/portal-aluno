<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Models\Course;
use Leilabrito\PortalAluno\Repositories\CourseRepository;
use Leilabrito\PortalAluno\Repositories\UserRepository;
use Leilabrito\PortalAluno\Services\AdminCourseService;

class AdminCourseController
{
    public function __construct(
        private CourseRepository $courses,
        private UserRepository $users,
        private AdminCourseService $service
    ) {
    }

    public function index(): never
    {
        $search = trim(is_string($_GET['search'] ?? null) ? $_GET['search'] : '');
        $status = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';
        $page = is_scalar($_GET['page'] ?? null) ? (int) $_GET['page'] : 1;
        $result = $this->courses->findPaginated($page, 20, $search, $status);
        $this->render(compact('search', 'status', 'result'));
    }

    public function create(): never
    {
        $this->render(['form' => true, 'course' => null, 'values' => ['name' => '', 'description' => '', 'ucode' => '', 'active' => true], 'errors' => []]);
    }

    public function edit(int|string $id): never
    {
        $course = $this->findCourse($id);
        $this->render(['form' => true, 'course' => $course, 'values' => [
            'name' => $course->getName(),
            'description' => $course->getDescription() ?? '',
            'ucode' => $course->getHotmartProductUcode() ?? '',
            'active' => $course->isActive(),
        ], 'errors' => []]);
    }

    public function store(): never
    {
        $this->save(null);
    }

    public function update(int|string $id): never
    {
        $this->save($this->findCourse($id));
    }

    public function status(int|string $id): never
    {
        $this->verifyToken();
        $course = $this->findCourse($id);
        if (!in_array($_POST['active'] ?? null, ['0', '1'], true)) {
            Response::viewError('400', 400, ['message' => 'Status inválido.']);
        }
        $active = $_POST['active'] === '1';
        $this->courses->setActive($course->getId(), $active);
        SessionManager::set('admin_course_message', $active ? 'Curso ativado.' : 'Curso inativado.');
        Response::redirect('/admin/courses');
    }

    private function save(?Course $course): never
    {
        $this->verifyToken();
        $result = $this->service->save($course?->getId(), $_POST);
        if ($result['errors'] !== []) {
            http_response_code(422);
            $this->render(['form' => true, 'course' => $course] + $result);
        }
        SessionManager::set('admin_course_message', $course === null ? 'Curso criado.' : 'Curso atualizado.');
        Response::redirect('/admin/courses/' . $result['id'] . '/edit');
    }

    private function findCourse(int|string $id): Course
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $course = $id === false ? null : $this->courses->findById($id);
        if ($course === null || $course->isDeleted()) {
            Response::viewError('404', 404, ['message' => 'Curso não encontrado.']);
        }

        return $course;
    }

    private function verifyToken(): void
    {
        $token = SessionManager::get('admin_course_token');
        if (!is_string($token) || !is_string($_POST['csrf_token'] ?? null) || !hash_equals($token, $_POST['csrf_token'])) {
            Response::viewError('403', 403);
        }
    }

    private function render(array $data): never
    {
        $user = $this->users->findById((int) SessionManager::get('user_id'));
        if ($user === null) {
            Response::viewError('401', 401);
        }
        $admin = ['name' => $user->getName(), 'email' => $user->getEmail()];
        $token = SessionManager::get('admin_course_token');
        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            SessionManager::set('admin_course_token', $token);
        }
        $message = SessionManager::get('admin_course_message');
        SessionManager::remove('admin_course_message');
        extract($data, EXTR_SKIP);
        require dirname(__DIR__) . '/Views/admin/courses.php';
        exit;
    }
}
