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
    $db->exec("INSERT INTO users (id,name,email,password_hash,role) VALUES (1,'Aluno','manual@example.invalid','unused','ALUNO')");
    $db->exec("INSERT INTO courses (id,name) VALUES (1,'Curso')");
    $db->exec("INSERT INTO course_classes (id,course_id,name,hotmart_class_id,is_lifetime) VALUES (1,1,'Time','time',1)");
    $db->exec("INSERT INTO user_courses (user_id,course_id,status,class_id,is_manual,access_expires_at) VALUES (1,1,'ACTIVE',1,1,DATE_ADD(NOW(), INTERVAL 1 DAY))");
    $row = $db->query('SELECT * FROM enrollment_access')->fetch();
    check($row['status'] === 'ACTIVE' && $row['access_expires_at'] !== null && !$row['is_lifetime'], 'Manual deve mostrar vencimento individual e conceder acesso antes dele.');
    check((int) $db->query('SELECT COUNT(*) FROM tool_class_access')->fetchColumn() === 1, 'Concessão válida libera ferramentas da turma.');
    $db->exec("UPDATE user_courses SET access_expires_at=NOW()");
    check($db->query('SELECT status FROM enrollment_access')->fetchColumn() === 'EXPIRED', 'Expira exatamente no limite.');
    check((int) $db->query('SELECT COUNT(*) FROM tool_class_access')->fetchColumn() === 0, 'Vencimento revoga ferramentas.');
    $db->exec("UPDATE user_courses SET access_expires_at=NULL");
    check($db->query('SELECT status FROM enrollment_access')->fetchColumn() === 'ACTIVE', 'Manual sem data mantém regra vitalícia.');
    $db->exec("UPDATE user_courses SET is_manual=0,club_status='ACTIVE',sync_pending=0,access_expires_at=NOW()");
    $row = $db->query('SELECT * FROM enrollment_access')->fetch();
    check($row['status'] === 'ACTIVE' && $row['access_expires_at'] === null && $row['is_lifetime'], 'Matrícula comercial ignora coluna antiga e mantém regra da turma.');
    $db->exec("UPDATE user_courses SET is_manual=1,status='SUSPENDED',access_expires_at=DATE_ADD(NOW(),INTERVAL 1 DAY)");
    check($db->query('SELECT status FROM enrollment_access')->fetchColumn() === 'SUSPENDED', 'Prazo manual não contorna suspensão.');
    echo "$checks verificações passaram.\n";
} finally {
    $db->exec("DROP DATABASE `$schema`");
}
