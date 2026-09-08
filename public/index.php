<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Middleware\AdminMiddleware;
use Leilabrito\PortalAluno\Middleware\AuthMiddleware;
use Leilabrito\PortalAluno\Controllers\AuthController;
use Leilabrito\PortalAluno\Core\Router;
use Leilabrito\PortalAluno\Repositories\UserRepository;
use Leilabrito\PortalAluno\Services\AuthService;
use Leilabrito\PortalAluno\Controllers\CourseController;
use Leilabrito\PortalAluno\Repositories\CourseRepository;
use Leilabrito\PortalAluno\Repositories\UserCourseRepository;
use Leilabrito\PortalAluno\Services\CourseAccessService;
use Leilabrito\PortalAluno\Controllers\DashboardController;
use Leilabrito\PortalAluno\Controllers\AdminController;
use Leilabrito\PortalAluno\Repositories\ToolRepository;
use Leilabrito\PortalAluno\Services\CourseToolService;

$router = new Router();

/*
 * Dependências da autenticação.
 */
$userRepository = new UserRepository();

$authService = new AuthService(
    $userRepository
);

$authController = new AuthController(
    $authService
);

/*
 * Dependências dos cursos.
 */
$courseRepository = new CourseRepository();

$userCourseRepository = new UserCourseRepository();

$toolRepository = new ToolRepository();

$courseAccessService = new CourseAccessService(
    $userCourseRepository,
    $courseRepository
);

$courseToolService = new CourseToolService(
    $toolRepository
);

$courseController = new CourseController(
    $courseAccessService,
    $courseToolService
);

$dashboardController = new DashboardController(
    $userCourseRepository
);

/*
 * Middlewares.
 */
$authMiddleware = new AuthMiddleware(
    $authService
);

$adminMiddleware = new AdminMiddleware(
    $authService
);

/*
 * Dependências administrativas.
 */
$adminController = new AdminController(
    $userRepository,
    $courseRepository,
    $userCourseRepository
);

/*
 * Rotas.
 */
$router->get('/', function (): void {
    echo 'Portal do aluno funcionando.';
});

$router->post('/login', [
    $authController,
    'login'
]);

$router->post('/logout', [
    $authController,
    'logout'
]);

$router->get(
    '/me',
    [
        $authController,
        'me'
    ],
    [
        $authMiddleware
    ]
);

$router->get(
    '/course/{courseId}',
    [
        $courseController,
        'show'
    ],
    [
        $authMiddleware
    ]
);

$router->get('/dashboard', [
    $dashboardController,
    'index'
], [
    $authMiddleware
]);

$router->get('/admin', [
    $adminController,
    'index'
], [
    $authMiddleware,
    $adminMiddleware
]);

/*
 * Executa a rota atual.
 */
try {

    $router->dispatch(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        $_SERVER['REQUEST_URI'] ?? '/'
    );

} catch (Throwable $e) {

    http_response_code(500);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor.'
    ]);
}