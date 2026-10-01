<?php

declare(strict_types=1);

$escape = static fn (?string $value): string => htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$form = $form ?? false;
$heading = $form ? ($class === null ? 'Nova turma' : 'Editar turma') : 'Turmas';
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
                <?php if (!$form): ?><a class="admin-button admin-button-primary" href="/admin/classes/new">Nova turma</a><?php endif; ?>
            </section>
            <?php if (is_string($message)): ?><div class="admin-course-feedback" role="status"><?= $escape($message) ?></div><?php endif; ?>
            <?php if ($form): ?>
                <?php require __DIR__ . '/partials/class-form.php'; ?>
            <?php else: ?>
                <form method="GET" action="/admin/classes" class="admin-course-filters">
                    <div class="admin-field"><label for="search">Pesquisar turma, curso ou ID Hotmart</label><input id="search" type="search" name="search" value="<?= $escape($search) ?>"></div>
                    <button class="admin-button admin-button-primary" type="submit">Filtrar</button>
                </form>
                <section class="content-section">
                    <div class="section-heading"><h2>Turmas cadastradas (<?= $result['total'] ?>)</h2></div>
                    <div class="admin-table-wrapper" tabindex="0" role="region" aria-label="Turmas cadastradas">
                        <table class="admin-table"><thead><tr><th>ID</th><th>Turma</th><th>Curso</th><th>ID Hotmart</th><th>Duração</th><th>Ferramentas</th><th>Ações</th></tr></thead><tbody>
                        <?php if ($result['items'] === []): ?><tr><td colspan="7" class="admin-table-empty">Nenhuma turma encontrada.</td></tr><?php endif; ?>
                        <?php foreach ($result['items'] as $item): ?><tr>
                            <td><?= (int) $item['id'] ?></td><td><?= $escape($item['name']) ?></td><td><?= $escape($item['course_name']) ?></td><td><?= $escape($item['hotmart_class_id']) ?></td>
                            <td><?= $item['is_lifetime'] ? 'Vitalício' : ($item['access_days'] === null ? 'Definir duração' : (int) $item['access_days'] . ' dias') ?></td><td><?= (int) $item['tool_count'] ?></td>
                            <td><a class="admin-table-link" href="/admin/classes/<?= (int) $item['id'] ?>/edit">Editar</a></td>
                        </tr><?php endforeach; ?></tbody></table>
                    </div>
                    <?php if ($result['total_pages'] > 1): ?>
                        <nav class="admin-pagination" aria-label="Paginação de turmas">
                            <span>Página <?= $result['page'] ?> de <?= $result['total_pages'] ?></span>
                            <?php foreach ([-1 => 'Anterior', 1 => 'Próxima'] as $step => $label): ?>
                                <?php $page = $result['page'] + $step; if ($page >= 1 && $page <= $result['total_pages']): ?>
                                    <a class="admin-pagination-link" href="<?= $escape('/admin/classes?' . http_build_query(['search' => $search, 'page' => $page])) ?>"><?= $label ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </nav>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>
        <footer class="portal-footer"><span>Portal ELO</span><span>Painel administrativo</span></footer>
    </main>
</div>
<script src="/assets/js/admin.js"></script>
<script src="/assets/js/admin-classes.js"></script>
</body>
</html>
