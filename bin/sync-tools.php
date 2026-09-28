<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
date_default_timezone_set('America/Sao_Paulo');
$db = Leilabrito\PortalAluno\Core\Database::getConnection();
$catalog = dirname(__DIR__) . '/database/tools-catalog.json';
$mode = $argv[1] ?? '';

try {
    if ($mode === 'export') {
        $entries = [];
        foreach ($db->query('SELECT * FROM tools WHERE deleted_at IS NULL')->fetchAll() as $tool) {
            if (!preg_match('/^[a-z0-9-]+$/D', $tool['slug']) || !is_file(dirname(__DIR__) . '/storage/tools/' . $tool['slug'] . '/index.html')) {
                continue;
            }
            $query = $db->prepare('SELECT c.name, c.description, c.hotmart_product_ucode, c.is_active, ct.is_active AS link_active FROM courses c JOIN course_tools ct ON ct.course_id = c.id WHERE ct.tool_id = ? AND c.deleted_at IS NULL');
            $query->execute([$tool['id']]);
            $courses = $query->fetchAll();
            foreach ($courses as $course) {
                if (empty($course['hotmart_product_ucode'])) {
                    throw new RuntimeException('Curso sem identificador Hotmart: ' . $course['name']);
                }
            }
            $entries[] = ['name' => $tool['name'], 'description' => $tool['description'], 'slug' => $tool['slug'], 'url' => '/tool/' . $tool['slug'], 'is_active' => (int) $tool['is_active'], 'courses' => $courses];
        }
        if (file_put_contents($catalog, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL) === false) {
            throw new RuntimeException('Falha ao gravar catálogo.');
        }
        echo count($entries) . " ferramentas exportadas.\n";
        exit;
    }
    if ($mode !== 'import') {
        throw new RuntimeException('Uso: php bin/sync-tools.php export|import');
    }
    $entries = json_decode(file_get_contents($catalog), true, 512, JSON_THROW_ON_ERROR);
    $backupDir = $argv[2] ?? dirname(__DIR__) . '/storage/backups';
    if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true)) {
        throw new RuntimeException('Falha ao criar diretório de backup.');
    }
    $db->beginTransaction();
    $backup = [];
    foreach (['tools', 'courses', 'course_tools'] as $table) {
        $backup[$table] = $db->query('SELECT * FROM ' . $table)->fetchAll();
    }
    $backupPath = $backupDir . '/tools-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.json';
    if (file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false || !chmod($backupPath, 0600)) {
        throw new RuntimeException('Falha ao preservar backup.');
    }
    foreach ($entries as $tool) {
        if (!preg_match('/^[a-z0-9-]+$/D', $tool['slug']) || !is_file(dirname(__DIR__) . '/storage/tools/' . $tool['slug'] . '/index.html')) {
            throw new RuntimeException('Código ausente para ferramenta: ' . $tool['slug']);
        }
        $query = $db->prepare('SELECT id, deleted_at FROM tools WHERE slug = ?');
        $query->execute([$tool['slug']]);
        $existing = $query->fetch();
        if ($existing && $existing['deleted_at'] !== null) {
            throw new RuntimeException('Ferramenta removida em produção: ' . $tool['slug']);
        }
        if ($existing) {
            $toolId = $existing['id'];
            $db->prepare('UPDATE tools SET name = ?, description = ?, url = ?, is_active = ? WHERE id = ?')->execute([$tool['name'], $tool['description'], $tool['url'], $tool['is_active'], $toolId]);
        } else {
            $db->prepare('INSERT INTO tools (name, description, slug, url, is_active) VALUES (?, ?, ?, ?, ?)')->execute([$tool['name'], $tool['description'], $tool['slug'], $tool['url'], $tool['is_active']]);
            $toolId = $db->lastInsertId();
        }
        foreach ($tool['courses'] as $course) {
            if (empty($course['hotmart_product_ucode'])) {
                throw new RuntimeException('Curso sem identificador Hotmart.');
            }
            $query = $db->prepare('SELECT id, deleted_at FROM courses WHERE hotmart_product_ucode = ?');
            $query->execute([$course['hotmart_product_ucode']]);
            $existingCourse = $query->fetch();
            if ($existingCourse && $existingCourse['deleted_at'] !== null) {
                throw new RuntimeException('Curso removido em produção: ' . $course['name']);
            }
            if ($existingCourse) {
                $courseId = $existingCourse['id'];
            } else {
                $db->prepare('INSERT INTO courses (name, description, hotmart_product_ucode, is_active) VALUES (?, ?, ?, ?)')->execute([$course['name'], $course['description'], $course['hotmart_product_ucode'], $course['is_active']]);
                $courseId = $db->lastInsertId();
            }
            $db->prepare('INSERT INTO course_tools (course_id, tool_id, is_active) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE is_active = VALUES(is_active)')->execute([$courseId, $toolId, $course['link_active']]);
        }
    }
    $db->commit();
    echo count($entries) . " ferramentas importadas. Backup: " . $backupPath . PHP_EOL;
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
