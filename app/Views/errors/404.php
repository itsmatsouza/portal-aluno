<?php

declare(strict_types=1);

$statusCode = 404;
$title = 'Página não encontrada';
$message = 'O conteúdo que você tentou acessar não existe ou foi removido.';
$buttonText = 'Voltar à página anterior';
$buttonUrl = '/portal';
$buttonUseHistory = true;

require __DIR__ . '/error-page.php';