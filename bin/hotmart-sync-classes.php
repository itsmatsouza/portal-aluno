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
    $api = new Leilabrito\PortalAluno\Services\HotmartApiService();
    $service = new Leilabrito\PortalAluno\Services\ClubEnrollmentService($repository, $api);
    $options = getopt('', ['pending', 'email:']);
    $pendingOnly = isset($options['pending']);
    $email = $options['email'] ?? null;
    if ($email !== null && (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        throw new RuntimeException('Informe um e-mail válido em --email.');
    }
    $discovery = ['pending' => [], 'deferred_enrollments' => []];
    $discoveryFailed = false;
    if (!$pendingOnly) {
        try {
            $importer = new Leilabrito\PortalAluno\Services\HotmartStudentImportService(
                $db, new Leilabrito\PortalAluno\Repositories\HotmartRepository($db)
            );
            $discovery = (new Leilabrito\PortalAluno\Services\ClubStudentDiscoveryService(
                $repository, $api, $importer
            ))->discover($email);
            echo "Descoberta: {$discovery['imported']} importadas; {$discovery['existing']} compras conhecidas; "
                . count($discovery['pending']) . " pendências.\n";
        } catch (Throwable $error) {
            $discoveryFailed = true;
            $discovery['pending'][] = ['course_id' => null, 'email' => null,
                'reason' => 'Descoberta interrompida; confira configuração, API e banco.'];
            fwrite(STDERR, "Falha na descoberta; reconciliação das matrículas existentes continuará.\n");
        }
        // Relatório privado; uma execução filtrada não substitui o relatório completo.
        $directory = dirname(__DIR__) . '/storage/logs';
        $temporary = false;
        try {
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new RuntimeException('Não foi possível criar diretório de relatório.');
            }
            $temporary = tempnam($directory, '.discovery-');
            if ($temporary === false || !chmod($temporary, 0600)) {
                throw new RuntimeException('Não foi possível criar relatório privado.');
            }
            $json = json_encode(['generated_at' => date(DATE_ATOM), 'email_filter' => $email,
                'failed' => $discoveryFailed, 'pending' => $discovery['pending']],
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $target = $directory . '/hotmart-discovery' . ($email === null ? '' : '-filtered') . '.json';
            if (file_put_contents($temporary, $json) === false || !rename($temporary, $target)) {
                throw new RuntimeException('Não foi possível salvar relatório de descoberta.');
            }
        } catch (Throwable $error) {
            $discoveryFailed = true;
            fwrite(STDERR, "Falha ao salvar relatório privado de descoberta.\n");
        } finally {
            if ($temporary !== false && is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
    $deferred = array_fill_keys($discovery['deferred_enrollments'], true);
    $afterId = 0;
    $processed = 0;
    $failed = 0;
    while (($batch = $repository->batch($afterId, $pendingOnly, $email)) !== []) {
        foreach ($batch as $enrollment) {
            $afterId = (int) $enrollment['id'];
            if (isset($deferred[$afterId])) {
                continue;
            }
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
    $exitCode = $failed === 0 && !$discoveryFailed && $discovery['pending'] === [] ? 0 : 1;
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    if ($lock) {
        $db->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]);
    }
}
exit($exitCode);
