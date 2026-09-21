<?php

declare(strict_types=1);

$escape = static fn (?string $value): string => htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$formatDate = static function (?string $value, string $empty = 'Não informada'): string {
    if ($value === null || $value === '') {
        return $empty;
    }
    $timestamp = strtotime($value);

    return $timestamp === false ? 'Data inválida' : date('d/m/Y H:i', $timestamp);
};
$heading = isset($enrollment) ? 'Detalhes da matrícula' : 'Acessos';
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
    <link rel="stylesheet" href="/assets/css/admin-access.css">
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
            <section class="admin-info-banner">
                <strong>Consulta somente leitura</strong>
                <span>Situação das matrículas e dados de compra registrados no portal.</span>
            </section>
            <?php if (isset($enrollment)): ?>
                <?php require __DIR__ . '/partials/access-details.php'; ?>
            <?php else: ?>
                <form method="GET" action="/admin/access" class="admin-users-filters admin-access-filters">
                    <div class="admin-field"><label for="search">Pesquisar</label><input type="search" id="search" name="search" value="<?= $escape($search) ?>" placeholder="Nome, e-mail ou transação"></div>
                    <div class="admin-field">
                        <label for="course">Curso</label>
                        <select id="course" name="course">
                            <option value="">Todos</option>
                            <?php if ($courseId !== null && !in_array($courseId, array_map('intval', array_column($courses, 'id')), true)): ?>
                                <option value="<?= $courseId ?>" selected>Curso #<?= $courseId ?> (indisponível)</option>
                            <?php endif; ?>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= (int) $course['id'] ?>" <?= $courseId === (int) $course['id'] ? 'selected' : '' ?>><?= $escape($course['name']) ?><?= $course['deleted_at'] !== null ? ' (excluído)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field"><label for="status">Situação da matrícula</label><select id="status" name="status">
                        <option value="">Todas</option>
                        <?php foreach ($statusLabels as $value => $label): ?><option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="admin-filter-actions"><button class="admin-button admin-button-primary" type="submit">Filtrar</button><a class="admin-button admin-button-secondary" href="/admin/access">Limpar</a></div>
                </form>
                <section class="content-section">
                    <div class="section-heading"><div><h2>Matrículas</h2><p><?= number_format($result['total'], 0, ',', '.') ?> registro(s) encontrado(s).</p></div></div>
                    <div class="admin-table-wrapper" tabindex="0" role="region" aria-label="Matrículas registradas">
                        <table class="admin-table">
                            <thead><tr><th scope="col">Aluno</th><th scope="col">Curso</th><th scope="col">Situação</th><th scope="col">Compra</th><th scope="col">Vencimento</th><th scope="col">Transação Hotmart</th><th scope="col">Ação</th></tr></thead>
                            <tbody>
                                <?php if ($result['items'] === []): ?><tr><td colspan="7" class="admin-table-empty">Nenhuma matrícula encontrada.</td></tr><?php endif; ?>
                                <?php foreach ($result['items'] as $item): ?>
                                    <tr>
                                        <td><div class="admin-user-data"><strong><?= $escape($item['user_name'] ?? 'Usuário indisponível') ?></strong><span><?= $escape($item['user_email']) ?></span>
                                            <?php if ($item['user_deleted_at'] !== null): ?><span class="admin-muted">Conta excluída</span><?php elseif ($item['user_name'] !== null && !$item['user_active']): ?><span class="admin-muted">Conta inativa</span><?php endif; ?>
                                        </div></td>
                                        <td><div class="admin-user-data"><strong><?= $escape($item['course_name'] ?? 'Curso indisponível') ?></strong>
                                            <?php if ($item['course_deleted_at'] !== null): ?><span class="admin-muted">Curso excluído</span><?php elseif ($item['course_name'] !== null && !$item['course_active']): ?><span class="admin-muted">Curso inativo</span><?php endif; ?>
                                        </div></td>
                                        <td><span class="admin-status <?= $item['status'] === 'ACTIVE' ? 'status-active' : 'status-inactive' ?>"><?= $escape($statusLabels[$item['status']] ?? $item['status']) ?></span></td>
                                        <td><?= $formatDate($item['purchased_at']) ?></td>
                                        <td><?= $formatDate($item['access_expires_at'], 'Sem vencimento') ?></td>
                                        <td class="admin-access-transaction"><?= $escape($item['hotmart_transaction_id'] ?? 'Não informada') ?></td>
                                        <td><a class="admin-table-link" href="/admin/access/<?= (int) $item['id'] ?>">Ver detalhes</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($result['total_pages'] > 1): ?>
                        <?php $pageUrl = static fn (int $page): string => $escape('/admin/access?' . http_build_query(['search' => $search, 'status' => $status, 'course' => $courseId, 'page' => $page])); ?>
                        <nav class="admin-pagination" aria-label="Paginação de matrículas">
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
</body>
</html>
