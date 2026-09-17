<?php

declare(strict_types=1);

$statusCode = 403;
$title = 'Acesso não disponível';
$message = 'Você não possui permissão para acessar este conteúdo no momento.';
$buttonText = 'Voltar ao portal';
$buttonUrl = '/portal';

require __DIR__ . '/error-page.php';