<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Repositories\UserCourseRepository;

$repository = new UserCourseRepository();

$userId = 1;
$courseId = 1;

echo "=== TESTE 1: BUSCAR POR ID ===\n";

$userCourse = $repository->findById(1);

if ($userCourse === null) {
    echo "FALHOU: vínculo não encontrado.\n";
    exit(1);
}

echo "PASSOU\n";
echo "ID: " . $userCourse->getId() . "\n";
echo "Usuário: " . $userCourse->getUserId() . "\n";
echo "Curso: " . $userCourse->getCourseId() . "\n";
echo "Status: " . $userCourse->getStatus() . "\n";
echo "Tem acesso: " .
    ($userCourse->hasAccess() ? 'SIM' : 'NÃO') . "\n";


echo "\n=== TESTE 2: BUSCAR POR USUÁRIO ===\n";

$userCourses = $repository->findByUserId($userId);

if (count($userCourses) === 0) {
    echo "FALHOU: nenhum vínculo encontrado.\n";
    exit(1);
}

echo "PASSOU\n";
echo "Quantidade: " . count($userCourses) . "\n";


echo "\n=== TESTE 3: BUSCAR USUÁRIO + CURSO ===\n";

$userCourse = $repository->findByUserAndCourse(
    $userId,
    $courseId
);

if ($userCourse === null) {
    echo "FALHOU: vínculo não encontrado.\n";
    exit(1);
}

echo "PASSOU\n";
echo "Status: " . $userCourse->getStatus() . "\n";


echo "\n=== TESTE 4: ACESSO ATIVO ===\n";

$hasAccess = $repository->hasAccess(
    $userId,
    $courseId
);

if (!$hasAccess) {
    echo "FALHOU: usuário deveria ter acesso.\n";
    exit(1);
}

echo "PASSOU\n";
echo "Acesso permitido: SIM\n";


echo "\n=== TESTE 5: CURSOS ATIVOS ===\n";

$activeCourses = $repository->findActiveByUserId(
    $userId
);

if (count($activeCourses) === 0) {
    echo "FALHOU: nenhum curso ativo encontrado.\n";
    exit(1);
}

echo "PASSOU\n";
echo "Quantidade ativa: " . count($activeCourses) . "\n";


echo "\nTestes do UserCourseRepository concluídos.\n";
