<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Repositories\UserRepository;
use Leilabrito\PortalAluno\Repositories\UserCourseRepository;

function verify(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    $repository = new UserRepository();
    $first = $repository->findPaginated(perPage: 1);
    $last = $repository->findPaginated(page: PHP_INT_MAX, perPage: 1);
    verify($last['page'] === $last['total_pages'], 'Página extrema não foi limitada.');
    $negative = $repository->findPaginated(page: -1);
    verify($negative['page'] === 1, 'Página negativa não foi limitada.');

    foreach (['active', 'inactive'] as $status) {
        foreach (['ADMIN', 'ALUNO'] as $role) {
            $result = $repository->findPaginated(search: '%', status: $status, role: $role);
            foreach ($result['items'] as $user) {
                verify(!$user->isDeleted(), 'Usuário excluído na listagem.');
                verify($user->getRole() === $role, 'Filtro de perfil incorreto.');
                verify($user->isActive() === ($status === 'active'), 'Filtro de status incorreto.');
            }
        }
    }

    $empty = $repository->findPaginated(page: 999, search: bin2hex(random_bytes(32)));
    verify($empty['items'] === [] && $empty['page'] === 1 && $empty['total_pages'] === 1, 'Estado vazio incorreto.');

    if ($first['items'] !== []) {
        $user = $first['items'][0];
        $match = $repository->findPaginated(search: $user->getEmail());
        verify(in_array($user->getId(), array_map(static fn ($item) => $item->getId(), $match['items']), true), 'Busca por e-mail falhou.');
        $match = $repository->findPaginated(search: $user->getName());
        verify($match['total'] > 0, 'Busca por nome falhou.');
        $enrollments = new UserCourseRepository();
        verify(count($enrollments->findDetailsByUserId($user->getId())) === count($enrollments->findByUserId($user->getId())), 'Consulta de matrículas inconsistente.');
    }

    echo "Consultas administrativas: OK (somente leitura).\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
