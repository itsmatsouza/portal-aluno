<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Repositories\CourseRepository;

$repository = new CourseRepository();

echo "=== TESTE 1: BUSCAR POR ID ===\n";

$course = $repository->findById(1);

if ($course === null) {
    echo "FALHOU: curso não encontrado.\n";
    exit(1);
}

echo "PASSOU\n";
echo "ID: " . $course->getId() . "\n";
echo "Nome: " . $course->getName() . "\n";
echo "UCODE: " . ($course->getHotmartProductUcode() ?? 'NULL') . "\n";
echo "Disponível: " . ($course->isAvailable() ? 'SIM' : 'NÃO') . "\n";


echo "\n=== TESTE 2: BUSCAR POR UCODE ===\n";

$course = $repository->findByHotmartProductUcode(
    'TEST_UCODE_001'
);

if ($course === null) {
    echo "FALHOU: curso não encontrado por UCODE.\n";
    exit(1);
}

echo "PASSOU\n";
echo "ID: " . $course->getId() . "\n";
echo "Nome: " . $course->getName() . "\n";


echo "\n=== TESTE 3: CURSOS DISPONÍVEIS ===\n";

$courses = $repository->findAllAvailable();

echo "Quantidade encontrada: " . count($courses) . "\n";

if (count($courses) === 0) {
    echo "FALHOU: nenhum curso disponível encontrado.\n";
    exit(1);
}

echo "PASSOU\n";

echo "\nTestes do CourseRepository concluídos.\n";
