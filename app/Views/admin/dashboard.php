<?php

declare(strict_types=1);

$adminName = (string) ($admin['name'] ?? 'Administrador');
$adminEmail = (string) ($admin['email'] ?? '');

$adminInitial = strtoupper(
    substr(trim($adminName), 0, 1)
);

$adminInitial = $adminInitial !== ''
    ? $adminInitial
    : 'A';

$usersTotal = (int) ($stats['users']['total'] ?? 0);
$usersActive = (int) ($stats['users']['active'] ?? 0);
$usersInactive = (int) ($stats['users']['inactive'] ?? 0);

$coursesTotal = (int) ($stats['courses']['total'] ?? 0);
$coursesActive = (int) ($stats['courses']['active'] ?? 0);
$coursesInactive = (int) ($stats['courses']['inactive'] ?? 0);

$accessTotal = (int) ($stats['user_courses']['total'] ?? 0);
$accessActive = (int) ($stats['user_courses']['active'] ?? 0);
$accessCancelled = (int) ($stats['user_courses']['cancelled'] ?? 0);

function adminEscape(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
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

    <title>Painel administrativo | Portal ELO</title>

    <link
        rel="stylesheet"
        href="/assets/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/admin.css"
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
                        <?= adminEscape($adminInitial) ?>
                    </div>

                    <div class="header-user-info">
                        <strong>
                            <?= adminEscape($adminName) ?>
                        </strong>

                        <span>
                            <?= adminEscape($adminEmail) ?>
                        </span>
                    </div>

                </div>

            </div>

        </header>

        <div class="portal-content">

            <section class="admin-welcome-section">

                <div class="welcome-copy">
                    <span class="section-kicker">
                        PAINEL ADMINISTRATIVO
                    </span>

                    <h1>
                        Visão geral<span>.</span>
                    </h1>

                    <p>
                        Acompanhe os principais indicadores
                        e gerencie os recursos do portal.
                    </p>
                </div>

                <div class="welcome-mark" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

            </section>

            <div
                class="portal-alert"
                id="portalAlert"
                role="alert"
            ></div>

            <section class="portal-stats admin-stats">

                <article class="stat-card stat-card-accent">
                    <div class="stat-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-3A4.5 4.5 0 0 0 4 18.5V20"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                            <circle
                                cx="10"
                                cy="7"
                                r="3"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                            <path
                                d="M16 4.5a3 3 0 0 1 0 5.8M17 14.2a4.5 4.5 0 0 1 3 4.3V20"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>

                    <div>
                        <span class="stat-label">
                            Total de usuários
                        </span>

                        <strong>
                            <?= $usersTotal ?>
                        </strong>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M5 12.5 9.2 17 19 7"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>

                    <div>
                        <span class="stat-label">
                            Usuários ativos
                        </span>

                        <strong>
                            <?= $usersActive ?>
                        </strong>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                            <path
                                d="M7.5 8h9M7.5 12h9M7.5 16h5"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>

                    <div>
                        <span class="stat-label">
                            Total de cursos
                        </span>

                        <strong>
                            <?= $coursesTotal ?>
                        </strong>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                            <path
                                d="M8 4v16M8 9h8"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>

                    <div>
                        <span class="stat-label">
                            Cursos ativos
                        </span>

                        <strong>
                            <?= $coursesActive ?>
                        </strong>
                    </div>
                </article>

                <article class="stat-card stat-card-accent">
                    <div class="stat-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                            <path
                                d="M8 8h8M8 12h8M8 16h5"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>

                    <div>
                        <span class="stat-label">
                            Total de acessos
                        </span>

                        <strong>
                            <?= $accessTotal ?>
                        </strong>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M5 12.5 9.2 17 19 7"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>

                    <div>
                        <span class="stat-label">
                            Acessos ativos
                        </span>

                        <strong>
                            <?= $accessActive ?>
                        </strong>
                    </div>
                </article>

            </section>

            <section class="content-section">

                <div class="section-heading">
                    <div>
                        <span class="section-kicker">
                            GERENCIAMENTO
                        </span>

                        <h2>
                            Ações rápidas
                        </h2>
                    </div>
                </div>

                <div class="admin-actions-grid">

                    <a
                        href="/admin/users"
                        class="admin-action-card"
                    >
                        <span class="admin-action-icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <circle
                                    cx="9"
                                    cy="8"
                                    r="3"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                                <path
                                    d="M3.5 20v-1.5A4.5 4.5 0 0 1 8 14h2a4.5 4.5 0 0 1 4.5 4.5V20"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M16 11h5M18.5 8.5v5"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>

                        <span class="admin-action-content">
                            <strong>Gerenciar usuários</strong>
                            <span>
                                Consultar e administrar os usuários.
                            </span>
                        </span>

                        <span class="admin-action-arrow">→</span>
                    </a>

                    <a
                        href="/admin/courses"
                        class="admin-action-card"
                    >
                        <span class="admin-action-icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M4 6.5A2.5 2.5 0 0 1 6.5 4H20v15H6.5A2.5 2.5 0 0 0 4 21.5v-15Z"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M4 21.5A2.5 2.5 0 0 1 6.5 19H20"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>

                        <span class="admin-action-content">
                            <strong>Gerenciar cursos</strong>
                            <span>
                                Criar, editar e controlar cursos.
                            </span>
                        </span>

                        <span class="admin-action-arrow">→</span>
                    </a>

                    <a
                        href="/admin/tools"
                        class="admin-action-card"
                    >
                        <span class="admin-action-icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="m14.5 6.5 3-3 3 3-3 3"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="m17.5 6.5-7 7"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M14 14 6.5 21.5H3v-3.5L10.5 10"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>

                        <span class="admin-action-content">
                            <strong>Gerenciar ferramentas</strong>
                            <span>
                                Administrar os recursos disponíveis.
                            </span>
                        </span>

                        <span class="admin-action-arrow">→</span>
                    </a>

                    <a
                        href="/admin/access"
                        class="admin-action-card"
                    >
                        <span class="admin-action-icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M7 11V8a5 5 0 0 1 10 0v3"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                                <rect
                                    x="4"
                                    y="11"
                                    width="16"
                                    height="9"
                                    rx="2"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                                <path
                                    d="M12 14v3"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>

                        <span class="admin-action-content">
                            <strong>Gerenciar acessos</strong>
                            <span>
                                Consultar matrículas e permissões.
                            </span>
                        </span>

                        <span class="admin-action-arrow">→</span>
                    </a>

                </div>

            </section>

            <section class="content-section">

                <div class="section-heading">
                    <div>
                        <span class="section-kicker">
                            SITUAÇÃO ATUAL
                        </span>

                        <h2>
                            Distribuição dos acessos
                        </h2>
                    </div>
                </div>

                <div class="admin-access-summary">

                    <div class="admin-summary-item">
                        <span>Ativos</span>
                        <strong><?= $accessActive ?></strong>
                    </div>

                    <div class="admin-summary-item">
                        <span>Cancelados</span>
                        <strong><?= $accessCancelled ?></strong>
                    </div>

                    <div class="admin-summary-item">
                        <span>Usuários inativos</span>
                        <strong><?= $usersInactive ?></strong>
                    </div>

                    <div class="admin-summary-item">
                        <span>Cursos inativos</span>
                        <strong><?= $coursesInactive ?></strong>
                    </div>

                </div>

            </section>

        </div>

        <footer class="portal-footer">
            <span>Portal ELO</span>
            <span>Painel administrativo</span>
        </footer>

    </main>

</div>

<script src="/assets/js/sidebar.js"></script>
<script src="/assets/js/admin.js"></script>

</body>
</html>