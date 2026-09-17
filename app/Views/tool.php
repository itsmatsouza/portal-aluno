<?php

declare(strict_types=1);

$slug = $_GET['slug'] ?? '';

if (!is_string($slug) || $slug === '') {
    $slug = 'diagnostico-precificacao';
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

    <title>Ferramenta | Portal do Aluno</title>

    <meta
        name="description"
        content="Ferramenta exclusiva do Portal ELO."
    >

    <link
        rel="stylesheet"
        href="/assets/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/tool.css"
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
                            Ferramenta exclusiva
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

            <section class="tool-page">

                <a
                    href="/portal"
                    class="tool-back-link"
                >
                    ← Voltar para o portal
                </a>

                <div
                    class="tool-alert"
                    id="toolAlert"
                    role="alert"
                ></div>

                <section class="tool-hero">

                    <div>
                        <span class="section-kicker">
                            RECURSO EXCLUSIVO
                        </span>

                        <h1 id="toolName">
                            Carregando ferramenta...
                        </h1>

                        <p id="toolDescription">
                            Aguarde enquanto carregamos a ferramenta.
                        </p>
                    </div>

                    <div class="tool-hero-actions">
                        <a
                            href="/portal"
                            class="tool-secondary-button"
                        >
                            Meus cursos
                        </a>
                    </div>

                </section>

                <section class="tool-content-card">

                    <div class="tool-content-header">
                        <div>
                            <span class="section-kicker">
                                ÁREA DE TRABALHO
                            </span>

                            <h2>Utilize sua ferramenta</h2>
                        </div>

                        <span class="tool-access-badge">
                            Acesso autorizado
                        </span>
                    </div>

                    <div class="tool-frame-wrapper">

                        <div
                            class="tool-frame-loading"
                            id="toolFrameLoading"
                        >
                            Carregando ferramenta...
                        </div>

                        <iframe
                            id="toolFrame"
                            class="tool-frame"
                            title="Ferramenta exclusiva"
                            src=""
                            loading="eager"
                        ></iframe>

                    </div>

                </section>

            </section>

        </main>

    </div>

    <script>
        window.TOOL_SLUG = <?= json_encode(
            $slug,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?>;
    </script>

    <script src="/assets/js/sidebar.js"></script>
    <script src="/assets/js/tool.js"></script>

</body>
</html>
