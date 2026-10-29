<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$config = require __DIR__ . '/config/database.php';
if (!in_array($config['host'], ['127.0.0.1', 'localhost'], true) || ($_ENV['APP_ENV'] ?? '') === 'production') {
    fwrite(STDERR, "Execute somente com configuração de banco local.\n");
    exit(1);
}
$socket = getenv('PORTAL_TEST_SOCKET');
$dsn = $socket ? "mysql:unix_socket=$socket;charset=utf8mb4"
    : "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
$db = new PDO($dsn, $socket ? 'root' : $config['username'], $socket ? '' : $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$schema = 'portal_admin_test_' . bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$schema`");
$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}
try {
    $db->exec("USE `$schema`");
    (new Leilabrito\PortalAluno\Database\MigrationRunner($db, __DIR__ . '/database/migrations'))->run();
    $db->exec("INSERT INTO users (id,name,email,password_hash,role) VALUES
        (1,'Admin','admin@example.invalid','unused','ADMIN'),
        (2,'Aluno','aluno@example.invalid','unused','ALUNO')");
    $db->exec("INSERT INTO courses (id,name) VALUES (1,'Curso')");
    $db->exec("INSERT INTO course_classes (id,course_id,name,hotmart_class_id,is_lifetime) VALUES
        (1,1,'Admin','admin',1),(2,1,'Alunos','students',1)");
    $db->exec("INSERT INTO tools (id,name,slug,url,is_active) VALUES
        (1,'Admin tool','admin-tool','/tool/admin-tool',1),(2,'Student tool','student-tool','/tool/student-tool',1)");
    $db->exec('INSERT INTO class_tools (class_id,tool_id) VALUES (1,1),(2,2)');
    $tools = new Leilabrito\PortalAluno\Repositories\ToolRepository($db);
    check($tools->hasActiveAccessForUser(1, 1), 'Admin existente precisa da ferramenta da turma admin.');
    check(!$tools->hasActiveAccessForUser(1, 2), 'Admin não recebe todas as ferramentas indiscriminadamente.');
    check(count($tools->findActiveForUser(1)) === 1, 'Listagem deve mostrar a ferramenta liberada.');
    check($tools->findActiveForUser(1, []) === [], 'Filtro vazio de cursos mantém comportamento anterior.');
    check(!$tools->hasActiveAccessForUser(2, 1), 'Aluno não pode usar turma reservada.');
    $db->exec("INSERT INTO users (id,name,email,password_hash,role) VALUES (3,'Novo','new@example.invalid','unused','ADMIN')");
    check($tools->hasActiveAccessForUser(3, 1), 'Novo admin recebe associação automaticamente.');
    $db->exec("UPDATE users SET role='ALUNO' WHERE id=3");
    check(!$tools->hasActiveAccessForUser(3, 1), 'Remover papel revoga acesso administrativo.');
    $db->exec('UPDATE users SET is_active=0 WHERE id=1');
    check(!$tools->hasActiveAccessForUser(1, 1), 'Admin bloqueado não pode acessar.');
    $db->exec('UPDATE users SET is_active=1,deleted_at=NOW() WHERE id=1');
    check(!$tools->hasActiveAccessForUser(1, 1), 'Admin excluído não pode acessar.');
    $db->exec('UPDATE users SET deleted_at=NULL WHERE id=1');
    $db->exec('UPDATE tools SET is_active=0 WHERE id=1');
    check(!$tools->hasActiveAccessForUser(1, 1), 'Ferramenta inativa não pode abrir.');
    $db->exec('UPDATE tools SET is_active=1,deleted_at=NOW() WHERE id=1');
    check(!$tools->hasActiveAccessForUser(1, 1), 'Ferramenta excluída não pode abrir.');
    $db->exec('UPDATE tools SET deleted_at=NULL WHERE id=1');
    $db->exec("INSERT INTO user_courses (user_id,course_id,status,class_id,club_status,sync_pending) VALUES (2,1,'ACTIVE',2,'ACTIVE',0)");
    check($tools->hasActiveAccessForUser(2, 2), 'Acesso comercial de aluno deve continuar funcionando.');
    $db->exec("UPDATE user_courses SET club_status='BLOCKED' WHERE user_id=2");
    check(!$tools->hasActiveAccessForUser(2, 2), 'Bloqueio do Club precisa continuar valendo.');
    $db->exec("UPDATE user_courses SET class_id=1,club_status='ACTIVE' WHERE user_id=2");
    check(!$tools->hasActiveAccessForUser(2, 1), 'Matrícula comercial não pode contornar a turma reservada.');
    $clubRepository = new Leilabrito\PortalAluno\Repositories\ClubEnrollmentRepository($db);
    $oldSnapshot = $clubRepository->snapshot(2, 1);
    $db->exec("UPDATE user_courses SET is_manual=1,club_status=NULL,sync_pending=0 WHERE user_id=2");
    check($tools->hasActiveAccessForUser(2, 1), 'Aluno com concessão manual explícita pode acessar a turma admin.');
    check($clubRepository->batch(0, false) === [], 'Cron deve ignorar matrícula manual.');
    check(!$clubRepository->apply($oldSnapshot, 'students', 'ACTIVE', null), 'Resposta antiga do Club não pode substituir concessão manual.');
    $club = new Leilabrito\PortalAluno\Services\ClubEnrollmentService($clubRepository,
        new class extends Leilabrito\PortalAluno\Services\HotmartApiService {
            public function clubUsers(string $subdomain, string $email, ?string $pageToken = null): array
            {
                throw new RuntimeException('Não deve consultar o Club para matrícula manual.');
            }
        });
    check($club->reconcile(2, 1), 'Reconciliação individual deve preservar matrícula manual.');
    $db->beginTransaction();
    (new Leilabrito\PortalAluno\Repositories\HotmartRepository($db))->saveAccess(2, 1, 'FAKE', 'REFUNDED', null, null);
    $db->commit();
    check($tools->hasActiveAccessForUser(2, 1), 'Webhook não deve substituir concessão manual.');
    $db->exec("UPDATE user_courses SET status='SUSPENDED' WHERE user_id=2");
    check(!$tools->hasActiveAccessForUser(2, 1), 'Suspensão manual ainda revoga acesso.');
    $db->exec('UPDATE courses SET deleted_at=NOW() WHERE id=1');
    check(!$tools->hasActiveAccessForUser(1, 1), 'Curso excluído revoga ferramentas administrativas.');
    $db->exec('UPDATE courses SET deleted_at=NULL WHERE id=1');
    $db->exec('DELETE FROM class_tools WHERE class_id=1');
    check(!$tools->hasActiveAccessForUser(1, 1), 'Remover ferramenta da turma revoga acesso.');
    check((int) $db->query('SELECT COUNT(*) FROM user_courses WHERE user_id IN (1,3)')->fetchColumn() === 0,
        'Associação administrativa não pode criar compras ou alterar matrículas.');
    echo "$checks verificações passaram.\n";
} finally {
    $db->exec("DROP DATABASE `$schema`");
}
