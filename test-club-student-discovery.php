<?php

declare(strict_types=1);

use Leilabrito\PortalAluno\Repositories\ClubEnrollmentRepository;
use Leilabrito\PortalAluno\Repositories\HotmartRepository;
use Leilabrito\PortalAluno\Services\ClubEnrollmentService;
use Leilabrito\PortalAluno\Services\ClubStudentDiscoveryService;
use Leilabrito\PortalAluno\Services\HotmartApiService;
use Leilabrito\PortalAluno\Services\HotmartStudentImportService;

require __DIR__ . '/vendor/autoload.php';
// Somente banco descartável explicitamente informado; nunca usa o banco do portal.
$socket = getenv('PORTAL_TEST_SOCKET');
if (!$socket) {
    fwrite(STDERR, "Informe PORTAL_TEST_SOCKET de um MariaDB de testes.\n");
    exit(1);
}
date_default_timezone_set('America/Sao_Paulo');
$db = new PDO("mysql:unix_socket=$socket;charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$schema = 'portal_discovery_test_' . bin2hex(random_bytes(6));
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
class DiscoveryApi extends HotmartApiService
{
    public array $members = [];
    public array $history = [];
    public bool $repeat = false;
    public int $salesCalls = 0;
    public function products(?string $pageToken = null): array
    {
        return ['items' => [['id' => 1, 'ucode' => 'course', 'created_at' => (int) (strtotime('-2 months') * 1000)]]];
    }
    public function clubMembers(string $subdomain, ?string $pageToken = null): array
    {
        if ($this->repeat) {
            return ['items' => $this->members, 'page_info' => ['next_page_token' => 'repeated']];
        }
        return $pageToken === null
            ? ['items' => [], 'page_info' => ['next_page_token' => 'members']]
            : ['items' => $this->members];
    }
    public function clubUsers(string $subdomain, string $email, ?string $pageToken = null): array
    {
        return ['items' => array_values(array_filter($this->members, fn ($m) => $m['email'] === $email))];
    }
    public function sales(array $filters = []): array
    {
        $this->salesCalls++;
        if (!isset($filters['page_token'])) {
            return ['items' => [], 'page_info' => ['next_page_token' => 'sales']];
        }
        return ['items' => array_values(array_filter($this->history, fn ($s) =>
            $s['purchase']['approved_date'] >= $filters['start_date']
            && $s['purchase']['approved_date'] <= $filters['end_date']))];
    }
}
function sale(string $email, string $transaction, int $days, string $status = 'APPROVED', int $product = 1): array
{
    return ['buyer' => ['email' => $email, 'name' => 'Aluno Teste'], 'product' => ['id' => $product],
        'purchase' => ['transaction' => $transaction, 'status' => $status,
            'approved_date' => strtotime("-$days days") * 1000]];
}
function member(string $email, string $class = 'X', string $status = 'ACTIVE'): array
{
    return ['email' => $email, 'class_id' => $class, 'status' => $status];
}
try {
    $db->exec("USE `$schema`");
    (new Leilabrito\PortalAluno\Database\MigrationRunner($db, __DIR__ . '/database/migrations'))->run();
    $db->exec("INSERT INTO courses (id,name,hotmart_product_ucode) VALUES (1,'Curso','course')");
    $db->exec("INSERT INTO course_classes (id,course_id,name,hotmart_class_id,is_lifetime,access_days) VALUES
        (1,1,'X','X',0,365),(2,1,'Y','Y',1,NULL),(3,1,'Admin','admin',1,NULL)");
    $_ENV['HOTMART_COURSE_SUBDOMAINS'] = '{"course":"club"}';
    $repository = new ClubEnrollmentRepository($db);
    $importer = new HotmartStudentImportService($db, new HotmartRepository($db));
    $api = new DiscoveryApi();
    $discovery = new ClubStudentDiscoveryService($repository, $api, $importer);
    $club = new ClubEnrollmentService($repository, $api);
    check(count($repository->discoverableClasses()) === 2, 'Excluir turma admin da descoberta.');
    $api->members = [member('new@example.invalid'), member('missing@example.invalid'),
        member('blockedclub@example.invalid', 'X', 'BLOCKED'), member('outside@example.invalid', 'outside'),
        member('reserved@example.invalid', 'admin')];
    $api->history = [sale('new@example.invalid', 'OLD', 25), sale('new@example.invalid', 'LATEST', 5, 'COMPLETE'),
        sale('new@example.invalid', 'REFUNDED', 1, 'REFUNDED'), sale('new@example.invalid', 'OTHER', 1, 'APPROVED', 2),
        sale('stranger@example.invalid', 'STRANGER', 1)];
    $report = $discovery->discover();
    check($report['imported'] === 1 && count($report['pending']) === 1, 'Criar apenas aluno com compra válida.');
    check((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 1, 'Sem contas para bloqueados, turmas externas ou sem compra.');
    $user = $repository->discoveryUser('new@example.invalid');
    $id = (int) $user['id'];
    $enrollment = $repository->snapshot($id, 1);
    check($enrollment['hotmart_transaction_id'] === 'LATEST', 'Última compra válida do mesmo curso.');
    check($enrollment['purchased_at'] === date('Y-m-d H:i:s', intdiv($api->history[1]['purchase']['approved_date'], 1000)), 'Data original da compra.');
    check($enrollment['class_id'] === null, 'Aguardar confirmação independente do Club.');
    check($club->reconcile($id, 1), 'Confirmar turma após importar.');
    $calls = $api->salesCalls;
    $report = $discovery->discover('new@example.invalid');
    check($report['unchanged'] === 1 && $api->salesCalls === $calls, 'Reexecução sem duplicação ou consulta comercial desnecessária.');
    $api->members[0]['class_id'] = 'Y';
    $report = $discovery->discover('new@example.invalid');
    check($report['existing'] === 1 && $club->reconcile($id, 1), 'Transferir matrícula existente.');
    $updated = $repository->snapshot($id, 1);
    check((int) $updated['class_id'] === 2 && $updated['purchased_at'] === $enrollment['purchased_at'], 'Transferência preserva início do prazo.');
    $api->members[0]['class_id'] = 'X';
    $savedHistory = $api->history;
    $api->history = [];
    $report = $discovery->discover('new@example.invalid');
    check(count($report['deferred_enrollments']) === 1 && !$club->reconcile($id, 1), 'Compra ausente não libera acesso pelo cron de pendências.');
    $api->history = $savedHistory;
    $discovery->discover('new@example.invalid');
    check($club->reconcile($id, 1), 'Compra confirmada resolve pendência.');
    $db->exec("UPDATE user_courses SET is_manual=1 WHERE user_id=$id");
    $report = $discovery->discover('new@example.invalid');
    check($report['skipped'] === 1, 'Preservar concessão manual.');
    $db->exec("UPDATE user_courses SET is_manual=0 WHERE user_id=$id");
    $db->exec("UPDATE users SET is_active=0 WHERE id=$id");
    check($discovery->discover('new@example.invalid')['skipped'] === 1, 'Preservar bloqueio local.');
    $db->exec("UPDATE users SET is_active=1,deleted_at=NOW() WHERE id=$id");
    check($discovery->discover('new@example.invalid')['skipped'] === 1, 'Não recriar usuário excluído.');
    $db->exec("UPDATE users SET deleted_at=NULL WHERE id=$id");
    $db->exec("INSERT INTO users (name,email,password_hash,role) VALUES ('Existente','existing@example.invalid','unused','ALUNO')");
    $api->members[] = member('existing@example.invalid');
    $api->history[] = sale('existing@example.invalid', 'EXISTING_USER', 3);
    check($discovery->discover('existing@example.invalid')['imported'] === 1, 'Matricular conta existente sem acesso.');
    check((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 2, 'Não duplicar conta existente.');
    $db->exec("UPDATE hotmart_purchases SET status='REFUNDED' WHERE transaction_id='LATEST'");
    $db->exec("UPDATE user_courses SET status='REFUNDED' WHERE user_id=$id");
    $api->members[0]['class_id'] = 'Y';
    $discovery->discover('new@example.invalid');
    $club->reconcile($id, 1);
    check($repository->snapshot($id, 1)['status'] === 'REFUNDED', 'API atrasada não reativa reembolso local.');
    $db->exec("UPDATE hotmart_purchases SET status='CHARGEBACK' WHERE transaction_id='LATEST'");
    $db->exec("UPDATE user_courses SET status='CHARGEBACK' WHERE user_id=$id");
    $api->members[0]['class_id'] = 'X';
    $discovery->discover('new@example.invalid');
    $club->reconcile($id, 1);
    check($repository->snapshot($id, 1)['status'] === 'CHARGEBACK', 'Preservar chargeback local.');
    $api->members[0]['class_id'] = 'Y';
    $api->history[] = sale('new@example.invalid', 'RENEWAL', 1);
    check($discovery->discover('new@example.invalid')['imported'] === 1, 'Transferência encontra compra mais recente.');
    $club->reconcile($id, 1);
    check($repository->snapshot($id, 1)['hotmart_transaction_id'] === 'RENEWAL', 'Renovação válida selecionada pelo importador.');
    $api->members[0]['class_id'] = 'X';
    $discovery->discover('new@example.invalid');
    $club->reconcile($id, 1);
    $db->exec('UPDATE course_classes SET access_days=1 WHERE id=1');
    check($db->query("SELECT status FROM enrollment_access WHERE user_id=$id")->fetchColumn() === 'EXPIRED', 'Turma com prazo vencido não libera acesso após transferência.');
    $api->members[] = member('duplicate@example.invalid');
    $api->members[] = member('duplicate@example.invalid', 'Y');
    $api->history[] = sale('duplicate@example.invalid', 'DUPLICATE', 1);
    check(count($discovery->discover('duplicate@example.invalid')['pending']) === 1, 'Identidade ambígua exige revisão.');
    check($repository->discoveryUser('duplicate@example.invalid') === null, 'Ambiguidade não cria conta.');
    $api->repeat = true;
    $before = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    check(count($discovery->discover()['pending']) > 0, 'Paginação repetida vira pendência.');
    check((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === $before, 'Lista incompleta não cadastra contas.');
    echo "$checks verificações passaram.\n";
} finally {
    $db->exec("DROP DATABASE `$schema`");
}
