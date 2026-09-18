<?php

declare(strict_types=1);

$statusCode = 400;
$title = 'Solicitação inválida';
$message = $message ?? 'Confira os dados enviados e tente novamente.';
$buttonText = 'Voltar aos cursos';
$buttonUrl = '/admin/courses';

require __DIR__ . '/error-page.php';
