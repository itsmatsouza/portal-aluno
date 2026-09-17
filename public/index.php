<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Middleware\AdminMiddleware;
use Leilabrito\PortalAluno\Middleware\AuthMiddleware;
use Leilabrito\PortalAluno\Middleware\StudentMiddleware;
use Leilabrito\PortalAluno\Controllers\AuthController;
use Leilabrito\PortalAluno\Core\Router;
use Leilabrito\PortalAluno\Core\Response;
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
use Leilabrito\PortalAluno\Controllers\ToolController;
use Leilabrito\PortalAluno\Controllers\PasswordResetController;
use Leilabrito\PortalAluno\Controllers\SupportController;
use Leilabrito\PortalAluno\Repositories\AuthTokenRepository;
use Leilabrito\PortalAluno\Services\MailService;
use Leilabrito\PortalAluno\Services\PasswordResetService;
use Leilabrito\PortalAluno\Core\Database;

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
 * Dependências da recuperação de senha.
 */
$authTokenRepository = new AuthTokenRepository(
    Database::getConnection()
);

$mailService = new MailService();

$passwordResetService = new PasswordResetService(
    $userRepository,
    $authTokenRepository,
    $mailService
);

$passwordResetController = new PasswordResetController(
    $passwordResetService
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

$toolController = new ToolController(
    $toolRepository,
    $courseToolService
);

$courseController = new CourseController(
    $courseAccessService,
    $courseToolService
);

$dashboardController = new DashboardController(
    $userCourseRepository,
    $courseToolService
);

$supportController = new SupportController(
    $authService
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

$studentMiddleware = new StudentMiddleware(
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

$router->get('/login', function (): void {
    require dirname(__DIR__) . '/app/Views/login.php';
});

$router->get('/password/forgot', function (): void {
    $mode = 'request';
    $token = '';

    require dirname(__DIR__) . '/app/Views/password-reset.php';
});

$router->get('/password/reset', function (): void {
    $mode = 'reset';
    $token = (string) ($_GET['token'] ?? '');

    require dirname(__DIR__) . '/app/Views/password-reset.php';
});

$router->post('/login', [
    $authController,
    'login'
]);

$router->post('/password/forgot', [
    $passwordResetController,
    'forgot'
]);

$router->post('/password/reset', [
    $passwordResetController,
    'reset'
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
        $authMiddleware,
        $studentMiddleware
    ]
);

$router->get(
    '/tool/{slug}/view',
    [
        $toolController,
        'view'
    ],
    [
        $authMiddleware,
        $studentMiddleware
    ]
);

$router->get(
    '/tool/{slug}',
    [
        $toolController,
        'show'
    ],
    [
        $authMiddleware,
        $studentMiddleware
    ]
);

$router->get('/course/{courseId}/view', function (): void {
    require dirname(__DIR__) . '/app/Views/course.php';
}, [
    $authMiddleware,
    $studentMiddleware
]);

$router->get('/portal', function (): void {
    require dirname(__DIR__) . '/app/Views/dashboard.php';
}, [
    $authMiddleware,
    $studentMiddleware
]);

$router->get('/support', [
    $supportController,
    'index'
], [
    $authMiddleware,
    $studentMiddleware
]);

$router->get('/dashboard', [
    $dashboardController,
    'index'
], [
    $authMiddleware,
    $studentMiddleware
]);

$router->get('/admin', [
    $adminController,
    'index'
], [
    $authMiddleware,
    $adminMiddleware
]);

$router->get(
    '/admin/users',
    [$adminController, 'users'],
    [$authMiddleware, $adminMiddleware]
);

/*
 * Executa a rota atual.
 */
try {

    $router->dispatch(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        $_SERVER['REQUEST_URI'] ?? '/'
    );

} catch (Throwable $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());

    Response::viewError(
        '500',
        500,
        [
            'message' => 'Ocorreu um erro interno. Tente novamente mais tarde.'
        ]
    );
}