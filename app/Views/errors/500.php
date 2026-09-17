<?php

declare(strict_types=1);

$statusCode = 500;
$title = 'Ocorreu um erro';
$message = 'Não foi possível carregar esta página agora. Tente novamente em alguns instantes.';
$buttonText = 'Voltar ao portal';
$buttonUrl = '/portal';

require __DIR__ . '/error-page.php';