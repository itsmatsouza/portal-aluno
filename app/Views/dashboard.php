<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard | Portal do Aluno</title>

    <meta
        name="description"
        content="Área exclusiva do aluno ELO."
    >

    <link
        rel="stylesheet"
        href="/assets/css/dashboard.css"
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
                            PORTAL DO ALUNO
                        </span>

                        <span class="header-title">
                            Visão geral
                        </span>
                    </div>

                </div>

                <div class="header-right">

                    <div class="header-user">
                        <div class="header-avatar" id="headerAvatar">
                            A
                        </div>

                        <div class="header-user-info">
                            <strong id="headerUserName">
                                Carregando...
                            </strong>

                            <span>Aluno ELO</span>
                        </div>
                    </div>

                </div>

            </header>

            <div class="portal-content">

                <section class="welcome-section">

                    <div class="welcome-copy">
                        <span class="section-kicker">
                            ÁREA EXCLUSIVA
                        </span>

                        <h1>
                            Olá, <span id="welcomeName">aluno</span>.
                        </h1>

                        <p>
                            Continue sua jornada de aprendizado
                            e aproveite suas ferramentas.
                        </p>
                    </div>

                    <div class="welcome-mark" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>

                </section>

                <section
                    class="portal-stats"
                    aria-label="Resumo do acesso"
                >

                    <article class="stat-card">
                        <div class="stat-icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M4 6.5A2.5 2.5 0 0 1 6.5 4H20v14H6.5A2.5 2.5 0 0 0 4 20.5v-14Z"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M4 20.5A2.5 2.5 0 0 1 6.5 18H20"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </div>

                        <div>
                            <span class="stat-label">Cursos disponíveis</span>
                            <strong id="coursesCount">—</strong>
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
                        </div>

                        <div>
                            <span class="stat-label">Ferramentas liberadas</span>
                            <strong id="toolsCount">—</strong>
                        </div>
                    </article>

                    <article class="stat-card stat-card-accent">
                        <div class="stat-icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="8"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                                <path
                                    d="m8.5 12 2.3 2.3 4.7-5"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </div>

                        <div>
                            <span class="stat-label">Status do acesso</span>
                            <strong>Ativo</strong>
                        </div>
                    </article>

                </section>

                <section
                    class="content-section"
                    id="coursesSection"
                >

                    <div class="section-heading">
                        <div>
                            <span class="section-kicker">
                                SUA BIBLIOTECA
                            </span>

                            <h2>Meus cursos</h2>
                        </div>

                        <span
                            class="section-counter"
                            id="coursesCounter"
                        >
                            0 cursos
                        </span>
                    </div>

                    <div
                        class="courses-grid"
                        id="coursesGrid"
                    >
                        <div class="content-loading">
                            <span class="content-spinner"></span>
                            Carregando seus cursos...
                        </div>
                    </div>

                </section>

                <section
                    class="content-section tools-section"
                    id="toolsSection"
                >

                    <div class="section-heading">
                        <div>
                            <span class="section-kicker">
                                RECURSOS EXCLUSIVOS
                            </span>

                            <h2>Minhas ferramentas</h2>
                        </div>

                        <span
                            class="section-counter"
                            id="toolsCounter"
                        >
                            0 ferramentas
                        </span>
                    </div>

                    <div
                        class="tools-grid"
                        id="toolsGrid"
                    >
                        <div class="content-loading">
                            <span class="content-spinner"></span>
                            Carregando suas ferramentas...
                        </div>
                    </div>

                </section>

                <section
                    class="empty-state"
                    id="emptyState"
                    hidden
                >
                    <div class="empty-state-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 6.5A2.5 2.5 0 0 1 6.5 4H20v14H6.5A2.5 2.5 0 0 0 4 20.5v-14Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M4 20.5A2.5 2.5 0 0 1 6.5 18H20"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>

                    <h2>
                        Seus cursos aparecerão aqui
                    </h2>

                    <p>
                        Assim que um curso estiver liberado
                        para sua conta, ele será exibido nesta área.
                    </p>
                </section>

                <div
                    class="portal-alert"
                    id="portalAlert"
                    role="alert"
                    aria-live="polite"
                ></div>

            </div>

            <footer class="portal-footer">
                <span>© ELO — Estrategista Legal de Obras</span>
                <span>Portal do Aluno</span>
            </footer>

        </main>

    </div>

    <script src="/assets/js/sidebar.js"></script>   
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>
