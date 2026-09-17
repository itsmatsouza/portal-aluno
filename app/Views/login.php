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

    <title>Portal do Aluno | ELO</title>

    <meta
        name="description"
        content="Acesse o Portal do Aluno ELO."
    >

    <link
        rel="stylesheet"
        href="/assets/css/login.css"
    >
</head>
<body>

    <main class="login-page">

        <section class="login-brand-panel">
            <div class="brand-background-shape"></div>
            <div class="brand-background-shape brand-background-shape-two"></div>

            <div class="brand-content">
                <img
                    src="/assets/images/logo-elo-horizontal.jpg"
                    alt="ELO — Estrategista Legal de Obras"
                    class="brand-logo"
                >

                <div class="brand-divider"></div>

                <p class="brand-eyebrow">
                    PORTAL DO ALUNO
                </p>

                <h1>
                    Conhecimento,
                    estratégia e evolução.
                </h1>

                <p class="brand-description">
                    Um espaço exclusivo para acessar seus cursos,
                    ferramentas e conteúdos da ELO.
                </p>
            </div>

            <div class="brand-footer">
                <span>Estratégia aplicada à construção.</span>
            </div>
        </section>

        <section class="login-form-panel">

            <div class="login-container">

                <div class="mobile-brand">
                    <img
                        src="/assets/images/logo-elo-horizontal.jpg"
                        alt="ELO — Estrategista Legal de Obras"
                        class="mobile-brand-logo"
                    >
                </div>

                <div class="login-heading">
                    <span class="login-kicker">
                        BEM-VINDO AO PORTAL
                    </span>

                    <h2>
                        Acesse sua conta
                    </h2>

                    <p>
                        Entre para continuar sua jornada de aprendizado.
                    </p>
                </div>

                <form
                    id="loginForm"
                    class="login-form"
                    novalidate
                >

                    <div class="form-field">
                        <label for="email">
                            E-mail
                        </label>

                        <div class="input-wrapper">
                            <svg
                                class="input-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M4 6.5h16v11H4z"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="m4.5 7 7.5 6 7.5-6"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="seu@email.com"
                                autocomplete="email"
                                required
                            >
                        </div>

                        <small
                            class="field-error"
                            data-error-for="email"
                        ></small>
                    </div>

                    <div class="form-field">
                        <div class="field-label-row">
                            <label for="password">
                                Senha
                            </label>

                            <a
                                href="/password/forgot"
                                class="forgot-link"
                                id="forgotPasswordLink"
                            >
                                Esqueci minha senha
                            </a>
                        </div>

                        <div class="input-wrapper">
                            <svg
                                class="input-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <rect
                                    x="4"
                                    y="10"
                                    width="16"
                                    height="10"
                                    rx="2"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                                <path
                                    d="M8 10V7.5a4 4 0 0 1 8 0V10"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                            </svg>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Digite sua senha"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                id="togglePassword"
                                aria-label="Mostrar senha"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linejoin="round"
                                    />
                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="2.5"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                    />
                                </svg>
                            </button>
                        </div>

                        <small
                            class="field-error"
                            data-error-for="password"
                        ></small>
                    </div>

                    <div
                        class="login-alert"
                        id="loginAlert"
                        role="alert"
                        aria-live="polite"
                    ></div>

                    <button
                        type="submit"
                        class="login-button"
                        id="loginButton"
                    >
                        <span class="button-label">
                            Entrar no portal
                        </span>

                        <span
                            class="button-loading"
                            aria-hidden="true"
                        >
                            <span class="loading-spinner"></span>
                            Verificando acesso...
                        </span>

                        <svg
                            class="button-arrow"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M5 12h13M13 6l6 6-6 6"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>

                </form>

                <div class="login-footer">
                    <span class="footer-line"></span>

                    <span>
                        Acesso exclusivo para alunos
                    </span>

                    <span class="footer-line"></span>
                </div>

            </div>
        </section>

    </main>

    <div
        class="login-success-overlay"
        id="loginSuccessOverlay"
        aria-hidden="true"
    >
        <div class="success-card">

            <div class="success-icon">
                <svg
                    viewBox="0 0 52 52"
                    fill="none"
                    aria-hidden="true"
                >
                    <circle
                        cx="26"
                        cy="26"
                        r="24"
                        stroke="currentColor"
                        stroke-width="2"
                    />

                    <path
                        d="m15 27 7 7 15-16"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>

            <span class="success-kicker">
                ACESSO CONFIRMADO
            </span>

            <h2>
                Seja bem-vindo.
            </h2>

            <p>
                Estamos preparando seu ambiente.
            </p>
        </div>
    </div>

    <script src="/assets/js/login.js"></script>
</body>
</html>
