<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Core\Database;
use Leilabrito\PortalAluno\Repositories\CourseRepository;
use Leilabrito\PortalAluno\Repositories\UserCourseRepository;
use Leilabrito\PortalAluno\Services\AdminCourseService;
use Leilabrito\PortalAluno\Services\CourseAccessService;

function verifyCourse(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// A tabela temporária oculta a tabela real somente nesta conexão.
$db = Database::getConnection();
$temporaryTable = false;
try {
    $schema = $db->query('SHOW CREATE TABLE courses')->fetch(PDO::FETCH_NUM)[1];
    $db->exec(preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $schema, 1));
    $temporaryTable = true;
    $courses = new CourseRepository();
    $service = new AdminCourseService($courses);
    $empty = $courses->findPaginated(PHP_INT_MAX);
    verifyCourse($empty['items'] === [] && $empty['page'] === 1, 'Estado vazio incorreto.');
    $invalid = $service->save(null, ['name' => ' ', 'ucode' => str_repeat('x', 101)]);
    verifyCourse(isset($invalid['errors']['name'], $invalid['errors']['ucode']), 'Validação de campos falhou.');
    $invalid = $service->save(null, ['name' => ['inválido'], 'description' => str_repeat('x', 65536)]);
    verifyCourse(isset($invalid['errors']['name'], $invalid['errors']['description']), 'Validação de tamanho/tipo falhou.');
    $created = $service->save(null, ['name' => ' Curso É ', 'description' => '0', 'ucode' => 'HOT-1', 'active' => '1']);
    verifyCourse($created['errors'] === [], 'Criação falhou.');
    $id = $created['id'];
    verifyCourse($courses->findById($id)->getDescription() === '0', 'Descrição alterada incorretamente.');
    $duplicate = $service->save(null, ['name' => 'Outro', 'ucode' => 'HOT-1']);
    verifyCourse(isset($duplicate['errors']['ucode']), 'Identificador duplicado aceito.');
    $updated = $service->save($id, ['name' => 'Curso atualizado', 'ucode' => 'HOT-1', 'active' => '1']);
    verifyCourse($updated['errors'] === [] && $courses->findById($id)->getName() === 'Curso atualizado', 'Edição falhou.');
    foreach (['Curso', 'HOT-1'] as $search) {
        verifyCourse($courses->findPaginated(search: $search)['total'] === 1, 'Busca falhou.');
    }
    $enrollments = new class extends UserCourseRepository {
        public function __construct() {}
        public function hasAccess(int $userId, int $courseId): bool { return true; }
    };
    $access = new CourseAccessService($enrollments, $courses);
    verifyCourse($access->canAccess(1, $id), 'Curso ativo bloqueado.');
    $courses->setActive($id, false);
    verifyCourse(!$access->canAccess(1, $id), 'Curso inativo permite acesso.');
    verifyCourse($courses->findPaginated(status: 'inactive')['total'] === 1, 'Filtro de inativos falhou.');
    verifyCourse($courses->findPaginated(status: 'active')['total'] === 0, 'Filtro de ativos falhou.');
    $courses->setActive($id, true);
    verifyCourse($access->canAccess(1, $id), 'Reativação falhou.');
    for ($i = 0; $i < 22; $i++) {
        $service->save(null, ['name' => 'Curso ' . $i]);
    }
    $last = $courses->findPaginated(PHP_INT_MAX);
    verifyCourse($last['page'] === 2 && count($last['items']) === 3, 'Limite de página incorreto.');
    $firstIds = array_map(static fn ($course) => $course->getId(), $courses->findPaginated()['items']);
    $lastIds = array_map(static fn ($course) => $course->getId(), $last['items']);
    verifyCourse(array_intersect($firstIds, $lastIds) === [], 'Paginação repete cursos.');
    $db->exec('UPDATE courses SET deleted_at = NOW() WHERE id = ' . $id);
    verifyCourse($courses->findPaginated(search: 'HOT-1')['total'] === 0, 'Curso excluído aparece na busca.');
    verifyCourse(!$access->canAccess(1, $id), 'Curso excluído permite acesso.');
    echo "Cursos: criação, edição, validação, busca, paginação e acesso OK. Dados reais preservados.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
} finally {
    if ($temporaryTable) {
        $db->exec('DROP TEMPORARY TABLE courses');
    }
}
