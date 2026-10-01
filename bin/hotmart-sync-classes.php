<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
date_default_timezone_set('America/Sao_Paulo');

$lock = false;
try {
    $db = Leilabrito\PortalAluno\Core\Database::getConnection();
    $lockName = 'portal-class-sync-' . substr(hash('sha256', (string) $db->query('SELECT DATABASE()')->fetchColumn()), 0, 32);
    $stmt = $db->prepare('SELECT GET_LOCK(?, 0)');
    $stmt->execute([$lockName]);
    $lock = (int) $stmt->fetchColumn() === 1;
    if (!$lock) {
        throw new RuntimeException('Outra sincronização de turmas está em andamento.');
    }
    $repository = new Leilabrito\PortalAluno\Repositories\ClubEnrollmentRepository($db);
    $service = new Leilabrito\PortalAluno\Services\ClubEnrollmentService($repository, new Leilabrito\PortalAluno\Services\HotmartApiService());
    $options = getopt('', ['pending', 'email:']);
    $pendingOnly = isset($options['pending']);
    $email = $options['email'] ?? null;
    if ($email !== null && (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        throw new RuntimeException('Informe um e-mail válido em --email.');
    }
    $afterId = 0;
    $processed = 0;
    $failed = 0;
    while (($batch = $repository->batch($afterId, $pendingOnly, $email)) !== []) {
        foreach ($batch as $enrollment) {
            $afterId = (int) $enrollment['id'];
            if ($service->reconcile((int) $enrollment['user_id'], (int) $enrollment['course_id'])) {
                $processed++;
            } else {
                $failed++;
                $current = $repository->snapshot((int) $enrollment['user_id'], (int) $enrollment['course_id']);
                fwrite(STDERR, 'Matrícula #' . $afterId . ': ' . ($current['sync_error'] ?? 'Matrícula alterada durante a consulta; repita a sincronização.') . PHP_EOL);
            }
        }
    }
    echo "Consultas concluídas: {$processed}. Falhas: {$failed}. Turmas não cadastradas permanecem pendentes em Acessos.\n";
    $exitCode = $failed === 0 ? 0 : 1;
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    if ($lock) {
        $db->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]);
    }
}
exit($exitCode);
