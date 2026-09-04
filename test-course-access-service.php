<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Core\Database;
use Leilabrito\PortalAluno\Repositories\CourseRepository;
use Leilabrito\PortalAluno\Repositories\UserCourseRepository;
use Leilabrito\PortalAluno\Services\CourseAccessService;

$db = Database::getConnection();

$courseRepository = new CourseRepository();
$userCourseRepository = new UserCourseRepository();

$service = new CourseAccessService(
    $userCourseRepository,
    $courseRepository
);

$userId = 1;
$courseId = 1;

echo "=== TESTE 1: ACESSO COM VÍNCULO ACTIVE ===\n";

$serviceAccess = $service->canAccess(
    $userId,
    $courseId
);

if (!$serviceAccess) {
    echo "FALHOU: acesso deveria estar permitido.\n";
    exit(1);
}

echo "PASSOU: acesso permitido.\n";


echo "\n=== TESTE 2: VÍNCULO CANCELLED ===\n";

$stmt = $db->prepare("
    UPDATE user_courses
    SET status = 'CANCELLED'
    WHERE user_id = :user_id
      AND course_id = :course_id
");

$stmt->execute([
    'user_id' => $userId,
    'course_id' => $courseId,
]);

if ($service->canAccess($userId, $courseId)) {
    echo "FALHOU: acesso deveria estar bloqueado.\n";
    exit(1);
}

echo "PASSOU: acesso bloqueado.\n";


echo "\n=== TESTE 3: VÍNCULO REFUNDED ===\n";

$stmt = $db->prepare("
    UPDATE user_courses
    SET status = 'REFUNDED'
    WHERE user_id = :user_id
      AND course_id = :course_id
");

$stmt->execute([
    'user_id' => $userId,
    'course_id' => $courseId,
]);

if ($service->canAccess($userId, $courseId)) {
    echo "FALHOU: acesso deveria estar bloqueado.\n";
    exit(1);
}

echo "PASSOU: acesso bloqueado.\n";


echo "\n=== TESTE 4: CURSO DESATIVADO ===\n";

/*
 * Primeiro restaura o vínculo.
 */
$stmt = $db->prepare("
    UPDATE user_courses
    SET status = 'ACTIVE'
    WHERE user_id = :user_id
      AND course_id = :course_id
");

$stmt->execute([
    'user_id' => $userId,
    'course_id' => $courseId,
]);

/*
 * Desativa o curso.
 */
$stmt = $db->prepare("
    UPDATE courses
    SET is_active = 0
    WHERE id = :course_id
");

$stmt->execute([
    'course_id' => $courseId,
]);

if ($service->canAccess($userId, $courseId)) {
    echo "FALHOU: curso desativado não deveria permitir acesso.\n";
    exit(1);
}

echo "PASSOU: acesso bloqueado.\n";


echo "\n=== TESTE 5: CURSO INEXISTENTE ===\n";

$nonExistentCourseId = 999999999;

if ($service->canAccess($userId, $nonExistentCourseId)) {
    echo "FALHOU: curso inexistente não deveria permitir acesso.\n";
    exit(1);
}

echo "PASSOU: acesso bloqueado.\n";


echo "\n=== TESTE 6: VÍNCULO INEXISTENTE ===\n";

$nonExistentUserId = 999999999;

if ($service->canAccess($nonExistentUserId, $courseId)) {
    echo "FALHOU: usuário sem vínculo não deveria ter acesso.\n";
    exit(1);
}

echo "PASSOU: acesso bloqueado.\n";


/*
 * Restaura o curso e o vínculo para o estado original.
 */
$stmt = $db->prepare("
    UPDATE courses
    SET is_active = 1
    WHERE id = :course_id
");

$stmt->execute([
    'course_id' => $courseId,
]);

$stmt = $db->prepare("
    UPDATE user_courses
    SET status = 'ACTIVE'
    WHERE user_id = :user_id
      AND course_id = :course_id
");

$stmt->execute([
    'user_id' => $userId,
    'course_id' => $courseId,
]);


echo "\n=== RESTAURAÇÃO ===\n";
echo "Curso restaurado para ATIVO.\n";
echo "Vínculo restaurado para ACTIVE.\n";


echo "\n=== TESTE FINAL ===\n";

if (!$service->canAccess($userId, $courseId)) {
    echo "FALHOU: acesso deveria estar permitido após restauração.\n";
    exit(1);
}

echo "PASSOU: acesso permitido novamente.\n";

echo "\nTestes do CourseAccessService concluídos com sucesso.\n";
