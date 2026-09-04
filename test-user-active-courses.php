<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Leilabrito\PortalAluno\Repositories\UserCourseRepository;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$repository = new UserCourseRepository();

echo "=== TESTE: CURSOS ATIVOS DO USUÁRIO ===\n";

$courses = $repository->findActiveCoursesByUser(1);

echo "Quantidade encontrada: " . count($courses) . "\n";

foreach ($courses as $course) {
    echo "ID: " . $course->getId() . "\n";
    echo "Nome: " . $course->getName() . "\n";
    echo "Disponível: " . ($course->isAvailable() ? 'SIM' : 'NÃO') . "\n";
    echo "-------------------------\n";
}

echo "Teste concluído.\n";
