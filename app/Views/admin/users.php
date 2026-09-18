<?php

declare(strict_types=1);

/**
 * Variáveis recebidas:
 *
 * @var array $admin
 * @var array $users
 * @var int $total
 * @var int $currentPage
 * @var int $perPage
 * @var int $totalPages
 */

$adminName = (string) ($admin['name'] ?? 'Administrador');
$adminEmail = (string) ($admin['email'] ?? '');

$adminInitial = adminUsersInitial($adminName);

$adminInitial = $adminInitial !== ''
    ? $adminInitial
    : 'A';

$search = htmlspecialchars(
    $search ?? '',
    ENT_QUOTES,
    'UTF-8'
);

$status = htmlspecialchars(
    $status ?? '',
    ENT_QUOTES,
    'UTF-8'
);

$role = htmlspecialchars(
    $role ?? '',
    ENT_QUOTES,
    'UTF-8'
);

function adminUsersQuery(array $changes = []): string
{
    $params = [
        'search' => $_GET['search'] ?? '',
        'status' => $_GET['status'] ?? '',
        'role' => $_GET['role'] ?? '',
        'page' => $_GET['page'] ?? 1,
    ];

    foreach ($changes as $key => $value) {
        $params[$key] = $value;
    }
    $params = array_filter($params, 'is_scalar');

    $params = array_filter(
        $params,
        static fn ($value) => $value !== null && $value !== ''
    );

    return htmlspecialchars('/admin/users?' . http_build_query($params), ENT_QUOTES, 'UTF-8');
}

function adminUsersFormatDate(?string $date): string
{
    if ($date === null || $date === '') {
        return 'Nunca';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '—';
    }

    return date('d/m/Y H:i', $timestamp);
}

function adminUsersStatusLabel(bool $active): string
{
    return $active ? 'Ativo' : 'Inativo';
}

function adminUsersStatusClass(bool $active): string
{
    return $active ? 'status-active' : 'status-inactive';
}

function adminUsersRoleLabel(string $role): string
{
    return $role === 'ADMIN'
        ? 'Administrador'
        : 'Aluno';
}

function adminUsersEscape(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

function adminUsersInitial(string $name): string
{
    $name = trim($name);

    if ($name === '') {
        return 'U';
    }

    preg_match('/^./us', $name, $matches);
    $initial = $matches[0] ?? 'U';

    return function_exists('mb_strtoupper')
        ? mb_strtoupper($initial, 'UTF-8')
        : strtoupper($initial);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Usuários | Portal ELO</title>

    <link
        rel="stylesheet"
        href="/assets/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/admin.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/admin-users.css"
    >
</head>
<body>

<div class="portal-shell">

    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="portal-main">

        <header class="portal-header">

            <div class="header-left">

                <button
                    type="button"
                    class="mobile-menu-button"
                    id="mobileMenuButton"
                    aria-label="Abrir menu"
                    aria-expanded="false"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M4 7h16M4 12h16M4 17h16"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                </button>

                <div class="header-context">

                    <span class="header-kicker">
                        PORTAL ELO
                    </span>

                    <span class="header-title">
                        Administração
                    </span>

                </div>

            </div>

            <div class="header-right">

                <div class="header-user">

                    <div class="header-avatar">
                        <?= adminUsersEscape($adminInitial) ?>
                    </div>

                    <div class="header-user-info">

                        <strong>
                            <?= adminUsersEscape($adminName) ?>
                        </strong>

                        <span>
                            <?= adminUsersEscape($adminEmail) ?>
                        </span>

                    </div>

                </div>

            </div>

        </header>

        <div class="portal-content">

            <section class="admin-welcome-section">

                <div class="welcome-copy">

                    <span class="section-kicker">
                        ADMINISTRAÇÃO
                    </span>

                    <h1>
                        <?= isset($detailUser) ? 'Detalhes do usuário' : 'Usuários' ?>
                    </h1>

                    <p>
                        Consulte os dados cadastrais e os vínculos registrados no portal.
                    </p>

                </div>

            </section>

            <section class="admin-info-banner">

                <strong>
                    Consulta somente leitura
                </strong>

                <span>
                    Dados cadastrais e matrículas disponíveis para consulta.
                </span>

            </section>

            <?php if (isset($detailUser)): ?>
                <?php require __DIR__ . '/partials/user-details.php'; ?>
            <?php else: ?>
            <section class="content-section">

                <div class="section-heading">

                    <div>

                        <span class="section-kicker">
                            FILTROS
                        </span>

                        <h2>
                            Pesquisar usuários
                        </h2>

                    </div>

                </div>

                <div class="admin-panel">

                    <form
                        method="GET"
                        action="/admin/users"
                        class="admin-users-filters"
                    >

                        <div class="admin-field">

                            <label for="search">
                                Pesquisar
                            </label>

                            <input
                                type="search"
                                id="search"
                                name="search"
                                value="<?= $search ?>"
                                placeholder="Nome ou e-mail"
                            >

                        </div>

                        <div class="admin-field">

                            <label for="status">
                                Status
                            </label>

                            <select
                                id="status"
                                name="status"
                            >
                                <option value="">
                                    Todos
                                </option>

                                <option
                                    value="active"
                                    <?= $status === 'active' ? 'selected' : '' ?>
                                >
                                    Ativos
                                </option>

                                <option
                                    value="inactive"
                                    <?= $status === 'inactive' ? 'selected' : '' ?>
                                >
                                    Inativos
                                </option>
                            </select>

                        </div>

                        <div class="admin-field">

                            <label for="role">
                                Perfil
                            </label>

                            <select
                                id="role"
                                name="role"
                            >
                                <option value="">
                                    Todos
                                </option>

                                <option
                                    value="ALUNO"
                                    <?= $role === 'ALUNO' ? 'selected' : '' ?>
                                >
                                    Alunos
                                </option>

                                <option
                                    value="ADMIN"
                                    <?= $role === 'ADMIN' ? 'selected' : '' ?>
                                >
                                    Administradores
                                </option>
                            </select>

                        </div>

                        <div class="admin-filter-actions">

                            <button
                                type="submit"
                                class="admin-button admin-button-primary"
                            >
                                Filtrar
                            </button>

                            <a
                                href="/admin/users"
                                class="admin-button admin-button-secondary"
                            >
                                Limpar
                            </a>

                        </div>

                    </form>

                </div>

            </section>

            <section class="content-section">

                <div class="section-heading">

                    <div>

                        <span class="section-kicker">
                            BASE DE USUÁRIOS
                        </span>

                        <h2>
                            Usuários cadastrados
                        </h2>

                        <p>
                            <?= number_format($total, 0, ',', '.') ?>
                            registro(s) encontrado(s).
                        </p>

                    </div>

                </div>

                <div class="admin-panel">

                    <div class="admin-table-wrapper" tabindex="0" role="region" aria-label="Usuários cadastrados">

                        <table class="admin-table">

                            <thead>

                                <tr>
                                    <th>Usuário</th>
                                    <th>Perfil</th>
                                    <th>Status</th>
                                    <th>Hotmart</th>
                                    <th>Último acesso</th>
                                    <th>Cadastro</th>
                                    <th>Ação</th>
                                </tr>

                            </thead>

                            <tbody>

                            <?php if ($users === []): ?>

                                <tr>

                                    <td
                                        colspan="7"
                                        class="admin-table-empty"
                                    >
                                        Nenhum usuário encontrado.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($users as $user): ?>

                                    <tr>

                                        <td>

                                            <div class="admin-user-cell">

                                                <div class="admin-user-avatar">
                                                    <?= adminUsersEscape(
                                                        adminUsersInitial(
                                                            $user->getName()
                                                        )
                                                    ) ?>
                                                </div>

                                                <div class="admin-user-data">

                                                    <strong>
                                                        <?= adminUsersEscape(
                                                            $user->getName()
                                                        ) ?>
                                                    </strong>

                                                    <span>
                                                        <?= adminUsersEscape(
                                                            $user->getEmail()
                                                        ) ?>
                                                    </span>

                                                </div>

                                            </div>

                                        </td>

                                        <td>

                                            <span class="admin-role">
                                                <?= adminUsersRoleLabel(
                                                    $user->getRole()
                                                ) ?>
                                            </span>

                                        </td>

                                        <td>

                                            <span
                                                class="admin-status <?= adminUsersStatusClass(
                                                    $user->isActive()
                                                ) ?>"
                                            >
                                                <?= adminUsersStatusLabel(
                                                    $user->isActive()
                                                ) ?>
                                            </span>

                                        </td>

                                        <td>

                                            <?php if ($user->getHotmartBuyerId()): ?>

                                                <span class="admin-hotmart-id">
                                                    <?= adminUsersEscape(
                                                        $user->getHotmartBuyerId()
                                                    ) ?>
                                                </span>

                                            <?php else: ?>

                                                <span class="admin-muted">
                                                    Não vinculado
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <?= adminUsersFormatDate(
                                                $user->getLastLoginAt()
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= adminUsersFormatDate(
                                                $user->getCreatedAt()
                                            ) ?>
                                        </td>

                                        <td>

                                            <a
                                                href="/admin/users/<?= $user->getId() ?>"
                                                class="admin-table-link"
                                            >
                                                Ver detalhes
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                    <?php if ($totalPages > 1): ?>

                        <nav class="admin-pagination" aria-label="Paginação de usuários">

                            <div class="admin-pagination-info">

                                Página
                                <strong>
                                    <?= $currentPage ?>
                                </strong>
                                de
                                <strong>
                                    <?= $totalPages ?>
                                </strong>

                            </div>

                            <div class="admin-pagination-links">

                                <?php if ($currentPage > 1): ?>

                                    <a
                                        href="<?= adminUsersQuery([
                                            'page' => $currentPage - 1
                                        ]) ?>"
                                        class="admin-pagination-link"
                                    >
                                        Anterior
                                    </a>

                                <?php endif; ?>

                                <?php
                                $startPage = max(
                                    1,
                                    $currentPage - 2
                                );

                                $endPage = min(
                                    $totalPages,
                                    $currentPage + 2
                                );
                                ?>

                                <?php for (
                                    $page = $startPage;
                                    $page <= $endPage;
                                    $page++
                                ): ?>

                                    <a
                                        href="<?= adminUsersQuery([
                                            'page' => $page
                                        ]) ?>"
                                        class="admin-pagination-link <?= $page === $currentPage ? 'is-active' : '' ?>"
                                        <?= $page === $currentPage ? 'aria-current="page"' : '' ?>
                                    >
                                        <?= $page ?>
                                    </a>

                                <?php endfor; ?>

                                <?php if ($currentPage < $totalPages): ?>

                                    <a
                                        href="<?= adminUsersQuery([
                                            'page' => $currentPage + 1
                                        ]) ?>"
                                        class="admin-pagination-link"
                                    >
                                        Próxima
                                    </a>

                                <?php endif; ?>

                            </div>

                        </nav>

                    <?php endif; ?>

                </div>

            </section>

            <?php endif; ?>
        </div>

        <footer class="portal-footer">

            <span>
                Portal ELO
            </span>

            <span>
                Painel administrativo
            </span>

        </footer>

    </main>

</div>

<script src="/assets/js/sidebar.js"></script>
<script src="/assets/js/admin.js"></script>

</body>
</html>
