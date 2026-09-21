<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Leilabrito\PortalAluno\Controllers\AdminCourseController;
use Leilabrito\PortalAluno\Core\Router;
use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Middleware\AdminMiddleware;
use Leilabrito\PortalAluno\Middleware\AuthMiddleware;
use Leilabrito\PortalAluno\Models\Course;
use Leilabrito\PortalAluno\Models\User;
use Leilabrito\PortalAluno\Repositories\CourseRepository;
use Leilabrito\PortalAluno\Repositories\ToolRepository;
use Leilabrito\PortalAluno\Repositories\UserRepository;
use Leilabrito\PortalAluno\Services\AdminCourseService;
use Leilabrito\PortalAluno\Services\AuthService;
use Leilabrito\PortalAluno\Services\CourseToolService;

$scenario = $argv[1] ?? null;
if ($scenario === null) {
    foreach (['view', 'empty', 'anonymous', 'student', 'missing', 'csrf', 'incomplete', 'invalid', 'save', 'clear'] as $case) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($case), $code);
        if ($code !== 0) {
            exit($code);
        }
    }
    exit;
}

ini_set('session.save_path', sys_get_temp_dir());
$_ENV['APP_ENV'] = 'local';
SessionManager::start();
SessionManager::set('user_id', 1);
SessionManager::set('admin_course_token', 'test-token');
$courses = new class extends CourseRepository {
    public function __construct() {}
    public function findById(int $id): ?Course
    {
        return $id === 1 ? new Course(1, 'Curso <teste>', '', null, true, null, '2026-01-01', '2026-01-01') : null;
    }
};
$users = new class($scenario) extends UserRepository {
    public function __construct(private string $scenario) {}
    public function findById(int $id): ?User
    {
        if ($this->scenario === 'anonymous') {
            return null;
        }
        return new User(1, 'Admin', 'admin@example.test', '', $this->scenario === 'student' ? 'ALUNO' : 'ADMIN', null, null, true, null, null, '2026-01-01', '2026-01-01');
    }
};
$tools = new class($scenario) extends ToolRepository {
    public ?array $saved = null;
    public function __construct(private string $scenario) {}
    public function findForCourseAdmin(int $courseId): array
    {
        return $this->scenario === 'empty' ? [] : [
            ['id' => 1, 'name' => 'Cálculo <script>', 'description' => 'Descrição', 'slug' => 'calculo', 'is_active' => 1, 'linked' => 1],
            ['id' => 2, 'name' => 'Planilha', 'description' => null, 'slug' => 'planilha', 'is_active' => 0, 'linked' => 0],
        ];
    }
    public function syncCourseTools(int $courseId, array $toolIds): void
    {
        if (array_diff($toolIds, [1, 2]) !== []) {
            throw new InvalidArgumentException('Seleção inválida.');
        }
        $this->saved = $toolIds;
    }
};
$controller = new AdminCourseController($courses, $users, new AdminCourseService($courses), new CourseToolService($tools));
$auth = new AuthService($users);
$middleware = [new AuthMiddleware($auth), new AdminMiddleware($auth)];
$router = new Router();
$router->get('/admin/courses/{id}/tools', [$controller, 'tools'], $middleware);
$router->post('/admin/courses/{id}/tools', [$controller, 'updateTools'], $middleware);
$post = in_array($scenario, ['csrf', 'incomplete', 'invalid', 'save', 'clear', 'student'], true);
$_POST = ['csrf_token' => 'test-token', 'selection_complete' => '1', 'tools' => ['1', '2']];
if ($scenario === 'csrf') $_POST['csrf_token'] = 'wrong';
if ($scenario === 'incomplete') unset($_POST['selection_complete']);
if ($scenario === 'invalid') $_POST['tools'] = ['999'];
if ($scenario === 'clear') unset($_POST['tools']);
$_SERVER['REQUEST_URI'] = '/admin/courses/' . ($scenario === 'missing' ? '999' : '1') . '/tools';
$expected = match ($scenario) {
    'anonymous' => 401,
    'student', 'csrf' => 403,
    'missing' => 404,
    'incomplete', 'invalid' => 422,
    'save', 'clear' => 302,
    default => 200,
};
ob_start();
register_shutdown_function(static function () use ($scenario, $expected, $tools): void {
    $html = ob_get_clean();
    $error = error_get_last();
    $valid = ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR], true))
        && (http_response_code() ?: 200) === $expected;
    $valid = $valid && ($tools->saved === match ($scenario) { 'save' => [1, 2], 'clear' => [], default => null });
    if ($scenario === 'view') {
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $valid = $valid && $xpath->query('//input[@name="tools[]"]')->length === 2
            && $xpath->query('//input[@name="tools[]" and @checked]')->length === 1
            && str_contains($html, 'Cálculo &lt;script&gt;')
            && str_contains($html, 'aria-current="page">Ferramentas');
    }
    if ($scenario === 'empty') $valid = $valid && str_contains($html, 'Nenhuma ferramenta cadastrada.');
    SessionManager::destroy();
    echo $scenario . ': ' . ($valid ? 'OK' : 'FALHOU') . PHP_EOL;
    if (!$valid) exit(1);
});
$router->dispatch($post ? 'POST' : 'GET', $_SERVER['REQUEST_URI']);
