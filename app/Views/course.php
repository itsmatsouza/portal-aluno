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

    <title>Curso | Portal do Aluno</title>

    <meta
        name="description"
        content="Detalhes do curso no Portal ELO."
    >

    <link
        rel="stylesheet"
        href="/assets/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/course.css"
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
                        <span>☰</span>
                    </button>

                    <div class="header-context">
                        <span class="header-kicker">
                            PORTAL DO ALUNO
                        </span>

                        <span class="header-title">
                            Detalhes do curso
                        </span>
                    </div>

                </div>

                <div class="header-right">
                    <div class="header-user">
                        <div
                            class="header-avatar"
                            id="headerAvatar"
                        >
                            A
                        </div>

                        <span
                            id="headerUserName"
                            class="header-user-name"
                        >
                            Aluno
                        </span>
                    </div>
                </div>

            </header>

            <section class="course-page">

                <a
                    href="/portal"
                    class="course-back-link"
                >
                    ← Voltar para meus cursos
                </a>

                <div
                    class="course-alert"
                    id="courseAlert"
                    role="alert"
                ></div>

                <section
                    class="course-hero"
                    id="courseHero"
                >
                    <span class="section-kicker">
                        CURSO EXCLUSIVO
                    </span>

                    <h1 id="courseName">
                        Carregando curso...
                    </h1>

                    <p id="courseDescription">
                        Aguarde enquanto carregamos os dados do curso.
                    </p>

                    <div class="course-hero-meta">
                        <span class="course-status">
                            Acesso ativo
                        </span>

                        <span id="courseToolsSummary">
                            Carregando ferramentas...
                        </span>
                    </div>
                </section>

                <section class="course-tools-section">

                    <div class="course-section-heading">
                        <div>
                            <span class="section-kicker">
                                RECURSOS DISPONÍVEIS
                            </span>

                            <h2>Ferramentas do curso</h2>
                        </div>
                    </div>

                    <div
                        class="course-tools-grid"
                        id="courseToolsGrid"
                    >
                        <div class="course-loading">
                            Carregando ferramentas...
                        </div>
                    </div>

                </section>

            </section>

        </main>

    </div>

    <script src="/assets/js/sidebar.js"></script>
    <script src="/assets/js/course.js"></script>

</body>
</html>
