<?php

declare(strict_types=1);

$statusCode = 401;
$title = 'Sua sessão expirou';
$message = 'Por segurança, faça login novamente para continuar.';
$buttonText = 'Ir para o login';
$buttonUrl = '/login';

require __DIR__ . '/error-page.php';