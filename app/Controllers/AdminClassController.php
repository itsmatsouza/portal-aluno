<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Repositories\ClassRepository;
use Leilabrito\PortalAluno\Repositories\CourseRepository;
use Leilabrito\PortalAluno\Repositories\UserRepository;
use Leilabrito\PortalAluno\Services\AdminClassService;

class AdminClassController
{
    public function __construct(
        private ClassRepository $classes,
        private CourseRepository $courses,
        private UserRepository $users,
        private AdminClassService $service
    ) {
    }

    public function index(): never
    {
        $search = is_string($_GET['search'] ?? null) ? trim($_GET['search']) : '';
        $page = is_scalar($_GET['page'] ?? null) ? (int) $_GET['page'] : 1;
        $this->render(['result' => $this->classes->listing($search, $page), 'search' => $search]);
    }

    public function create(): never
    {
        $this->renderForm(null, ['name' => '', 'course_id' => '', 'hotmart_class_id' => '',
            'expiration' => '', 'access_days' => '', 'tools' => []]);
    }

    public function edit(int|string $id): never
    {
        $class = $this->find($id);
        $values = $class;
        $values['expiration'] = $class['is_lifetime'] ? 'lifetime' : 'days';
        $values['access_days'] = (string) ($class['access_days'] ?? '');
        $values['tools'] = array_map('intval', array_column(array_filter($this->classes->tools((int) $class['id']), static fn (array $tool): bool => (bool) $tool['linked']), 'id'));
        $this->renderForm($class, $values);
    }

    public function store(): never
    {
        $this->save(null);
    }

    public function update(int|string $id): never
    {
        $this->save($this->find($id));
    }

    private function save(?array $class): never
    {
        $token = SessionManager::get('admin_class_token');
        if (!is_string($token) || !is_string($_POST['csrf_token'] ?? null) || !hash_equals($token, $_POST['csrf_token'])) {
            Response::viewError('403', 403);
        }
        $result = $this->service->save($class === null ? null : (int) $class['id'], $_POST);
        if ($result['errors'] !== []) {
            http_response_code(422);
            $this->renderForm($class, $result['values'], $result['errors']);
        }
        SessionManager::set('admin_class_message', 'Turma salva. O prazo de cada aluno é calculado desde a compra, conforme a duração da turma.');
        Response::redirect('/admin/classes/' . $result['id'] . '/edit');
    }

    private function find(int|string $id): array
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $class = $id === false ? null : $this->classes->find($id);
        if ($class === null) {
            Response::viewError('404', 404, ['message' => 'Turma não encontrada.']);
        }
        return $class;
    }

    private function renderForm(?array $class, array $values, array $errors = []): never
    {
        $this->render(['form' => true, 'class' => $class, 'values' => $values, 'errors' => $errors,
            'courses' => $this->courses->findAllAvailable(), 'tools' => $this->classes->tools($class === null ? null : (int) $class['id'])]);
    }

    private function render(array $data): never
    {
        $user = $this->users->findById((int) SessionManager::get('user_id'));
        if ($user === null) {
            Response::viewError('401', 401);
        }
        $admin = ['name' => $user->getName(), 'email' => $user->getEmail()];
        $token = SessionManager::get('admin_class_token');
        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            SessionManager::set('admin_class_token', $token);
        }
        $message = SessionManager::get('admin_class_message');
        SessionManager::remove('admin_class_message');
        extract($data, EXTR_SKIP);
        require dirname(__DIR__) . '/Views/admin/classes.php';
        exit;
    }
}
