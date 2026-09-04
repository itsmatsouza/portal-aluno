<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Core/Bootstrap.php';

use Leilabrito\PortalAluno\Core\Database;
use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Repositories\UserRepository;
use Leilabrito\PortalAluno\Services\AuthService;

try {
    SessionManager::start();

    $db = Database::getConnection();

    $repository = new UserRepository();
    $auth = new AuthService($repository);

    /*
     * Localiza o usuário de teste.
     */
    $stmt = $db->prepare(
        'SELECT id FROM users WHERE email = :email LIMIT 1'
    );

    $stmt->execute([
        'email' => 'teste@portal.local',
    ]);

    $user = $stmt->fetch();

    if (!$user) {
        throw new RuntimeException(
            'Usuário teste não encontrado.'
        );
    }

    $userId = (int) $user['id'];

    /*
     * Garante estado inicial.
     */
    $stmt = $db->prepare(
        'UPDATE users
         SET is_active = 1,
             deleted_at = NULL
         WHERE id = :id'
    );

    $stmt->execute([
        'id' => $userId,
    ]);

    echo "=== TESTE 1: USUÁRIO ATIVO ===" . PHP_EOL;

    $result = $auth->login(
        'teste@portal.local',
        'Teste@123'
    );

    echo $result ? "PASSOU" : "FALHOU";
    echo PHP_EOL;

    $auth->logout();
    SessionManager::start();

    /*
     * Desativa o usuário.
     */
    $stmt = $db->prepare(
        'UPDATE users
         SET is_active = 0
         WHERE id = :id'
    );

    $stmt->execute([
        'id' => $userId,
    ]);

    echo PHP_EOL;
    echo "=== TESTE 2: USUÁRIO DESATIVADO ===" . PHP_EOL;

    $result = $auth->login(
        'teste@portal.local',
        'Teste@123'
    );

    echo !$result ? "PASSOU" : "FALHOU";
    echo PHP_EOL;

    /*
     * Reativa para testar soft delete.
     */
    $stmt = $db->prepare(
        'UPDATE users
         SET is_active = 1,
             deleted_at = NOW()
         WHERE id = :id'
    );

    $stmt->execute([
        'id' => $userId,
    ]);

    echo PHP_EOL;
    echo "=== TESTE 3: SOFT DELETE ===" . PHP_EOL;

    $result = $auth->login(
        'teste@portal.local',
        'Teste@123'
    );

    echo !$result ? "PASSOU" : "FALHOU";
    echo PHP_EOL;

    /*
     * Restaura usuário para os próximos testes.
     */
    $stmt = $db->prepare(
        'UPDATE users
         SET is_active = 1,
             deleted_at = NULL
         WHERE id = :id'
    );

    $stmt->execute([
        'id' => $userId,
    ]);

    echo PHP_EOL;
    echo "Usuário de teste restaurado." . PHP_EOL;

    echo "Testes de controle de acesso concluídos." . PHP_EOL;

} catch (Throwable $e) {

    echo PHP_EOL;
    echo "ERRO: " . $e->getMessage() . PHP_EOL;
    echo "Arquivo: " . $e->getFile() . PHP_EOL;
    echo "Linha: " . $e->getLine() . PHP_EOL;

    exit(1);

} finally {

    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}