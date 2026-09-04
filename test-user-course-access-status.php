<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Core\Database;
use Leilabrito\PortalAluno\Repositories\UserCourseRepository;

$repository = new UserCourseRepository();
$db = Database::getConnection();

$userId = 1;
$courseId = 1;

$statuses = [
    'CANCELLED',
    'REFUNDED',
    'CHARGEBACK',
    'EXPIRED',
    'SUSPENDED',
];

echo "=== TESTE DE STATUS DE ACESSO ===\n\n";

foreach ($statuses as $status) {

    echo "Status: {$status}\n";

    $stmt = $db->prepare("
        UPDATE user_courses
        SET status = :status
        WHERE user_id = :user_id
          AND course_id = :course_id
    ");

    $stmt->execute([
        'status' => $status,
        'user_id' => $userId,
        'course_id' => $courseId,
    ]);

    $hasAccess = $repository->hasAccess(
        $userId,
        $courseId
    );

    if ($hasAccess) {
        echo "FALHOU: acesso foi permitido.\n";
        exit(1);
    }

    echo "PASSOU: acesso bloqueado.\n\n";
}

/*
 * Restaura o vínculo para ACTIVE.
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

echo "Status restaurado para ACTIVE.\n";

$hasAccess = $repository->hasAccess(
    $userId,
    $courseId
);

if (!$hasAccess) {
    echo "FALHOU: acesso deveria estar liberado após restauração.\n";
    exit(1);
}

echo "Acesso ACTIVE confirmado.\n";
echo "\nTeste de status concluído com sucesso.\n";
