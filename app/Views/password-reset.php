<?php

declare(strict_types=1);

$token = $token ?? '';
$mode = $mode ?? 'request';

$isResetMode = $mode === 'reset';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= $isResetMode
            ? 'Redefinir senha'
            : 'Recuperar senha'
        ?>
        | Portal do Aluno
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/password-reset.css"
    >
</head>
<body>

    <main class="reset-page">

        <section class="reset-brand-panel">
            <div class="reset-shape reset-shape-one"></div>
            <div class="reset-shape reset-shape-two"></div>

            <div class="reset-brand-content">
                <img
                    src="/assets/images/logo-elo-horizontal.jpg"
                    alt="ELO — Estrategista Legal de Obras"
                    class="reset-logo"
                >

                <div class="reset-divider"></div>

                <span class="reset-eyebrow">
                    PORTAL DO ALUNO
                </span>

                <h1>
                    Seu acesso,
                    sempre disponível.
                </h1>

                <p>
                    Recupere sua senha de forma segura
                    e continue sua jornada de aprendizado.
                </p>
            </div>

            <div class="reset-brand-footer">
                <span>Estratégia aplicada à construção.</span>
            </div>
        </section>

        <section class="reset-form-panel">

            <div class="reset-container">

                <div class="reset-mobile-brand">
                    <img
                        src="/assets/images/logo-elo-horizontal.jpg"
                        alt="ELO — Estrategista Legal de Obras"
                        class="reset-mobile-logo"
                    >
                </div>

                <a
                    href="/login"
                    class="back-link"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M19 12H5M11 18l-6-6 6-6"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>

                    Voltar para o login
                </a>

                <div class="reset-heading">
                    <span class="reset-kicker">
                        <?= $isResetMode
                            ? 'NOVA SENHA'
                            : 'RECUPERAÇÃO DE ACESSO'
                        ?>
                    </span>

                    <h2>
                        <?= $isResetMode
                            ? 'Crie uma nova senha'
                            : 'Esqueceu sua senha?'
                        ?>
                    </h2>

                    <p>
                        <?= $isResetMode
                            ? 'Defina uma nova senha para acessar o portal.'
                            : 'Informe seu e-mail e enviaremos as instruções para recuperar o acesso.'
                        ?>
                    </p>
                </div>

                <?php if ($isResetMode): ?>

                    <form
                        id="resetPasswordForm"
                        class="reset-form"
                        novalidate
                    >
                        <input
                            type="hidden"
                            id="resetToken"
                            value="<?= htmlspecialchars(
                                $token,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                        <div class="reset-field">
                            <label for="newPassword">
                                Nova senha
                            </label>

                            <div class="reset-input-wrapper">
                                <input
                                    type="password"
                                    id="newPassword"
                                    placeholder="Digite sua nova senha"
                                    autocomplete="new-password"
                                    required
                                >

                                <button
                                    type="button"
                                    class="reset-toggle-password"
                                    data-target="newPassword"
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
                                class="reset-field-error"
                                data-error-for="newPassword"
                            ></small>
                        </div>

                        <div class="reset-field">
                            <label for="confirmPassword">
                                Confirmar nova senha
                            </label>

                            <div class="reset-input-wrapper">
                                <input
                                    type="password"
                                    id="confirmPassword"
                                    placeholder="Digite a senha novamente"
                                    autocomplete="new-password"
                                    required
                                >

                                <button
                                    type="button"
                                    class="reset-toggle-password"
                                    data-target="confirmPassword"
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
                                class="reset-field-error"
                                data-error-for="confirmPassword"
                            ></small>
                        </div>

                        <div
                            class="reset-alert"
                            id="resetAlert"
                            role="alert"
                            aria-live="polite"
                        ></div>

                        <button
                            type="submit"
                            class="reset-button"
                            id="resetButton"
                        >
                            <span class="reset-button-label">
                                Redefinir minha senha
                            </span>

                            <span class="reset-button-loading">
                                <span class="reset-spinner"></span>
                                Salvando nova senha...
                            </span>

                            <svg
                                class="reset-button-arrow"
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

                <?php else: ?>

                    <form
                        id="forgotPasswordForm"
                        class="reset-form"
                        novalidate
                    >
                        <div class="reset-field">
                            <label for="forgotEmail">
                                E-mail cadastrado
                            </label>

                            <div class="reset-input-wrapper">
                                <svg
                                    class="reset-input-icon"
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
                                    id="forgotEmail"
                                    placeholder="seu@email.com"
                                    autocomplete="email"
                                    required
                                >
                            </div>

                            <small
                                class="reset-field-error"
                                data-error-for="forgotEmail"
                            ></small>
                        </div>

                        <div
                            class="reset-alert"
                            id="forgotAlert"
                            role="alert"
                            aria-live="polite"
                        ></div>

                        <div
                            class="reset-success"
                            id="forgotSuccess"
                            role="status"
                            aria-live="polite"
                        >
                            <div class="reset-success-icon">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="m5 12 4 4L19 6"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>

                            <div>
                                <strong>
                                    Solicitação processada
                                </strong>

                                <span>
                                    Se o e-mail estiver cadastrado,
                                    você receberá as instruções.
                                </span>
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="reset-button"
                            id="forgotButton"
                        >
                            <span class="reset-button-label">
                                Enviar instruções
                            </span>

                            <span class="reset-button-loading">
                                <span class="reset-spinner"></span>
                                Enviando...
                            </span>

                            <svg
                                class="reset-button-arrow"
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

                <?php endif; ?>

                <div class="reset-footer">
                    <span class="reset-footer-line"></span>

                    <span>
                        Acesso exclusivo para alunos
                    </span>

                    <span class="reset-footer-line"></span>
                </div>

            </div>
        </section>

    </main>

    <script>
        window.PASSWORD_RESET_TOKEN = <?= json_encode(
            $token,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?>;
    </script>

    <script src="/assets/js/password-reset.js"></script>
</body>
</html>
