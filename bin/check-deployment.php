<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
date_default_timezone_set('America/Sao_Paulo');

try {
    if (($_ENV['APP_ENV'] ?? '') !== 'production'
        || rtrim($_ENV['APP_URL'] ?? '', '/') !== 'https://aluno.leilabrito.com.br') {
        throw new RuntimeException('Configure APP_ENV=production e APP_URL=https://aluno.leilabrito.com.br.');
    }
    $db = Leilabrito\PortalAluno\Core\Database::getConnection();
    // Somente leitura. As migrations continuam sendo uma etapa explícita do deploy.
    foreach (['users', 'courses', 'user_courses', 'tools', 'course_tools', 'auth_tokens', 'hotmart_events', 'hotmart_purchases'] as $table) {
        $db->query('SELECT 1 FROM `' . $table . '` LIMIT 0');
    }
    $db->query('SELECT access_days FROM courses LIMIT 0');
    echo "Configuração e esquema do banco disponíveis.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Publicação interrompida. Confira .env, conexão e migrations do banco.\n");
    exit(1);
}
