<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Core\Database;
use Leilabrito\PortalAluno\Repositories\ToolRepository;
use Leilabrito\PortalAluno\Services\CourseToolService;

function verifyCourseTools(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$db = Database::getConnection();
$temporaryTables = [];
try {
    // Tabelas isoladas nesta conexão; nenhum registro real é alterado.
    $schemas = [
        'courses' => 'id INT PRIMARY KEY, is_active TINYINT DEFAULT 1, deleted_at DATETIME NULL',
        'tools' => 'id INT PRIMARY KEY, name VARCHAR(200), description TEXT, slug VARCHAR(100), url VARCHAR(500),
            is_active TINYINT DEFAULT 1, deleted_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP',
        'course_tools' => 'id INT AUTO_INCREMENT PRIMARY KEY, course_id INT, tool_id INT, is_active TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (course_id, tool_id)',
        'user_courses' => 'user_id INT, course_id INT, status VARCHAR(20)',
    ];
    foreach ($schemas as $table => $schema) {
        $db->exec("CREATE TEMPORARY TABLE {$table} ({$schema}) ENGINE=InnoDB");
        $temporaryTables[] = $table;
    }
    $db->exec('INSERT INTO courses (id) VALUES (1), (2)');
    $db->exec("INSERT INTO tools (id, name, slug, url, is_active) VALUES
        (1, 'Cálculo', 'calculo', '/tool/calculo', 1), (2, 'Planilha', 'planilha', '/tool/planilha', 0)");
    $db->exec("INSERT INTO user_courses VALUES (1, 1, 'ACTIVE'), (1, 2, 'ACTIVE')");
    $repository = new ToolRepository();
    $service = new CourseToolService($repository);
    $service->saveCourseTools(1, ['1', '2', '1']);
    verifyCourseTools(count($repository->findActiveByCourseId(1)) === 1, 'Ferramenta inativa liberada.');
    verifyCourseTools($repository->hasActiveAccessForUser(1, 1), 'Vínculo não concedeu acesso.');
    $originalId = $db->query('SELECT id FROM course_tools WHERE course_id = 1 AND tool_id = 1')->fetchColumn();
    $service->saveCourseTools(2, ['1']);
    $service->saveCourseTools(1, []);
    verifyCourseTools($repository->hasActiveAccessForUser(1, 1), 'Outro curso perdeu acesso.');
    verifyCourseTools(count($repository->findActiveByCourseId(1)) === 0, 'Desvinculação falhou.');
    $service->saveCourseTools(1, ['1']);
    verifyCourseTools($originalId === $db->query('SELECT id FROM course_tools WHERE course_id = 1 AND tool_id = 1')->fetchColumn(), 'Reativação recriou vínculo.');
    foreach ([['999'], ['-1'], [['1']], '1'] as $invalid) {
        try {
            $service->saveCourseTools(1, $invalid);
            throw new RuntimeException('Seleção inválida aceita.');
        } catch (InvalidArgumentException $expected) {
            verifyCourseTools(count($repository->findActiveByCourseId(1)) === 1, 'Erro alterou vínculos salvos.');
        }
    }
    $db->exec('UPDATE tools SET deleted_at = NOW() WHERE id = 2');
    verifyCourseTools(count($service->getToolsForAdministration(1)) === 1, 'Ferramenta excluída no catálogo.');
    try {
        $service->saveCourseTools(1, ['2']);
        throw new RuntimeException('Ferramenta excluída aceita.');
    } catch (InvalidArgumentException $expected) {
        verifyCourseTools(count($repository->findActiveByCourseId(1)) === 1, 'Validação não preservou vínculo.');
    }
    $service->saveCourseTools(2, []);
    $service->saveCourseTools(1, []);
    verifyCourseTools(!$repository->hasActiveAccessForUser(1, 1), 'Acesso mantido sem vínculo.');
    $db->exec('UPDATE courses SET deleted_at = NOW() WHERE id = 1');
    try {
        $service->saveCourseTools(1, ['1']);
        throw new RuntimeException('Curso excluído aceito.');
    } catch (InvalidArgumentException $expected) {
        verifyCourseTools(!$db->inTransaction(), 'Transação permaneceu aberta.');
    }
    echo "Vínculos: seleção, reativação, validação e acesso por outro curso OK. Dados reais preservados.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
} finally {
    foreach (array_reverse($temporaryTables) as $table) {
        $db->exec("DROP TEMPORARY TABLE {$table}");
    }
}
