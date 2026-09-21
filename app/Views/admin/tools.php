<?php

declare(strict_types=1);

$escape = static fn (?string $value): string => htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$editing = isset($tool);
$heading = $editing ? 'Editar ferramenta' : 'Ferramentas';
preg_match('/^./us', trim($admin['name']), $initial);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $heading ?> | Portal ELO</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/admin-users.css">
    <link rel="stylesheet" href="/assets/css/admin-courses.css">
    <link rel="stylesheet" href="/assets/css/admin-tools.css">
</head>
<body>
<div class="portal-shell">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="portal-main">
        <header class="portal-header">
            <div class="header-left">
                <button type="button" class="mobile-menu-button" id="mobileMenuButton" aria-label="Abrir menu" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                </button>
                <div class="header-context"><span class="header-kicker">PORTAL ELO</span><span class="header-title">Administração</span></div>
            </div>
            <div class="header-right">
                <div class="header-user">
                    <div class="header-avatar"><?= $escape($initial[0] ?? 'A') ?></div>
                    <div class="header-user-info"><strong><?= $escape($admin['name']) ?></strong><span><?= $escape($admin['email']) ?></span></div>
                </div>
            </div>
        </header>
        <div class="portal-content">
            <section class="admin-welcome-section">
                <div class="welcome-copy"><span class="section-kicker">ADMINISTRAÇÃO</span><h1><?= $heading ?></h1></div>
            </section>
            <?php if (is_string($message)): ?><div class="admin-course-feedback" role="status"><?= $escape($message) ?></div><?php endif; ?>
            <?php if ($editing): ?>
                <?php require __DIR__ . '/partials/tool-form.php'; ?>
            <?php else: ?>
                <form method="GET" action="/admin/tools" class="admin-course-filters">
                    <div class="admin-field"><label for="search">Pesquisar</label><input type="search" id="search" name="search" value="<?= $escape($search) ?>" placeholder="Nome ou identificador"></div>
                    <div class="admin-field"><label for="status">Status</label><select id="status" name="status">
                        <option value="">Todos</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativas</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inativas</option>
                    </select></div>
                    <div class="admin-filter-actions"><button class="admin-button admin-button-primary" type="submit">Filtrar</button><a class="admin-button admin-button-secondary" href="/admin/tools">Limpar</a></div>
                </form>
                <section class="content-section">
                    <div class="section-heading"><div><h2>Ferramentas cadastradas</h2><p><?= $result['total'] ?> registro(s) encontrado(s).</p></div></div>
                    <div class="admin-table-wrapper" tabindex="0" role="region" aria-label="Ferramentas cadastradas">
                        <table class="admin-table">
                            <thead><tr><th scope="col">Ferramenta</th><th scope="col">Status global</th><th scope="col">Arquivo</th><th scope="col">Cursos vinculados</th><th scope="col">Ações</th></tr></thead>
                            <tbody>
                                <?php if ($result['items'] === []): ?><tr><td colspan="5" class="admin-table-empty">Nenhuma ferramenta encontrada.</td></tr><?php endif; ?>
                                <?php foreach ($result['items'] as $item): ?>
                                    <?php $entry = $item['tool']; ?>
                                    <tr>
                                        <td><div class="admin-user-data"><strong><?= $escape($entry->getName()) ?></strong><span><?= $escape($entry->getSlug()) ?></span></div></td>
                                        <td><span class="admin-status <?= $entry->isActive() ? 'status-active' : 'status-inactive' ?>"><?= $entry->isActive() ? 'Ativa' : 'Inativa' ?></span></td>
                                        <td><?= $item['has_file'] ? 'Disponível' : 'Ausente ou inacessível' ?></td>
                                        <td><a class="admin-table-link" href="/admin/tools/<?= $entry->getId() ?>/edit#linkedCourses"><?= $item['course_count'] ?> curso(s)</a></td>
                                        <td><div class="admin-course-actions">
                                            <a class="admin-table-link" href="/admin/tools/<?= $entry->getId() ?>/edit">Editar</a>
                                            <form method="POST" action="/admin/tools/<?= $entry->getId() ?>/status" <?= $entry->isActive() ? 'data-confirm-tool-inactive' : '' ?> data-course-count="<?= $item['course_count'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $escape($token) ?>">
                                                <input type="hidden" name="active" value="<?= $entry->isActive() ? '0' : '1' ?>">
                                                <button type="submit" class="admin-course-status-button" <?= !$entry->isActive() && !$item['has_file'] ? 'disabled title="Arquivo ausente ou inacessível"' : '' ?>><?= $entry->isActive() ? 'Inativar' : 'Ativar' ?></button>
                                            </form>
                                        </div></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($result['total_pages'] > 1): ?>
                        <?php $pageUrl = static fn (int $page): string => $escape('/admin/tools?' . http_build_query(['search' => $search, 'status' => $status, 'page' => $page])); ?>
                        <nav class="admin-pagination" aria-label="Paginação de ferramentas">
                            <span class="admin-pagination-info">Página <?= $result['page'] ?> de <?= $result['total_pages'] ?></span>
                            <div class="admin-pagination-links">
                                <?php if ($result['page'] > 1): ?><a class="admin-pagination-link" href="<?= $pageUrl($result['page'] - 1) ?>">Anterior</a><?php endif; ?>
                                <?php for ($page = max(1, $result['page'] - 2); $page <= min($result['total_pages'], $result['page'] + 2); $page++): ?>
                                    <a class="admin-pagination-link <?= $page === $result['page'] ? 'is-active' : '' ?>" href="<?= $pageUrl($page) ?>" <?= $page === $result['page'] ? 'aria-current="page"' : '' ?>><?= $page ?></a>
                                <?php endfor; ?>
                                <?php if ($result['page'] < $result['total_pages']): ?><a class="admin-pagination-link" href="<?= $pageUrl($result['page'] + 1) ?>">Próxima</a><?php endif; ?>
                            </div>
                        </nav>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>
        <footer class="portal-footer"><span>Portal ELO</span><span>Painel administrativo</span></footer>
    </main>
</div>
<script src="/assets/js/admin.js"></script>
<script src="/assets/js/admin-tools.js"></script>
</body>
</html>
