<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use InvalidArgumentException;
use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Models\Tool;
use Leilabrito\PortalAluno\Repositories\ToolRepository;
use Leilabrito\PortalAluno\Repositories\UserRepository;
use Leilabrito\PortalAluno\Services\AdminToolService;

class AdminToolController
{
    public function __construct(
        private ToolRepository $tools,
        private UserRepository $users,
        private AdminToolService $service
    ) {
    }

    public function index(): never
    {
        $search = trim(is_string($_GET['search'] ?? null) ? $_GET['search'] : '');
        $status = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';
        $page = is_scalar($_GET['page'] ?? null) ? (int) $_GET['page'] : 1;
        $result = $this->tools->findPaginated($page, $search, $status);
        foreach ($result['items'] as &$item) {
            $item['has_file'] = $this->service->hasFile($item['tool']);
        }
        unset($item);
        $this->render(compact('search', 'status', 'result'));
    }

    public function edit(int|string $id): never
    {
        $this->renderEdit($this->findTool($id));
    }

    public function update(int|string $id): never
    {
        $this->verifyToken();
        $tool = $this->findTool($id);
        $result = $this->service->save($tool, $_POST);
        if ($result['errors'] !== []) {
            http_response_code(422);
            $this->renderEdit($tool, $result);
        }
        SessionManager::set('admin_tool_message', 'Ferramenta atualizada.');
        Response::redirect('/admin/tools/' . $tool->getId() . '/edit');
    }

    public function status(int|string $id): never
    {
        $this->verifyToken();
        $tool = $this->findTool($id);
        if (!in_array($_POST['active'] ?? null, ['0', '1'], true)) {
            http_response_code(422);
            $this->renderEdit($tool, ['statusError' => 'Status inválido.']);
        }
        $active = $_POST['active'] === '1';
        try {
            $this->service->setActive($tool, $active);
        } catch (InvalidArgumentException $error) {
            http_response_code(422);
            $this->renderEdit($tool, ['statusError' => $error->getMessage()]);
        }
        SessionManager::set('admin_tool_message', $active ? 'Ferramenta ativada.' : 'Ferramenta inativada em todos os cursos vinculados.');
        Response::redirect('/admin/tools/' . $tool->getId() . '/edit');
    }

    private function findTool(int|string $id): Tool
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $tool = $id === false ? null : $this->tools->findById($id);
        if ($tool === null || $tool->isDeleted()) {
            Response::viewError('404', 404, ['message' => 'Ferramenta não encontrada.']);
        }

        return $tool;
    }

    private function renderEdit(Tool $tool, array $data = []): never
    {
        $this->render($data + [
            'tool' => $tool,
            'hasFile' => $this->service->hasFile($tool),
            'courses' => $this->tools->findLinkedCourses($tool->getId()),
            'values' => ['name' => $tool->getName(), 'description' => $tool->getDescription() ?? ''],
            'errors' => [],
            'statusError' => null,
        ]);
    }

    private function verifyToken(): void
    {
        $token = SessionManager::get('admin_tool_token');
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
        $token = SessionManager::get('admin_tool_token');
        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            SessionManager::set('admin_tool_token', $token);
        }
        $message = SessionManager::get('admin_tool_message');
        SessionManager::remove('admin_tool_message');
        extract($data, EXTR_SKIP);
        require dirname(__DIR__) . '/Views/admin/tools.php';
        exit;
    }
}
