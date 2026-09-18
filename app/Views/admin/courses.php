<?php

declare(strict_types=1);

$escape = static fn (?string $value): string => htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$form = $form ?? false;
$heading = $form ? ($course === null ? 'Novo curso' : 'Editar curso') : 'Cursos';
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
                <?php if (!$form): ?><a class="admin-button admin-button-primary" href="/admin/courses/new">Novo curso</a><?php endif; ?>
            </section>
            <?php if (is_string($message)): ?><div class="admin-course-feedback" role="status"><?= $escape($message) ?></div><?php endif; ?>
            <?php if ($form): ?>
                <?php require __DIR__ . '/partials/course-form.php'; ?>
            <?php else: ?>
                <form method="GET" action="/admin/courses" class="admin-course-filters">
                    <div class="admin-field"><label for="search">Pesquisar</label><input type="search" id="search" name="search" value="<?= $escape($search) ?>" placeholder="Nome ou identificador Hotmart"></div>
                    <div class="admin-field"><label for="status">Status</label><select id="status" name="status">
                        <option value="">Todos</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativos</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inativos</option>
                    </select></div>
                    <div class="admin-filter-actions"><button class="admin-button admin-button-primary" type="submit">Filtrar</button><a class="admin-button admin-button-secondary" href="/admin/courses">Limpar</a></div>
                </form>
                <section class="content-section">
                    <div class="section-heading"><div><h2>Cursos cadastrados</h2><p><?= $result['total'] ?> registro(s) encontrado(s).</p></div></div>
                    <div class="admin-table-wrapper" tabindex="0" role="region" aria-label="Cursos cadastrados">
                        <table class="admin-table admin-course-table">
                            <thead><tr><th scope="col">Curso</th><th scope="col">Hotmart</th><th scope="col">Status</th><th scope="col">Cadastro</th><th scope="col">Ações</th></tr></thead>
                            <tbody>
                                <?php if ($result['items'] === []): ?><tr><td colspan="5" class="admin-table-empty">Nenhum curso encontrado.</td></tr><?php endif; ?>
                                <?php foreach ($result['items'] as $item): ?>
                                    <tr>
                                        <td><strong><?= $escape($item->getName()) ?></strong></td>
                                        <td><?= $escape($item->getHotmartProductUcode() ?? 'Não vinculado') ?></td>
                                        <td><span class="admin-status <?= $item->isActive() ? 'status-active' : 'status-inactive' ?>"><?= $item->isActive() ? 'Ativo' : 'Inativo' ?></span></td>
                                        <td><?= date('d/m/Y', strtotime($item->getCreatedAt())) ?></td>
                                        <td><div class="admin-course-actions">
                                            <a class="admin-table-link" href="/admin/courses/<?= $item->getId() ?>/edit">Editar</a>
                                            <form method="POST" action="/admin/courses/<?= $item->getId() ?>/status" <?= $item->isActive() ? 'data-confirm-inactive' : '' ?>>
                                                <input type="hidden" name="csrf_token" value="<?= $escape($token) ?>">
                                                <input type="hidden" name="active" value="<?= $item->isActive() ? '0' : '1' ?>">
                                                <button type="submit" class="admin-course-status-button"><?= $item->isActive() ? 'Inativar' : 'Ativar' ?></button>
                                            </form>
                                        </div></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($result['total_pages'] > 1): ?>
                        <?php $pageUrl = static fn (int $page): string => $escape('/admin/courses?' . http_build_query(['search' => $search, 'status' => $status, 'page' => $page])); ?>
                        <nav class="admin-pagination" aria-label="Paginação de cursos">
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
<script src="/assets/js/admin-courses.js"></script>
</body>
</html>
