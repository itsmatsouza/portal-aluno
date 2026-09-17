<?php

declare(strict_types=1);

/**
 * Variáveis esperadas:
 *
 * @var int         $statusCode
 * @var string      $title
 * @var string      $message
 * @var string      $buttonText
 * @var string      $buttonUrl
 * @var bool        $buttonUseHistory
 */

$statusCode = $statusCode ?? 500;
$title = $title ?? 'Ocorreu um erro';
$message = $message ?? 'Não foi possível concluir esta operação.';
$buttonText = $buttonText ?? 'Voltar à página anterior.';
$buttonUrl = $buttonUrl ?? '/portal';
$buttonUseHistory = $buttonUseHistory ?? false;

$buttonUrl = htmlspecialchars(
    $buttonUrl,
    ENT_QUOTES,
    'UTF-8'
);

$title = htmlspecialchars(
    $title,
    ENT_QUOTES,
    'UTF-8'
);

$message = htmlspecialchars(
    $message,
    ENT_QUOTES,
    'UTF-8'
);

$buttonText = htmlspecialchars(
    $buttonText,
    ENT_QUOTES,
    'UTF-8'
);

$cssPath = '/assets/css/errors.css';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= $statusCode ?> — <?= $title ?></title>

    <link rel="stylesheet" href="<?= $cssPath ?>">
</head>
<body>
    <main class="error-page">
        <section class="error-card" aria-labelledby="error-title">
            <div class="error-code">
                <?= $statusCode ?>
            </div>

            <div class="error-divider" aria-hidden="true"></div>

            <h1 id="error-title">
                <?= $title ?>
            </h1>

            <p class="error-message">
                <?= $message ?>
            </p>

            <a
                class="error-button"
                href="<?= $buttonUseHistory ? '#' : $buttonUrl ?>"
                <?php if ($buttonUseHistory): ?>
                    onclick="return goBackOrFallback(event, '<?= $buttonUrl ?>');"
                <?php endif; ?>
            >
                <?= $buttonText ?>
            </a>
        </section>
    </main>

    <?php if ($buttonUseHistory): ?>
        <script>
            function goBackOrFallback(event, fallbackUrl) {
                event.preventDefault();

                if (window.history.length > 1) {
                    window.history.back();
                    return false;
                }

                window.location.href = fallbackUrl;
                return false;
            }
        </script>
    <?php endif; ?>
</body>
</html>