<?php

declare(strict_types=1);

$userName = htmlspecialchars(
    $user->getName(),
    ENT_QUOTES,
    'UTF-8'
);

$userInitial = mb_strtoupper(
    mb_substr($user->getName(), 0, 1)
);

$whatsappMessage = rawurlencode(
    'Olá, sou ' . $user->getName() .
    ', estou com uma dúvida sobre o Portal do Aluno, pode me ajudar?'
);

$whatsappLink = 'https://wa.me/5519996371148?text=' . $whatsappMessage;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Ajuda e Suporte | Portal ELO</title>

    <meta
        name="description"
        content="Central de ajuda e suporte do Portal ELO."
    >

    <link
        rel="stylesheet"
        href="/assets/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/support.css"
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
                            Ajuda e suporte
                        </span>

                    </div>

                </div>

                <div class="header-right">

                    <div class="header-user">

                        <div
                            class="header-avatar"
                            id="headerAvatar"
                        >
                            <?= htmlspecialchars(
                                $userInitial,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>

                        <div class="header-user-info">

                            <strong id="headerUserName">
                                <?= $userName ?>
                            </strong>

                            <span>Aluno ELO</span>

                        </div>

                    </div>

                </div>

            </header>

            <div class="portal-content support-content">

                <section class="support-intro">

                    <span class="section-kicker">
                        CENTRAL DE ATENDIMENTO
                    </span>

                    <h1>
                        Como podemos ajudar?
                    </h1>

                    <p>
                        Encontre orientações para utilizar o Portal ELO
                        ou entre em contato caso precise de assistência.
                    </p>

                </section>

                <section
                    class="support-grid"
                    aria-label="Orientações de suporte"
                >

                    <article class="support-card">

                        <div class="support-card-icon">
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    d="M12 17h.01M12 13a2 2 0 0 0 2-2
                                    c0-1.1-.9-2-2-2s-2 .9-2 2m2 2v-2
                                    M5 4h14a2 2 0 0 1 2 2v12
                                    a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6
                                    a2 2 0 0 1 2-2Z"
                                />
                            </svg>
                        </div>

                        <h2>
                            Como acessar meus cursos?
                        </h2>

                        <p>
                            Acesse a opção “Meus cursos” no menu lateral.
                            Em seguida, selecione o curso disponível para
                            visualizar seu conteúdo.
                        </p>

                    </article>

                    <article class="support-card">

                        <div class="support-card-icon">
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    d="M4 5a2 2 0 0 1 2-2h12
                                    a2 2 0 0 1 2 2v14
                                    a2 2 0 0 1-2 2H6
                                    a2 2 0 0 1-2-2V5Z
                                    M8 7h8M8 11h8M8 15h5"
                                />
                            </svg>
                        </div>

                        <h2>
                            Como utilizar uma ferramenta?
                        </h2>

                        <p>
                            Entre em “Minhas ferramentas” e selecione
                            uma ferramenta liberada para sua conta.
                        </p>

                    </article>

                    <article class="support-card">

                        <div class="support-card-icon">
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    d="M4 4h16v12H4V4Z
                                    M8 20h8M12 16v4"
                                />
                            </svg>
                        </div>

                        <h2>
                            Problemas de acesso
                        </h2>

                        <p>
                            Caso não consiga acessar sua conta, utilize
                            a opção “Esqueci minha senha” na tela de login
                            para iniciar a recuperação.
                        </p>

                    </article>

                    <article class="support-card">

                        <div class="support-card-icon">
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    d="M4 5a2 2 0 0 1 2-2h12
                                    a2 2 0 0 1 2 2v10
                                    a2 2 0 0 1-2 2h-5l-3 3v-3H6
                                    a2 2 0 0 1-2-2V5Z"
                                />
                            </svg>
                        </div>

                        <h2>
                            Precisa falar com a equipe?
                        </h2>

                        <p>
                            Envie uma mensagem descrevendo sua dúvida,
                            informando seu nome e, se possível, o curso
                            ou ferramenta relacionada.
                        </p>

                        <a
                            class="support-contact-button"
                            href="<?= htmlspecialchars($whatsappLink, ENT_QUOTES, 'UTF-8') ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Falar pelo WhatsApp
                        </a>

                    </article>

                </section>

                <section class="support-contact-panel">

                    <div>

                        <span class="section-kicker">
                            ATENDIMENTO
                        </span>

                        <h2>
                            Não encontrou o que precisava?
                        </h2>

                        <p>
                            Nossa equipe poderá orientar você sobre
                            o acesso ao portal e a utilização dos recursos
                            disponíveis.
                        </p>

                    </div>

                    <a
                        class="support-contact-button support-contact-button-light"
                        href="<?= htmlspecialchars($whatsappLink, ENT_QUOTES, 'UTF-8') ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Falar pelo WhatsApp
                    </a>

                </section>

            </div>

            <footer class="portal-footer">

                <span>
                    © ELO — Estrategista Legal de Obras
                </span>

                <span>
                    Portal do Aluno
                </span>

            </footer>

        </main>

    </div>

    <script src="/assets/js/sidebar.js"></script>

</body>
</html>